<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('duration'); // số tháng
            $table->integer('max_borrows'); // số sách được mượn tối đa (đổi từ max_books)
            $table->integer('borrow_duration'); // thời gian mượn (ngày) (đổi từ loan_duration)
            $table->integer('extension_limit'); // số lần gia hạn
            $table->boolean('can_reserve')->default(false);
            $table->boolean('priority_support')->default(false); // đổi từ priority_reservation
            $table->boolean('delivery')->default(false);
            $table->string('color')->default('#4F46E5');
            $table->boolean('recommended')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
}; 