<?php

namespace App\Mail;

use App\Models\Lpk;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveillanceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Lpk $lpk,
        public array $alert
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $senderAddress = config('mail.from.address', 'simasadi@kan.or.id');
        $senderName = config('mail.from.name', 'Sekretariat KAN - SIMASADI');

        return new Envelope(
            from: new Address($senderAddress, $senderName),
            subject: "[PEMBERITAHUAN KAN] Jadwal Pengawasan {$this->alert['name']} - {$this->lpk->name} ({$this->lpk->registration_number})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.surveillance-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
