<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->integer('number_of_draws')->default(0)->after('number_of_victories');
            $table->unique('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropUnique(['team_id']);
            $table->dropColumn('number_of_draws');
        });
    }
};
