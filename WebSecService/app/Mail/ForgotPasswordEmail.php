<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $link = null;
    private $name = null;

    public function __construct($link, $name) {
        $this->link = $link;
        $this->name = $name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.forgot_password',
            with: [
                'link' => $this->link,
                'name' => $this->name,
            ],
        );
    }
} 