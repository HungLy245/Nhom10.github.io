<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('book_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('book_id')->constrained();
            $table->enum('status', ['pending', 'available', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_reservations');
    }
}; 