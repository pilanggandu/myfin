<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $subjectTitle,
        public string $messageText,
        public array $details = [],
        public ?string $actionUrl = null
    ) {
        $this->actionUrl = $actionUrl ?? url('/dashboard');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ARUS Pengingat] '.$this->subjectTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
            with: [
                'userName' => $this->userName,
                'subjectTitle' => $this->subjectTitle,
                'messageText' => $this->messageText,
                'details' => $this->details,
                'actionUrl' => $this->actionUrl,
            ],
        );
    }
}
