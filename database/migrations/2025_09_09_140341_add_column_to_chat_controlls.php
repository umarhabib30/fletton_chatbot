<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_controlls', function (Blueprint $table) {
            $table->boolean('unread')->default(false);
            $table->integer('unread_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_controlls', function (Blueprint $table) {
             $table->timestamp('date_time')->useCurrent();
        });
    }
};
