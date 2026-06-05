<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->enum('result', ['passed', 'flagged']);
            $table->json('flagged_items')->nullable(); // list of found violations
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index('book_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
