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
        Schema::create('presentes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', []);
            $table->decimal('valor_min', 10, 2);
            $table->decimal('valor_max', 10, 2);
            $table->string('level');
            $table->string('name_img');
            $table->longText('img_url')->nullable();
            $table->string('name_selected_id')->nullable();
            $table->boolean('flg_disponivel')->default(1);
            $table->dateTime('selected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentes');
    }
};
