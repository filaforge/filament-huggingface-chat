<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Remove DeepSeek and Ollama Llama3 entries from hf_model_profiles and related usage rows
        $ids = DB::table('hf_model_profiles')
            ->whereIn('model_id', ['deepseek-chat', 'llama3:latest'])
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('hf_model_profile_usages')->whereIn('model_profile_id', $ids)->delete();
            DB::table('hf_model_profiles')->whereIn('id', $ids)->delete();
        }
    }

    public function down(): void
    {
        // no-op: data removal is irreversible
    }
};




