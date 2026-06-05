<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class BookApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Book $book) {}

    public function via($notifiable): array { return ['mail', 'database']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Book Has Been Approved: {$this->book->title}")
            ->line("Congratulations! Your book \"{$this->book->title}\" has been approved.")
            ->line('It is now awaiting final publishing by an admin.');
    }

    public function toArray($notifiable): array
    {
        return [
            'book_id' => $this->book->id,
            'title'   => $this->book->title,
            'event'   => 'book_approved',
        ];
    }
}