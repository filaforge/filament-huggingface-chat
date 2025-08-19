<?php

return [
	'api_key' => env('HF_API_TOKEN'),
	'base_url' => env('HF_BASE_URL', 'https://api-inference.huggingface.co'),
	'model_id' => env('HF_MODEL_ID', 'meta-llama/Meta-Llama-3-8B-Instruct'),
	'stream' => env('HF_STREAM', false),
	'use_openai' => env('HF_USE_OPENAI', true),
	'system_prompt' => env('HF_SYSTEM_PROMPT', 'You are a helpful assistant.'),
	'allow_roles' => [],
	'admin_roles' => [],
	'timeout' => env('HF_TIMEOUT', 60),

	// Default HF models to create if they don't exist
	'default_profiles' => [
		[
			'name' => 'Default Llama',
			'provider' => 'huggingface',
			'model_id' => 'meta-llama/Meta-Llama-3-8B-Instruct',
			'base_url' => 'https://router.huggingface.co',
			'api_key' => null,
			'stream' => false,
			'timeout' => 120,
			'system_prompt' => 'You are a helpful AI assistant powered by Llama 3.',
			'is_active' => true,
		],
		[
			'name' => 'GPT-OSS 120B (Fireworks)',
			'provider' => 'fireworks',
			'model_id' => 'openai/gpt-oss-120b:fireworks-ai',
			'base_url' => 'https://router.huggingface.co',
			'api_key' => null,
			'stream' => true,
			'timeout' => 120,
			'system_prompt' => 'You are a helpful AI assistant powered by GPT-OSS 120B.',
			'is_active' => false,
		],
	],
];


