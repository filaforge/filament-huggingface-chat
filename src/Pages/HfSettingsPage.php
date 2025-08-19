<?php

namespace Filaforge\HuggingfaceChat\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filaforge\HuggingfaceChat\Pages\HfChatPage;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filaforge\HuggingfaceChat\Models\Setting;

class HfSettingsPage extends Page implements HasForms
{
	use InteractsWithForms;
	protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
	protected string $view = 'huggingface-chat::pages.settings';
	protected static ?string $navigationLabel = 'HF Settings';
	protected static \UnitEnum|string|null $navigationGroup = 'HF Chat';
	protected static ?int $navigationSort = 11;
	protected static ?string $title = 'Hugging Face Settings';

	public ?string $apiKey = null;
	/** @var array<string,mixed> */
	public array $settingsData = [];

	public function mount(): void
	{
		$this->apiKey = auth()->user()?->hf_api_key;
		$this->loadSettings();
		// Fill Filament form state if initialized
		if (method_exists($this, 'form')) { $this->form->fill($this->settingsData); }
	}

	public function save(): void
	{
		$user = auth()->user();
		if (! $user) { 
			Notification::make()->title('Authentication required')->danger()->send();
			return; 
		}

		// Validate API key format
		if ($this->apiKey && !str_starts_with(trim($this->apiKey), 'hf_')) {
			Notification::make()
				->title('Invalid API Key')
				->body('Hugging Face API keys should start with "hf_"')
				->danger()
				->send();
			return;
		}

		$user->forceFill(['hf_api_key' => trim($this->apiKey)])->save();

		Notification::make()
			->title('API Token Saved')
			->body('Your Hugging Face API token has been saved successfully.')
			->success()
			->send();
	}

	public function saveSettingsForm(): void
	{
		$userId = (int) auth()->id();
		if (! $userId) { 
			Notification::make()->title('Authentication required')->danger()->send();
			return; 
		}

		try {
			// Prefer Filament form state if available
			$formState = $this->settingsData ?? [];
			$data = $formState;
			
			// Validate required fields
			if (empty($data['model_id'])) {
				Notification::make()
					->title('Validation Error')
					->body('Model ID is required.')
					->danger()
					->send();
				return;
			}

			$payload = [
				'model_id' => trim((string) $data['model_id']),
				'base_url' => trim((string) data_get($data, 'base_url', '')),
				'use_openai' => (bool) data_get($data, 'use_openai', true),
				'stream' => (bool) data_get($data, 'stream', false),
				'timeout' => max(5, min(600, (int) data_get($data, 'timeout', 60))),
				'system_prompt' => trim((string) data_get($data, 'system_prompt', '')),
			];

			Setting::updateOrCreate(['user_id' => $userId], $payload + ['user_id' => $userId]);
			$this->settingsData = $payload;

			// Handle API key if provided
			$apiKey = trim((string) data_get($data, 'hf_api_key', ''));
			if ($apiKey !== '' && $apiKey !== $this->apiKey) { 
				$this->apiKey = $apiKey; 
				$this->save(); 
			}

			Notification::make()
				->title('Settings Saved')
				->body('Your model and request settings have been saved successfully.')
				->success()
				->send();
		} catch (\Exception $e) {
			Notification::make()
				->title('Error Saving Settings')
				->body('An error occurred while saving your settings. Please try again.')
				->danger()
				->send();
		}
	}

	protected function loadSettings(): void
	{
		$userId = (int) auth()->id();
		$record = Setting::query()->where('user_id', $userId)->latest('id')->first();
		$this->settingsData = [
			'model_id' => (string) (data_get($record, 'model_id') ?? config('hf-chat.model_id')),
			'base_url' => (string) (data_get($record, 'base_url') ?? config('hf-chat.base_url')),
			'use_openai' => (bool) (data_get($record, 'use_openai') ?? config('hf-chat.use_openai')),
			'stream' => (bool) (data_get($record, 'stream') ?? config('hf-chat.stream')),
			'timeout' => (int) (data_get($record, 'timeout') ?? config('hf-chat.timeout')),
			'system_prompt' => (string) (data_get($record, 'system_prompt') ?? config('hf-chat.system_prompt')),
			'hf_api_key' => (string) ($this->apiKey ?? ''),
		];
	}





	protected function getFormSchema(): array
	{
		return [
			Forms\Components\TextInput::make('model_id')->label('Model ID')->required()->maxLength(190),
			Forms\Components\TextInput::make('base_url')->label('Base URL')->placeholder('https://api-inference.huggingface.co')->columnSpanFull(),
			Forms\Components\Toggle::make('use_openai')->label('Use OpenAI Compatible API')->default(true),
			Forms\Components\Toggle::make('stream')->label('Stream Responses'),
			Forms\Components\TextInput::make('timeout')->label('Timeout (s)')->numeric()->default(60)->minValue(5)->maxValue(600)->columnSpan(1),
			Forms\Components\Textarea::make('system_prompt')->label('System Prompt')->rows(3)->columnSpanFull(),
			Forms\Components\TextInput::make('hf_api_key')->label('HuggingFace API Key')->password()->revealable()->columnSpanFull(),
		];
	}

	protected function getFormStatePath(): string
	{
		return 'settingsData';
	}

	public static function canAccess(): bool
	{
		$user = auth()->user();
		if (! $user) return false;
		$allowed = (array) config('hf-chat.allow_roles', []);
		if (empty($allowed)) { return true; }
		if (method_exists($user, 'hasAnyRole')) { return $user->hasAnyRole($allowed); }
		$role = data_get($user, 'role');
		return $role ? in_array($role, $allowed, true) : false;
	}
}





