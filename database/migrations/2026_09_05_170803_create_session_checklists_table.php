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
        Schema::create('session_checklists', function (Blueprint $table) {
            $table->id();

            // Foreign key to your actual lab_sessions table
            $table->foreignId('lab_session_id')
                ->nullable()
                ->constrained('lab_sessions')
                ->cascadeOnDelete();

            // Matches student_id_number in lab_sessions
            $table->string('student_id_number')->index();
            $table->string('pc_number')->nullable()->index();
            $table->string('lab_name')->nullable();

            // Hardware checklist item booleans
            $table->boolean('monitor_ok')->default(true);
            $table->boolean('keyboard_ok')->default(true);
            $table->boolean('mouse_ok')->default(true);
            $table->boolean('avr_ok')->default(true);
            $table->boolean('pc_case_ok')->default(true);
            $table->boolean('headset_ok')->default(true);

            // Audit status & raw payload
            $table->boolean('all_operational')->default(true);
            $table->json('items_payload')->nullable();

            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_checklists');
    }
};
