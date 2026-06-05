<?php

namespace App\Providers;

use App\Events\BookApproved;
use App\Events\BookCreated;
use App\Events\BookPublished;
use App\Events\BookSubmitted;
use App\Events\BookVersionCreated;
use App\Listeners\LogBookActivity;
use App\Listeners\NotifyOnBookApproved;
use App\Listeners\NotifyOnBookPublished;
use App\Listeners\NotifyOnBookSubmitted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookCreated::class => [
            LogBookActivity::class,
        ],
        BookVersionCreated::class => [
            LogBookActivity::class,
        ],
        BookSubmitted::class => [
            LogBookActivity::class,
            NotifyOnBookSubmitted::class,
        ],
        BookApproved::class => [
            LogBookActivity::class,
            NotifyOnBookApproved::class,
        ],
        BookPublished::class => [
            LogBookActivity::class,
            NotifyOnBookPublished::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
