<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'hf_last_profile_id')) {
                $table->unsignedBigInteger('hf_last_profile_id')->nullable()->after('hf_api_key');
                $table->foreign('hf_last_profile_id')->references('id')->on('hf_model_profiles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'hf_last_profile_id')) {
                $table->dropConstrainedForeignId('hf_last_profile_id');
            }
        });
    }
};
