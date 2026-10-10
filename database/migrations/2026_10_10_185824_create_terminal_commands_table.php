
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')
                ->constrained('computers')
                ->cascadeOnDelete();

            $table->string('type');
            $table->text('payload')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['computer_id', 'delivered_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_commands');
    }
};
