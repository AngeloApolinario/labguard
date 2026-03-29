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
            $table->string('student_name');
            $table->string('student_id_number');

            // Use 'dateTime' instead of 'timestamp' to avoid MySQL auto-update bugs
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');;
            $table->foreignId('lab_id')->nullable()->constrained()->onDelete('cascade')->after('computer_id');
            $table->timestamps(); // This creates created_at and updated_at
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
