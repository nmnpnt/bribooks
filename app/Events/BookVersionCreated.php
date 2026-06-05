<?php

namespace App\Events;

use App\Models\BookVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookVersionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BookVersion $version) {}
}
