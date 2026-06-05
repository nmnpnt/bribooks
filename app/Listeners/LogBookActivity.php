<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class LogBookActivity
{
    public function handle(object $event): void
    {
        $class = class_basename($event);

        $context = match (true) {
            property_exists($event, 'book')    => ['book_id' => $event->book->id, 'title' => $event->book->title],
            property_exists($event, 'version') => ['version_id' => $event->version->id, 'book_id' => $event->version->book_id],
            default                            => [],
        };

        Log::info("[Event] {$class}", $context);
    }
}
