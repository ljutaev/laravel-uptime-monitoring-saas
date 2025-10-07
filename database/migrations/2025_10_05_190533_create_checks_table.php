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
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');

            // Результат перевірки
            $table->boolean('is_up')->default(true);
            $table->integer('status_code')->nullable();
            $table->integer('response_time')->nullable(); // мілісекунди

            // SSL перевірка
            $table->boolean('ssl_valid')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();

            // Помилки
            $table->text('error_message')->nullable();
            $table->string('error_type')->nullable(); // timeout, connection, ssl

            // Час перевірки
            $table->timestamp('checked_at')->index();

            $table->index(['monitor_id', 'checked_at']);
            $table->index(['monitor_id', 'is_up']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
