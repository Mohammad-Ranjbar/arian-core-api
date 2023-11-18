<?php

use App\Models\SmsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        SmsService::query()->insert([
            'text' => 'Code: {{$code}}', 'type' => '2fa',
        ]);
    }


    /**
     * @return void
     */
    public function down(): void
    {
        SmsService::query()->where('type', '2fa')->delete();
    }
};
