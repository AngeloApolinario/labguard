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

            $table->foreignId('computer_id')
                ->nullable()
                ->constrained('computers')
                ->nullOnDelete();

            $table->string('student_name');

            $table->string('student_id_number');

            $table->dateTime('time_in')->nullable();

            $table->dateTime('time_out')->nullable();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('lab_id')
                ->nullable()
                ->constrained('labs')
                ->nullOnDelete();

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
