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
        Schema::create('computers', function (Blueprint $table) {
            $table->id();

            // Link to the Lab table instead of a string
            $table->foreignId('lab_id')->constrained()->onDelete('cascade');

            $table->string('pc_number'); // e.g., "PC-01"

            // Hardware Identifiers
            $table->string('serial_number')->nullable()->unique();
            $table->string('asset_tag')->nullable()->unique();

            // Status Logic
            $table->enum('status', ['available', 'active', 'maintenance'])->default('available');

            $table->timestamps();

            // Unique constraint: Ensure PC-01 only exists once PER lab
            $table->unique(['lab_id', 'pc_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
