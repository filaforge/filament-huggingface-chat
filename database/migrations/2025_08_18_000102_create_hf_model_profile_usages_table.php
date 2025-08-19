<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hf_model_profile_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('model_profile_id');
            $table->timestamp('minute_at');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['user_id','model_profile_id','minute_at'], 'hf_profile_usage_unique');
            $table->index(['model_profile_id','minute_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hf_model_profile_usages');
    }
};
