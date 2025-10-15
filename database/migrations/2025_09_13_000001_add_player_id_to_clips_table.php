<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clips', function (Blueprint $table) {
            $table->foreignId('player_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('clips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('player_id');
        });
    }
};
