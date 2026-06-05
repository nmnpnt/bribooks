<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class BookPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Book $book) {}

    public function via($notifiable): array { return ['mail', 'database']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Book Is Now Live: {$this->book->title}")
            ->line("Your book \"{$this->book->title}\" is now published and live!")
            ->action('View Book', url("/books/{$this->book->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'book_id' => $this->book->id,
            'title'   => $this->book->title,
            'event'   => 'book_published',
        ];
    }
}
