<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hf_model_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider')->default('huggingface');
            $table->string('model_id');
            $table->string('base_url')->nullable();
            $table->string('api_key')->nullable();
            $table->boolean('stream')->default(true);
            $table->unsignedInteger('timeout')->default(60);
            $table->text('system_prompt')->nullable();
            $table->json('extra')->nullable();
            $table->unsignedInteger('per_minute_limit')->nullable();
            $table->unsignedInteger('per_day_limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hf_model_profiles');
    }
};
