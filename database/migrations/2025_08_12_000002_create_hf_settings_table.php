<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('hf_settings', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
			$table->string('model_id')->nullable();
			$table->string('base_url')->nullable();
			$table->boolean('use_openai')->default(true);
			$table->boolean('stream')->default(false);
			$table->integer('timeout')->default(60);
			$table->text('system_prompt')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('hf_settings');
	}
};






















