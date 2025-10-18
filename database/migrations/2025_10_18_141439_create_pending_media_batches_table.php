<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_media_batches', function (Blueprint $table) {
            $table->id();
            $table->string('user_number')->index();  // WhatsApp Number
            $table->json('media_paths');  // Array of stored images
            $table->timestamp('last_received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_media_batches');
    }
};
