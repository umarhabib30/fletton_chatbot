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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            // gpt fields
            $table->longText('open_ai_key')->nullable();
            $table->longText('assistant_id')->nullable();
            // twilio fields
            $table->longText('twilio_sid')->nullable();
            $table->longText('twilio_token')->nullable();
            $table->longText('twilio_whats_app')->nullable();
            // crm fields
            $table->longText('keap_api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
