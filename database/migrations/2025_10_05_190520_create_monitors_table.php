<?php

// database/migrations/2024_01_01_000004_create_monitors_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Базова інформація
            $table->string('name');
            $table->string('url', 500);
            $table->enum('type', ['http', 'https'])->default('https');

            // Налаштування перевірки
            $table->integer('check_interval')->default(5); // хвилини
            $table->integer('timeout')->default(30); // секунди

            // Статус
            $table->enum('status', ['up', 'down', 'paused'])->default('up');
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('last_status_code')->nullable();
            $table->integer('last_response_time')->nullable(); // ms

            // Статистика (кешовані значення)
            $table->decimal('uptime_7d', 5, 2)->default(100);
            $table->decimal('uptime_30d', 5, 2)->default(100);

            // Інциденти
            $table->integer('total_incidents')->default(0);

            // Налаштування
            $table->boolean('notifications_enabled')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
