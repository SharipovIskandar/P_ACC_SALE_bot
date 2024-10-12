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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tg_user_id')->constrained('tg_users')->onDelete('cascade'); // tg_users jadvali bilan bog'lanadi
            $table->json('properties'); // Foydalanuvchi haqida boshqa ma'lumotlar JSON formatda
            $table->boolean('is_approved')->default(false); // Defaultda tasdiqlanmagan
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
