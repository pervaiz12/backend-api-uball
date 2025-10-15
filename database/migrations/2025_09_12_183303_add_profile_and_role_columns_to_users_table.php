<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'home_court')) {
                $table->string('home_court', 100)->nullable()->after('profile_photo');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->after('home_court');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['player', 'staff', 'admin'])->default('player')->after('city');
            }
            if (!Schema::hasColumn('users', 'is_official')) {
                $table->boolean('is_official')->default(false)->after('role');
            }
            if (!Schema::hasColumn('users', 'official_request')) {
                $table->enum('official_request', ['pending', 'approved', 'rejected'])->default('pending')->after('is_official');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'official_request')) {
                $table->dropColumn('official_request');
            }
            if (Schema::hasColumn('users', 'is_official')) {
                $table->dropColumn('is_official');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'home_court')) {
                $table->dropColumn('home_court');
            }
            if (Schema::hasColumn('users', 'profile_photo')) {
                $table->dropColumn('profile_photo');
            }
        });
    }
};
