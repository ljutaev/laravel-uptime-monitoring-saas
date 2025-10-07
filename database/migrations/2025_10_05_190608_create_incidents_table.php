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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');

            // Статус
            $table->enum('status', ['ongoing', 'resolved'])->default('ongoing');

            // Час
            $table->timestamp('started_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('duration')->nullable(); // секунди

            // Деталі помилки
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_type')->nullable();

            // Лічильник невдалих перевірок
            $table->integer('failed_checks_count')->default(1);

            // Сповіщення
            $table->boolean('email_sent')->default(false);
            $table->boolean('telegram_sent')->default(false);
            $table->timestamp('notifications_sent_at')->nullable();

            $table->timestamps();

            $table->index(['monitor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
