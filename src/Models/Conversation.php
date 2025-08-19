<?php

namespace Filaforge\HuggingfaceChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
	protected $table = 'hf_conversations';

	protected $fillable = [
		'user_id',
		'title',
		'model',
		'messages',
	];

	protected $casts = [
		'messages' => 'array',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(\App\Models\User::class);
	}
}





