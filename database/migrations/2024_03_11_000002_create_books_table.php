<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('quantity')->default(0);
            $table->foreignId('category_id');
            $table->timestamps();
            
            // Thêm foreign key constraint sau khi tạo bảng
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
}; 