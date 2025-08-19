<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		// Import legacy rows from `hf_chat_models` into `hf_model_profiles` if present.
		if (! Schema::hasTable('hf_chat_models')) {
			return; // nothing to import
		}

		if (! Schema::hasTable('hf_model_profiles')) {
			return; // target table missing (plugin not fully migrated yet)
		}

		$legacyColumns = Schema::getColumnListing('hf_chat_models');
		$has = function (string $col) use ($legacyColumns): bool {
			return in_array($col, $legacyColumns, true);
		};

		DB::transaction(function () use ($has) {
			$rows = DB::table('hf_chat_models')->get();
			$importedIds = [];

			foreach ($rows as $row) {
				// Heuristic mapping from legacy columns to new profile schema
				$name = null;
				if ($has('name')) { $name = (string) ($row->name ?? ''); }
				elseif ($has('title')) { $name = (string) ($row->title ?? ''); }
				elseif ($has('label')) { $name = (string) ($row->label ?? ''); }
				if ($name === '') { $name = 'Imported Model'; }

				$modelId = null;
				if ($has('model_id')) { $modelId = (string) ($row->model_id ?? ''); }
				elseif ($has('model')) { $modelId = (string) ($row->model ?? ''); }
				elseif ($has('slug')) { $modelId = (string) ($row->slug ?? ''); }
				if ($modelId === '') { continue; } // cannot import without a model identifier

				$baseUrl = $has('base_url') ? (string) ($row->base_url ?? '') : '';
				$apiKey = $has('api_key') ? (string) ($row->api_key ?? '') : '';
				$provider = $has('provider') ? (string) ($row->provider ?? '') : '';
				$stream = $has('stream') ? (bool) $row->stream : true;
				$timeout = $has('timeout') ? (int) ($row->timeout ?? 60) : 60;
				$systemPrompt = $has('system_prompt') ? (string) ($row->system_prompt ?? '') : '';
				$isActive = $has('is_active') ? (bool) $row->is_active : true;
				$perMinute = $has('per_minute_limit') ? (int) ($row->per_minute_limit ?? 0) : 0;
				$perDay = $has('per_day_limit') ? (int) ($row->per_day_limit ?? 0) : 0;

				// Derive provider if not explicitly set
				if ($provider === '') {
					$lowerModel = strtolower($modelId);
					$lowerBase = strtolower($baseUrl);
					if (str_contains($lowerBase, 'ollama') || str_contains($lowerModel, 'ollama') || str_contains($lowerModel, 'llama3:')) {
						$provider = 'ollama';
					} elseif (str_contains($lowerModel, 'deepseek')) {
						$provider = 'deepseek';
					} elseif (str_contains($lowerBase, 'huggingface') || str_contains($lowerBase, 'router.huggingface')) {
						$provider = 'huggingface';
					} else {
						$provider = 'huggingface';
					}
				}

				// Avoid duplicates by model_id
				$exists = DB::table('hf_model_profiles')->where('model_id', $modelId)->exists();
				if ($exists) {
					continue;
				}

				$id = DB::table('hf_model_profiles')->insertGetId([
					'name' => $name,
					'provider' => $provider,
					'model_id' => $modelId,
					'base_url' => $baseUrl !== '' ? $baseUrl : null,
					'api_key' => $apiKey !== '' ? $apiKey : null,
					'stream' => (bool) $stream,
					'is_active' => (bool) $isActive,
					'timeout' => $timeout > 0 ? $timeout : 60,
					'system_prompt' => $systemPrompt !== '' ? $systemPrompt : null,
					'per_minute_limit' => $perMinute > 0 ? $perMinute : null,
					'per_day_limit' => $perDay > 0 ? $perDay : null,
					'extra' => null,
					'created_at' => now(),
					'updated_at' => now(),
				]);

				$importedIds[] = $id;
			}

			// If users don't have a selected profile yet, set their default to the first imported profile
			if (! empty($importedIds) && Schema::hasColumn('users', 'hf_last_profile_id')) {
				$firstId = $importedIds[0];
				DB::table('users')
					->whereNull('hf_last_profile_id')
					->update(['hf_last_profile_id' => $firstId]);
			}
		});
	}

	public function down(): void
	{
		// Non-destructive: we do not remove imported profiles on rollback to avoid data loss.
	}
};


