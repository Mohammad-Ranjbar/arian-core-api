<?php

use App\Models\SmsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('sms_services', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('text');
            $table->softDeletes();
            $table->timestamps();
        });
        $data = [
            [
                'type' => 'driver-enter-notify', 'text' => 'لطفا جهت ورود به درب ورود مراجعه فرمایید',
            ]
        ];
        SmsService::query()->insert($data);
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_services');
    }
};
