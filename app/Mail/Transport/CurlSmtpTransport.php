<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * SMTP transport backed by the curl binary.
 *
 * This is useful for SMTP servers which only accept a specific authentication
 * mechanism (notably AUTH LOGIN) that cannot be negotiated by Symfony Mailer.
 * The raw MIME message produced by Laravel is streamed to curl, so HTML and
 * attachments (the PDF reports) are kept intact.
 */
class CurlSmtpTransport extends AbstractTransport
{
    public function __construct(
        private string $host,
        private int $port,
        private ?string $username,
        private ?string $password,
        private ?string $encryption,
        private int $timeout = 30,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->host === '') {
            throw new TransportException('Le serveur SMTP curl n\'est pas configuré.');
        }

        $curlConfig = $this->createCurlConfigFile();

        try {
            $url = ($this->encryption === 'ssl' ? 'smtps' : 'smtp') . '://' . $this->host . ':' . $this->port;
            $command = [
                'curl',
                '--silent',
                '--show-error',
                '--fail',
                '--url', $url,
                '--mail-from', $message->getEnvelope()->getSender()->getAddress(),
                '--upload-file', '-',
                '--connect-timeout', (string) min($this->timeout, 15),
                '--max-time', (string) $this->timeout,
            ];

            foreach ($message->getEnvelope()->getRecipients() as $recipient) {
                $command[] = '--mail-rcpt';
                $command[] = $recipient->getAddress();
            }

            // --ssl-reqd asks curl to negotiate STARTTLS on smtp://.
            if (in_array($this->encryption, ['tls', 'starttls'], true)) {
                $command[] = '--ssl-reqd';
            }

            if ($curlConfig !== null) {
                // Equivalent to --user "username:password", but the secret is
                // never exposed in the process command line.
                $command[] = '--config';
                $command[] = $curlConfig;
            }

            $result = $this->runCurl($command, $message->toString());
            $message->appendDebug($result['stderr']);

            if ($result['exit_code'] !== 0) {
                throw new TransportException('curl SMTP a échoué (code ' . $result['exit_code'] . '): ' . trim($result['stderr']));
            }
        } finally {
            if ($curlConfig !== null && is_file($curlConfig)) {
                @unlink($curlConfig);
            }
        }
    }

    public function __toString(): string
    {
        return 'curl-smtp://' . $this->host . ':' . $this->port;
    }

    private function createCurlConfigFile(): ?string
    {
        if ($this->username === null || $this->username === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'checktime-curl-');
        if ($path === false) {
            throw new TransportException('Impossible de préparer les identifiants SMTP pour curl.');
        }

        $credentials = $this->escapeCurlConfigValue($this->username . ':' . ($this->password ?? ''));
        $value = "user = \"{$credentials}\"\nlogin-options = \"AUTH=LOGIN\"\n";
        if (file_put_contents($path, $value, LOCK_EX) === false) {
            @unlink($path);
            throw new TransportException('Impossible d\'écrire la configuration SMTP temporaire pour curl.');
        }

        @chmod($path, 0600);
        return $path;
    }

    private function escapeCurlConfigValue(string $value): string
    {
        return str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '\\r', '\\n'], $value);
    }

    /** @return array{exit_code:int, stderr:string} */
    private function runCurl(array $command, string $message): array
    {
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, null, null, ['bypass_shell' => true]);

        if (!is_resource($process)) {
            throw new TransportException('Impossible de démarrer curl. Vérifiez qu\'il est installé sur le serveur.');
        }

        $offset = 0;
        $length = strlen($message);
        while ($offset < $length) {
            $written = fwrite($pipes[0], substr($message, $offset));
            if ($written === false || $written === 0) {
                fclose($pipes[0]);
                proc_terminate($process);
                throw new TransportException('Impossible de transmettre le message à curl.');
            }
            $offset += $written;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return ['exit_code' => proc_close($process), 'stderr' => $stderr];
    }
}
