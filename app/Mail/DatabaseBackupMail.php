<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Vendor\BackupData\Models\DataBackup;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public DataBackup $backup;
    public bool $attachmentOmitted;

    public function __construct(DataBackup $backup, bool $attachmentOmitted = false)
    {
        $this->backup = $backup;
        $this->attachmentOmitted = $attachmentOmitted;
    }

    public function build(): static
    {
        return $this
            ->subject('💾 Sauvegarde de la base de données — ' . now()->format('d/m/Y H:i'))
            ->view('emails.database-backup')
            ->with([
                'backup'            => $this->backup,
                'attachmentOmitted' => $this->attachmentOmitted,
            ]);
    }
}
