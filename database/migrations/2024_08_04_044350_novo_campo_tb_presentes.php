<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentes', function (Blueprint $table) {
            $table->boolean('vlr_simbolico')->default(false)->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('presentes', function ($table) {
            $table->dropColumn('vlr_simbolico');
        });
    }
};
