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

            // Physical Location
            $table->string('lab_name'); // e.g., "Lab 1", "Lab 2"
            $table->string('pc_number'); // e.g., "PC-01", "PC-02"

            // Hardware Identifiers (Crucial for Theft tracking)
            $table->string('serial_number')->nullable()->unique(); // Case/Monitor serial
            $table->string('asset_tag')->nullable()->unique(); // School asset sticker ID

            /** * Status Logic:
             * 'available' = Green (No one is timed in)
             * 'active' = Red (Student is currently timed in)
             * 'maintenance' = Yellow (PC is broken/out for repair)
             */
            $table->enum('status', ['available', 'active', 'maintenance'])->default('available');

            $table->timestamps();

            // Ensure we don't have two "PC-01" in the same "Lab 1"
            $table->unique(['lab_name', 'pc_number']);
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
