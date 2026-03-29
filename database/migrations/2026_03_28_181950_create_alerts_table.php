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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            // Links the alert to a specific PC
            $table->foreignId('computer_id')->constrained()->onDelete('cascade');

            // Categorize the issue (e.g., 'Hardware', 'Software', 'Network')
            $table->string('issue_type')->default('Technical');

            // The student's actual message
            $table->text('remarks');

            // Tracking the fix
            $table->enum('status', ['pending', 'fixing', 'resolved'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
