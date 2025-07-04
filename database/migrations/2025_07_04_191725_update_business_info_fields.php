<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->string('business_email')->nullable();
            $table->string('business_phone_number')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('business_address')->nullable();
            $table->string('city')->nullable();
            $table->text('business_description')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('business_website')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn([
                'business_email',
                'business_phone_number',
                'business_registration_number',
                'business_address',
                'city',
                'business_description',
                'country',
                'tax_identification_number',
                'business_website',
            ]);
        });
    }
};
