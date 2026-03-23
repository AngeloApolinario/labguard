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
        Schema::create('lab_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')->constrained()->onDelete('cascade');

            // Student Info (Manual input by teacher)
            $table->string('student_name');
            $table->string('student_id_number');

            $table->timestamp('time_in');
            $table->timestamp('time_out')->nullable();

            // The Teacher who is responsible for this entry
            $table->foreignId('teacher_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_sessions');
    }
};
