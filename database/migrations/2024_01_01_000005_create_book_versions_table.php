<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->integer('version_number');
            $table->string('label')->nullable(); // e.g. "v1.0", "First Draft"
            $table->text('change_notes')->nullable();
            // Full snapshot stored as JSON
            $table->json('snapshot'); // {metadata: {...}, chapters: [{...pages}]}
            $table->timestamps();

            $table->unique(['book_id', 'version_number']);
            $table->index('book_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_versions');
    }
};
