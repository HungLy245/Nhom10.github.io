<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}; 