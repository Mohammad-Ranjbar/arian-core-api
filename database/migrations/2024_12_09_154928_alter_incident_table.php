<?php

use App\Models\Incident;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->tinyInteger('weight')->default(1);
        });

        Incident::query()->create([
            'title' => 'بدون ایراد',
            'title_en' => 'no fault',
            'code' => '000000',
            'weight' => 0
        ]);
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
