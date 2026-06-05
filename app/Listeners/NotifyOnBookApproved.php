<?php

namespace App\Listeners;

use App\Events\BookApproved;
use App\Events\BookPublished;
use App\Events\BookSubmitted;
use App\Notifications\BookApprovedNotification;
use App\Notifications\BookPublishedNotification;
use App\Notifications\BookSubmittedNotification;


class NotifyOnBookApproved
{
    /**
     * Notify the author that their book was approved.
     */
    public function handle(BookApproved $event): void
    {
        $event->book->author->notify(new BookApprovedNotification($event->book));
    }
}