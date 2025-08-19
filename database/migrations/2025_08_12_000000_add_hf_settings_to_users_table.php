<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (!Schema::hasColumn('users', 'hf_api_key')) {
				$table->text('hf_api_key')->nullable();
			}
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (Schema::hasColumn('users', 'hf_api_key')) {
				$table->dropColumn('hf_api_key');
			}
		});
	}
};





