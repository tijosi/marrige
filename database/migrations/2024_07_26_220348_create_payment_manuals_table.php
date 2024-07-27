<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('payment_manual', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('presente_id')->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_manual');
    }
};
