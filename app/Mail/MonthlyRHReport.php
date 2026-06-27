<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class MonthlyRHReport extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $monthYear = $this->data['month_name'] . ' ' . $this->data['year'];
        $clientName = $this->data['client']->raison_sociale ?? config('app.name', 'CheckTime');
        
        return new Envelope(
            subject: '📊 Rapport Mensuel RH - ' . $monthYear . ' - ' . $clientName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-rh-report-email', // Vue séparée pour l'email
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Les pièces jointes seront ajoutées dynamiquement dans la commande
        return [];
    }
}