<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailableUntilToBookReservationsTable extends Migration
{
    public function up()
    {
        Schema::table('book_reservations', function (Blueprint $table) {
            $table->timestamp('available_until')->nullable();
        });
    }

    public function down()
    {
        Schema::table('book_reservations', function (Blueprint $table) {
            $table->dropColumn('available_until');
        });
    }
} 