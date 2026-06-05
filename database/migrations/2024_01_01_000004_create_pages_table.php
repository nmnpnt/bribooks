<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->longText('content');
            $table->string('content_type')->default('html'); // html, text, markdown
            $table->integer('page_number')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['chapter_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
