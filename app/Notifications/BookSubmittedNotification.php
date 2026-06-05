<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class BookSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Book $book) {}

    public function via($notifiable): array { return ['mail', 'database']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Book Submitted for Review: {$this->book->title}")
            ->line("A new book titled \"{$this->book->title}\" has been submitted for review.")
            ->action('Review Book', url("/books/{$this->book->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'book_id' => $this->book->id,
            'title'   => $this->book->title,
            'event'   => 'book_submitted',
        ];
    }
}
