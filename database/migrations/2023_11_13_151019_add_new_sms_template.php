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
        \App\Models\SmsService::query()->insert([
            'text' => 'Code: {{$code}}',
            'type' => '2fa',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\SmsService::query()->whereType('2fa')->delete();
    }
};
