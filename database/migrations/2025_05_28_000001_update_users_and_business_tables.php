<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('phone_number')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained('business')->onDelete('cascade');
            $table->string('phone_number');
            $table->string('reset_code')->nullable();
            $table->timestamp('reset_code_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn(['business_id', 'phone_number', 'reset_code', 'reset_code_expires_at']);
        });

        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone_number']);
        });
    }
};
