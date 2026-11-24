<?php

namespace App\Mail\Customer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public User $user
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Made Exclusif!'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.welcome',
            with: [
                'user' => $this->user,
                'shopUrl' => config('app.frontend_url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}