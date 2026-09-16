<?php

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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Associated Laboratory
            $table->foreignId('lab_id')->constrained()->cascadeOnDelete();

            // Event Configuration
            $table->boolean('is_event')->default(false);
            $table->date('event_date')->nullable();

            // Host / Teacher Assignment
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // The Teacher (nullable for external events)
            $table->string('speaker_name')->nullable(); // Guest Speaker / Host for Events

            // Recurring day of week (nullable for events with specific calendar dates)
            $table->enum('day', [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday'
            ])->nullable();

            // Time Window
            $table->time('start_time');
            $table->time('end_time');

            // Subject Code (e.g. IT-402) or Event Title (e.g. SEMINAR: CYBERSECURITY)
            $table->string('subject_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
