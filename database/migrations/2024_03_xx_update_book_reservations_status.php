<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('book_reservations', function (Blueprint $table) {
            // Xóa enum cũ
            DB::statement("ALTER TABLE book_reservations MODIFY COLUMN status ENUM('pending', 'available', 'fulfilled', 'cancelled') DEFAULT 'pending'");
        });
    }

    public function down()
    {
        Schema::table('book_reservations', function (Blueprint $table) {
            DB::statement("ALTER TABLE book_reservations MODIFY COLUMN status ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending'");
        });
    }
}; 