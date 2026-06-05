<?php

namespace App\Listeners;

use App\Events\BookApproved;
use App\Events\BookPublished;
use App\Events\BookSubmitted;
use App\Notifications\BookApprovedNotification;
use App\Notifications\BookPublishedNotification;
use App\Notifications\BookSubmittedNotification;


class NotifyOnBookPublished
{
    /**
     * Notify the author that their book was published.
     */
    public function handle(BookPublished $event): void
    {
        $event->book->author->notify(new BookPublishedNotification($event->book));
    }
}