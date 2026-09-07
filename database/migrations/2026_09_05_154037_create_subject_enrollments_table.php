<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subject_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code'); // e.g., ITE-403
            $table->string('email'); // The student's email
            $table->timestamps();

            // Prevent duplicate enrollments for the same email and subject
            $table->unique(['subject_code', 'email']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_enrollments');
    }
};
