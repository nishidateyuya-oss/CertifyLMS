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
        Schema::create('enrollment_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('enrollment_id')->constrained()->onDelete('cascade');
            $table->string('title', 100);
            $table->date('target_date');
            $table->text('description')->nullable();
            $table->date('achieved_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_goals');
    }
};
