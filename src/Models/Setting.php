<?php

namespace Filaforge\HuggingfaceChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
	protected $table = 'hf_settings';

	protected $fillable = [
		'user_id',
		'model_id',
		'base_url',
		'use_openai',
		'stream',
		'timeout',
		'system_prompt',
	];

	protected $casts = [
		'use_openai' => 'boolean',
		'stream' => 'boolean',
		'timeout' => 'integer',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(\App\Models\User::class);
	}
}






















