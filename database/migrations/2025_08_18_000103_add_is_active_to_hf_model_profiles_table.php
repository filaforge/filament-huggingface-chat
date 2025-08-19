<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hf_model_profiles', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('stream');
        });
    }

    public function down(): void
    {
        Schema::table('hf_model_profiles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};


