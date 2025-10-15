<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_upload_clips')) {
                $table->boolean('can_upload_clips')->default(false)->after('role');
            }
            if (!Schema::hasColumn('users', 'is_official')) {
                $table->boolean('is_official')->default(false)->after('can_upload_clips');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_official')) {
                $table->dropColumn('is_official');
            }
            if (Schema::hasColumn('users', 'can_upload_clips')) {
                $table->dropColumn('can_upload_clips');
            }
        });
    }
};
