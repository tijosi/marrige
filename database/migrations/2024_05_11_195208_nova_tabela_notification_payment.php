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
        Schema::create('webhook_payment', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable();
            $table->string('api_version')->nullable();
            $table->dateTime('date_created')->nullable();
            $table->string('user_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->text('json')->nullable();
        });

        Schema::create('gift_payment', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payment_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('presente_id')->nullable();
            $table->string('valor')->nullable();
            $table->string('status')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('dt_created')->nullable();
            $table->dateTime('dt_updated')->nullable();
        });

        Schema::table('presentes', function (Blueprint $table) {
            $table->string('tipo_selected')->nullable();
            $table->text('payment_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_payment');
        Schema::dropIfExists('gift_payment');

        Schema::table('presentes', function (Blueprint $table) {
            $table->dropColumn('tipo_selected');
            $table->dropColumn('payment_url');
        });
    }
};
