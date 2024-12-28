<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        \App\Models\Setting::query()->create([
            'key' => 'notify_with_delay_minute',
            'value' => '30',
        ]);
    }


    public function down(): void
    {

    }
};
