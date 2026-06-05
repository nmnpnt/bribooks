<?php

namespace App\Listeners;

use App\Events\BookApproved;
use App\Events\BookPublished;
use App\Events\BookSubmitted;
use App\Notifications\BookApprovedNotification;
use App\Notifications\BookPublishedNotification;
use App\Notifications\BookSubmittedNotification;


class NotifyOnBookSubmitted
{
    /**
     * Notify all reviewers that a book is waiting for review.
     */
    public function handle(BookSubmitted $event): void
    {
        $reviewers = \App\Models\User::where('role', 'reviewer')->get();
        foreach ($reviewers as $reviewer) {
            $reviewer->notify(new BookSubmittedNotification($event->book));
        }
    }
}