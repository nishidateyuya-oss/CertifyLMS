<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qa_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('certification_id')->constrained()->onDelete('cascade');
            $table->string('title', 200);
            $table->text('body');
            $table->string('status')->default('未解決');
            $table->timestamps();
            $table->index(['certification_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
