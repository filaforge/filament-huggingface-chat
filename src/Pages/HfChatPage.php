<?php

namespace Filaforge\HuggingfaceChat\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Filaforge\HuggingfaceChat\Models\Conversation;
use Filaforge\HuggingfaceChat\Models\Setting;
use Filaforge\HuggingfaceChat\Models\ModelProfile;
use Filaforge\HuggingfaceChat\Models\ModelProfileUsage;
use Filament\Notifications\Notification;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class HfChatPage extends Page implements Tables\Contracts\HasTable, HasForms
{
	use Tables\Concerns\InteractsWithTable;
	use InteractsWithForms;
	protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';
	protected string $view = 'huggingface-chat::pages.chat';
	protected static ?string $navigationLabel = 'HF Chat';
	protected static \UnitEnum|string|null $navigationGroup = 'HF Chat';
	protected static ?int $navigationSort = 10;
	protected static ?string $title = 'HF Chat';

	public ?string $userInput = '';
	public array $messages = [];
	public ?int $conversationId = null;
    public ?string $hfApiKey = null;
    public ?string $selectedModelId = null;
	public ?int $selectedProfileId = null; // new multi-profile support
	public array $availableProfiles = [];
    public array $settings = [];
	public array $settingsForm = [];
	public ?array $settingsData = [];
    public string $viewMode = 'chat';
	public ?string $profileApiKeyInput = null; // new property for profile API key input
	public bool $showProfileApiKeyPrompt = false; // show prompt only after unauthorized
	public array $profileForm = [
		'name' => '',
		'provider' => 'huggingface',
		'model_id' => '',
		'base_url' => '',
		'api_key' => '',
		'stream' => true,
		'timeout' => 60,
		'system_prompt' => '',
	];
	public ?int $editingProfileId = null;
	public bool $showProfileForm = false;
	/** @var array<int, array{id:int,title:?string,updated_at:string}> */
	public array $conversationList = [];
	public bool $canViewAllChats = false; // admin flag

	public function mount(): void
	{
		$this->messages = [];
		$this->conversationId = null;
		$this->canViewAllChats = $this->canViewAllChats();
		$this->hfApiKey = auth()->user()?->hf_api_key;
        $this->selectedModelId = (string) config('hf-chat.model_id', 'microsoft/DialoGPT-medium');
        $this->loadSettings();
		$this->loadProfiles();
		$this->restoreUserLastProfile();
		$this->settingsForm = $this->settings; // legacy compatibility
		$this->loadConversations();
		if ($this->canViewAllChats) {
			$this->loadAllConversations();
		}
		$open = (string) request()->query('open', '');
		if ($open === 'settings') {
			$this->showSettings();
		}
	}

	protected function loadProfiles(): void
	{
		$this->availableProfiles = ModelProfile::query()
			->where('is_active', true)
			->orderBy('name')
			->get(['id','name','provider','model_id','stream','timeout','api_key'])
			->map(fn($p)=>[
				'id'=>(int)$p->id,
				'name'=>(string)$p->name,
				'provider'=>(string)$p->provider,
				'model_id'=>(string)$p->model_id,
				'stream'=>(bool)$p->stream,
				'timeout'=>(int)($p->timeout ?? 60),
				'api_key'=>(string)($p->api_key ?? ''),
			])->all();
	}

	protected function restoreUserLastProfile(): void
	{
		$user = auth()->user();
		if ($user && $user->hf_last_profile_id) {
			$this->selectedProfileId = (int) $user->hf_last_profile_id;
		} elseif (!$this->selectedProfileId && !empty($this->availableProfiles)) {
			$this->selectedProfileId = (int) $this->availableProfiles[0]['id'];
		}
	}

	public function updatedSelectedProfileId($value): void
	{
		// Remember for user
		$user = auth()->user();
		if ($user) {
			$user->forceFill(['hf_last_profile_id' => $value ?: null])->save();
		}
		// Clear any pending API key input and hide prompt
		$this->profileApiKeyInput = null;
		$this->showProfileApiKeyPrompt = false;
		// Reset conversation context
		$this->newConversation();
	}

	public function showProfiles(): void
	{
		$this->loadProfiles();
		$this->viewMode = 'profiles';
		$this->showProfileForm = false; // start with table only
		if (method_exists($this, 'resetTable')) { $this->resetTable(); }
	}

	public function newProfile(): void
	{
		$this->editingProfileId = null;
		$this->profileForm = [ 'name'=>'','provider'=>'huggingface','model_id'=>'','base_url'=>'','api_key'=>'','stream'=>true,'timeout'=>60,'system_prompt'=>'' ];
		$this->showProfileForm = true;
		$this->viewMode = 'profiles';
	}

	public function cancelProfileForm(): void
	{
		$this->editingProfileId = null;
		$this->showProfileForm = false;
		$this->profileForm = [
			'name' => '',
			'provider' => 'huggingface',
			'model_id' => '',
			'base_url' => '',
			'api_key' => '',
			'stream' => true,
			'timeout' => 60,
			'system_prompt' => '',
		];
	}

	public function saveProfile(): void
	{
		$data = $this->profileForm;
		$data['name'] = trim((string) $data['name']);
		$data['model_id'] = trim((string) $data['model_id']);
		if ($data['name'] === '' || $data['model_id'] === '') { return; }
		if ($this->editingProfileId) {
			$profile = ModelProfile::find($this->editingProfileId);
			if ($profile) {
				$profile->update([
					'name' => $data['name'],
					'provider' => $data['provider'] ?: 'huggingface',
					'model_id' => $data['model_id'],
					'base_url' => $data['base_url'] ?: null,
					'api_key' => $data['api_key'] ?: null,
					'stream' => (bool) $data['stream'],
					'timeout' => (int) ($data['timeout'] ?: 60),
					'system_prompt' => $data['system_prompt'] ?: null,
				]);
			}
		} else {
			$profile = ModelProfile::create([
				'name' => $data['name'],
				'provider' => $data['provider'] ?: 'huggingface',
				'model_id' => $data['model_id'],
				'base_url' => $data['base_url'] ?: null,
				'api_key' => $data['api_key'] ?: null,
				'stream' => (bool) $data['stream'],
				'timeout' => (int) ($data['timeout'] ?: 60),
				'system_prompt' => $data['system_prompt'] ?: null,
			]);
		}
		$this->profileForm = [ 'name'=>'','provider'=>'huggingface','model_id'=>'','base_url'=>'','api_key'=>'','stream'=>true,'timeout'=>60,'system_prompt'=>'' ];
		$this->editingProfileId = null;
		$this->showProfileForm = false;
		$this->loadProfiles();
		if (isset($profile) && $profile) { $this->selectedProfileId = (int) $profile->id; }
		$this->restoreUserLastProfile();
	}

	public function editProfile(int $id): void
	{
		$p = ModelProfile::find($id);
		if (! $p) return;
		$this->editingProfileId = (int) $p->id;
		$this->profileForm = [
			'name' => (string)$p->name,
			'provider' => (string)$p->provider,
			'model_id' => (string)$p->model_id,
			'base_url' => (string)($p->base_url ?? ''),
			'api_key' => '', // never expose stored secret
			'stream' => (bool)$p->stream,
			'timeout' => (int)($p->timeout ?? 60),
			'system_prompt' => (string)($p->system_prompt ?? ''),
		];
		$this->viewMode = 'profiles';
		$this->showProfileForm = true; // open form
	}

	public function deleteProfile(int $id): void
	{
		try {
			$p = ModelProfile::find($id);
			if (! $p) {
				Notification::make()->title('Not found')->danger()->body('Profile not found.')->send();
				return;
			}
			$p->delete();
			// clear selection if needed
			if ($this->selectedProfileId === $id) { $this->selectedProfileId = null; }
			if ($this->editingProfileId === $id) { $this->editingProfileId = null; }
			$this->loadProfiles();
			$this->restoreUserLastProfile();
			Notification::make()->title('Deleted')->success()->body('Profile deleted successfully.')->send();
		} catch (\Exception $e) {
			Notification::make()->title('Error')->danger()->body('Failed to delete profile.')->send();
			report($e);
		}
	}

	public function table(Table $table): Table
	{
		// Dynamic table: show conversations (default) or hf models when in models view
		if ($this->viewMode === 'profiles') {
			return $table
				->query(ModelProfile::query()->orderBy('name'))
				->columns([
					TextColumn::make('name')->label('Name')->sortable()->searchable(),
					TextColumn::make('model_id')->label('Model')->limit(40)->tooltip(fn($record)=>$record->model_id),
					TextColumn::make('provider')->label('Provider')->toggleable(isToggledHiddenByDefault: true),
					TextColumn::make('per_minute_limit')->label('Min')->toggleable(isToggledHiddenByDefault: true),
					TextColumn::make('per_day_limit')->label('Day')->toggleable(isToggledHiddenByDefault: true),
					TextColumn::make('updated_at')->since()->label('Updated'),
				])
				->actions([
					Action::make('use')
						->label('Use')
						->icon('heroicon-o-check')
						->color('primary')
						->action(fn(ModelProfile $record) => $this->setProfile((int) $record->id)),
					Action::make('editProfile')
						->label('Edit')
						->icon('heroicon-o-pencil-square')
						->color('gray')
						->action(fn(ModelProfile $record) => $this->editProfile((int) $record->id)),
					Action::make('deleteProfile')
						->label('Delete')
						->icon('heroicon-o-trash')
						->color('danger')
						->requiresConfirmation()
						->action(fn(ModelProfile $record) => $this->deleteProfile((int) $record->id)),
				])
				->emptyStateHeading('No profiles found.')
				->defaultPaginationPageOption(10)
				->striped();
		}

		// Conversations table
		$query = Conversation::query();
		if (! $this->canViewAllChats) {
			$userId = (int) auth()->id();
			$query->where('user_id', $userId);
		} else {
			$query->with('user:id,name');
		}
		$expr = $this->jsonArrayLengthExpr('messages');
		if ($expr) {
			$query->select($query->getModel()->getTable() . '.*')->selectRaw($expr . ' as messages_count');
		}
		$query->latest('updated_at');
		return $table
			->query($query)
			->defaultPaginationPageOption(10)
			->columns([
				TextColumn::make('title')->label('Title')->searchable()->sortable()->limit(60)->wrap(false),
				TextColumn::make('model')->label('Model')->limit(40),
				TextColumn::make('user.name')->label('User')->visible($this->canViewAllChats),
				TextColumn::make('messages_count')->label('Messages')->visible((bool) $expr)->sortable(['messages_count'])->formatStateUsing(fn ($state) => (string) ($state ?? 0)),
				TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
			])
			->filters([
				SelectFilter::make('user_id')->label('User')->relationship('user', 'name')->visible($this->canViewAllChats),
				TernaryFilter::make('has_replies')->label('Has replies')->queries(
					true: function (Builder $q) use ($expr): Builder { return $expr ? $q->whereRaw($expr . ' > 1') : $q; },
					false: function (Builder $q) use ($expr): Builder { return $expr ? $q->whereRaw($expr . ' <= 1') : $q; }
				),
			])
			->actions([
				Action::make('open')
					->label('Open Chat')
					->icon('heroicon-o-eye')
					->color('gray')
					->action(function (Conversation $record): void { $this->openConversation((int) $record->id); $this->showChat(); }),
				Action::make('delete')
					->label('Delete')
					->icon('heroicon-o-trash')
					->color('danger')
					->requiresConfirmation()
					->action(function (Conversation $record): void { if ($this->canViewAllChats) { $this->adminDeleteConversation((int) $record->id); } else { $this->deleteConversation((int) $record->id); } }),
			])
			->striped()
			->emptyStateHeading('No chats found.');
	}

	public function setProfile(int $id): void
	{
		if (ModelProfile::query()->whereKey($id)->exists()) {
			$this->selectedProfileId = $id;
			$this->updatedSelectedProfileId($id);
		}
	}

	private function jsonArrayLengthExpr(string $column): ?string
	{
		$driver = DB::connection()->getDriverName();
		return match ($driver) {
			'mysql' => "JSON_LENGTH($column)",
			'pgsql' => "json_array_length(($column)::json)",
			'sqlite' => "json_array_length($column)",
			default => null,
		};
	}

	protected function loadConversations(): void
	{
		$userId = (int) auth()->id();
		if (! $userId) {
			$this->conversationList = [];
			return;
		}

		$this->conversationList = Conversation::query()
			->where('user_id', $userId)
			->latest('updated_at')
			->limit(50)
			->get(['id', 'title', 'updated_at'])
			->map(fn ($c) => [
				'id' => (int) $c->id,
				'title' => (string) ($c->title ?? 'Untitled'),
				'updated_at' => (string) $c->updated_at,
			])
			->all();
	}

	public function loadAllConversations(): void
	{
		if (! $this->canViewAllChats) {
			return;
		}

		Conversation::query()
			->with('user:id,name')
			->latest('updated_at')
			->limit(200)
			->get(['id', 'title', 'updated_at', 'user_id'])
			->map(function ($c) {
				return [
					'id' => (int) $c->id,
					'title' => (string) ($c->title ?? 'Untitled'),
					'updated_at' => (string) $c->updated_at,
					'user_id' => (int) $c->user_id,
					'user_name' => (string) optional($c->user)->name ?? 'User #'.$c->user_id,
				];
			})
			->all(); // side-effect intentionally discarded (was previously stored)
	}


	public function newConversation(): void
	{
		$this->conversationId = null;
		$this->messages = [];
		$this->profileApiKeyInput = null; // Clear any pending API key input
		$this->showProfileApiKeyPrompt = false;
	}

	public function openConversation(int $id): void
	{
		$userId = (int) auth()->id();
		if (! $userId) return;

		$query = Conversation::query();
		if (! $this->canViewAllChats) {
			$query->where('user_id', $userId);
		}
		$conv = $query->find($id);

		if (! $conv) return;

		$this->conversationId = (int) $conv->id;
		$this->messages = (array) $conv->messages;
	}

	public function deleteConversation(int $id): void
	{
		$userId = (int) auth()->id();
		if (! $userId) return;

		$query = Conversation::query()->where('user_id', $userId);
		$conv = $query->find($id);

		if (! $conv) return;

		$conv->delete();
		if ($this->conversationId === $id) {
			$this->newConversation();
		}
		$this->loadConversations();
		if ($this->canViewAllChats) {
			$this->loadAllConversations();
		}
	}

	public function adminDeleteConversation(int $id): void
	{
		if (! $this->canViewAllChats) return;

		$conv = Conversation::find($id);
		if (! $conv) return;

		$conv->delete();
		if ($this->conversationId === $id) {
			$this->newConversation();
		}
		$this->loadConversations();
		$this->loadAllConversations();
	}

	public function renameConversation(int $id, string $title): void
	{
		$userId = (int) auth()->id();
		if (! $userId) return;

		$title = trim($title);
		if ($title === '') return;

		$conv = Conversation::query()->where('user_id', $userId)->find($id);
		if (! $conv) return;

		$conv->update(['title' => str($title)->limit(60)]);
		$this->loadConversations();
	}

	public function saveApiKey(string $hf_api_key): void
	{
		$user = auth()->user();
		if (! $user) return;
		$user->forceFill(['hf_api_key' => $hf_api_key])->save();
	}

    public function saveSettings(): void
    {
        $this->saveApiKey((string) $this->hfApiKey);
    }

    public function loadSettings(): void
    {
        $userId = (int) auth()->id();
        $record = Setting::query()->where('user_id', $userId)->latest('id')->first();
        $this->settings = [
            'model_id' => (string) (data_get($record, 'model_id') ?? config('hf-chat.model_id')),
            'base_url' => (string) (data_get($record, 'base_url') ?? config('hf-chat.base_url')),
            'use_openai' => (bool) (data_get($record, 'use_openai') ?? config('hf-chat.use_openai')),
            'stream' => (bool) (data_get($record, 'stream') ?? config('hf-chat.stream')),
            'timeout' => (int) (data_get($record, 'timeout') ?? config('hf-chat.timeout')),
            'system_prompt' => (string) (data_get($record, 'system_prompt') ?? config('hf-chat.system_prompt')),
        ];
        $this->selectedModelId = (string) $this->settings['model_id'];
    }

    public function showSettings(): void
    {
        $this->loadSettings();
        $this->settingsForm = $this->settings;
		$this->settingsData = $this->settings + ['hf_api_key' => $this->hfApiKey];
		// Fill Filament form state if initialized
		if (method_exists($this, 'form')) { $this->form->fill($this->settingsData); }
        $this->viewMode = 'settings';
		$this->showProfileForm = false;
    }

    public function showChat(): void
    {
        $this->viewMode = 'chat';
		$this->showProfileForm = false;
		$this->profileApiKeyInput = null; // Clear any pending API key input
		$this->showProfileApiKeyPrompt = false;
    }

	public function showConversations(): void
	{
		$this->viewMode = 'conversations';
		$this->showProfileForm = false;
		if (method_exists($this, 'resetTable')) { $this->resetTable(); }
	}

    public function newChatFromInput(): void
    {
        $this->newConversation();
        $this->showChat();
    }

    public function saveSettingsForm(): void
    {
		$userId = (int) auth()->id();
		if (! $userId) { return; }
		// Prefer Filament form state if available
		$formState = $this->settingsData ?? $this->settingsForm;
		$payload = [
			'model_id' => (string) data_get($formState, 'model_id'),
			'base_url' => (string) data_get($formState, 'base_url'),
			'use_openai' => (bool) data_get($formState, 'use_openai', true),
			'stream' => (bool) data_get($formState, 'stream', false),
			'timeout' => (int) data_get($formState, 'timeout', 60),
			'system_prompt' => (string) data_get($formState, 'system_prompt', ''),
		];
		Setting::updateOrCreate(['user_id' => $userId], $payload + ['user_id' => $userId]);
		$this->settings = $payload + ['user_id' => $userId];
		$this->selectedModelId = (string) $payload['model_id'];
		$apiKey = (string) data_get($formState, 'hf_api_key', (string) $this->hfApiKey);
		if ($apiKey !== '') { $this->hfApiKey = $apiKey; $this->saveApiKey($apiKey); }
		$this->viewMode = 'chat';
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

	public function send(): void
	{
		$content = trim((string) $this->userInput);
		if ($content === '') { return; }

		$this->messages[] = ['role' => 'user', 'content' => $content];
		$this->userInput = '';
		$this->dispatch('messageSent');

        $apiKey = auth()->user()?->hf_api_key ?: config('hf-chat.api_key');
        $base = rtrim((string) ($this->settings['base_url'] ?? config('hf-chat.base_url')), '/');
        $model = trim((string) ($this->selectedModelId ?: ($this->settings['model_id'] ?? config('hf-chat.model_id', 'meta-llama/Meta-Llama-3-8B-Instruct'))));

		// Resolve model/profile configuration
		$profile = $this->selectedProfileId ? ModelProfile::find($this->selectedProfileId) : null;
		if ($profile) {
			// Rate limiting per profile
			$this->enforceProfileLimits($profile);
			$model = $profile->model_id ?: $model;
			if ($profile->base_url) { $base = rtrim($profile->base_url,'/'); }
			if ($profile->api_key) { $apiKey = $profile->api_key; }
			// override streaming etc. (future enhancement)
		}

		$prompt = $this->buildPrompt($this->messages);

		try {
            $endpointBase = $base;
            $useOpenAi = (bool) ($this->settings['use_openai'] ?? config('hf-chat.use_openai', true));
			$isDedicatedEndpoint = str_contains($endpointBase, 'endpoints.huggingface');
			$urlPrimary = $useOpenAi
				? rtrim($endpointBase, '/') . '/v1/chat/completions'
				: ($isDedicatedEndpoint ? $endpointBase : rtrim($endpointBase, '/') . '/models/' . $model);

			$req = Http::withToken($apiKey)
				->acceptJson()
				->timeout((int) config('hf-chat.timeout', 120))
				->connectTimeout((int) config('hf-chat.connect_timeout', 30));

			$payload = $useOpenAi
				? [
					'model' => $model,
					'messages' => $this->buildOpenAiMessages($this->messages, (string) config('hf-chat.system_prompt', 'You are a helpful assistant.')),
					'max_tokens' => 512,
					'temperature' => 0.7,
				]
				: [
					'inputs' => $prompt,
					'parameters' => [
						'temperature' => 0.7,
						'max_new_tokens' => 512,
						'return_full_text' => false,
					],
					'options' => [
						'wait_for_model' => true,
						'use_cache' => false,
					],
				];

			$response = $req->post($urlPrimary, $payload);

			// If OpenAI style request failed with 400 model_not_supported or not a chat model, auto-fallback to standard HF inference
			if ($useOpenAi && $response->status() === 400) {
				$err = (array) $response->json('error');
				$msg = strtolower((string) ($err['message'] ?? ''));
				$code = strtolower((string) ($err['code'] ?? ''));
				if (str_contains($msg, 'not a chat model') || str_contains($code, 'model_not_supported') || str_contains($msg, 'does not exist') || str_contains($code, 'model_not_found')) {
					$useOpenAi = false; // disable for downstream formatting
					$response = Http::withToken($apiKey)
						->acceptJson()
						->timeout((int) config('hf-chat.timeout', 120))
						->connectTimeout((int) config('hf-chat.connect_timeout', 30))
						->post(rtrim($endpointBase, '/') . '/models/' . $model, [
							'inputs' => $prompt,
							'parameters' => [
								'temperature' => 0.7,
								'max_new_tokens' => 512,
								'return_full_text' => false,
							],
							'options' => [
								'wait_for_model' => true,
								'use_cache' => false,
							],
						]);
				}
			}

			// Fallbacks on 404:
			// 1) If OpenAI path failed, try the standard Inference API models endpoint with non-OpenAI payload
			if ($response->status() === 404 && $useOpenAi) {
				$response = Http::withToken($apiKey)
					->acceptJson()
					->timeout((int) config('hf-chat.timeout', 120))
					->connectTimeout((int) config('hf-chat.connect_timeout', 30))
					->post(rtrim($endpointBase, '/') . '/models/' . $model, [
						'inputs' => $prompt,
						'parameters' => [
							'temperature' => 0.7,
							'max_new_tokens' => 512,
							'return_full_text' => false,
						],
						'options' => [
							'wait_for_model' => true,
							'use_cache' => false,
						],
					]);
			}

			// 2) If still 404 and not dedicated endpoint, try the pipeline path
			if ($response->status() === 404 && ! $isDedicatedEndpoint) {
				$response = Http::withToken($apiKey)
					->acceptJson()
					->timeout((int) config('hf-chat.timeout', 120))
					->connectTimeout((int) config('hf-chat.connect_timeout', 30))
					->post(rtrim($endpointBase, '/') . '/pipeline/text-generation/' . $model, [
						'inputs' => $prompt,
						'parameters' => [
							'temperature' => 0.7,
							'max_new_tokens' => 512,
							'return_full_text' => false,
						],
						'options' => [
							'wait_for_model' => true,
							'use_cache' => false,
						],
					]);
			}

			if ($response->failed()) {
				// Handle unauthorized: now prompt for API key input
				if (in_array($response->status(), [401, 403], true)) {
					$this->showProfileApiKeyPrompt = (bool) $this->selectedProfileId;
					$profName = $profile?->name ?: 'current model';
					$this->messages[] = ['role' => 'assistant', 'content' => "Authentication failed (".$response->status()."). Please add an API key for '{$profName}' and try again."];
					$this->dispatch('messageReceived');
					return;
				}

				$body = (string) $response->body();
				$error = $response->json()['error'] ?? [];
				$errorMessage = $error['message'] ?? 'Unknown error';

				// Provide helpful error messages for common issues
				if (str_contains(strtolower($errorMessage), 'does not exist') || str_contains(strtolower($errorMessage), 'model_not_found')) {
					$helpfulMessage = "⚠️ Model '{$model}' not found. Please check your available models in HF Models or add new ones.";
					$this->messages[] = ['role' => 'assistant', 'content' => $helpfulMessage];
				} else {
					$this->messages[] = ['role' => 'assistant', 'content' => 'HF API error: '.$response->status().' '.str($body)->limit(300)];
				}
				$this->dispatch('messageReceived');
				return;
			}

			$data = $response->json();
			$reply = $useOpenAi
				? (string) data_get($data, 'choices.0.message.content', 'No response.')
				: (is_array($data)
					? (string) data_get($data, '0.generated_text', 'No response.')
					: (string) data_get($data, 'generated_text', 'No response.'));
			$this->messages[] = ['role' => 'assistant', 'content' => $reply];

			$user = auth()->user();
			if ($user) {
				if ($this->conversationId) {
					$conv = Conversation::find($this->conversationId);
					if ($conv && $conv->user_id === $user->id) {
						$conv->update(['messages' => $this->messages, 'model' => $model]);
					}
				} else {
					$conv = Conversation::create([
						'user_id' => $user->id,
						'title' => str($content)->limit(60),
						'model' => $model,
						'messages' => $this->messages,
					]);
					$this->conversationId = $conv->id;
				}
				$this->loadConversations();
			}

			$this->dispatch('messageReceived');
		} catch (\Throwable $e) {
			$this->messages[] = ['role' => 'assistant', 'content' => 'Request failed: '.$e->getMessage()];
			$this->dispatch('messageReceived');
		}
	}

	protected function enforceProfileLimits(ModelProfile $profile): void
	{
		$userId = (int) auth()->id();
		if (! $userId) return;
		$perMinute = $profile->per_minute_limit;
		$perDay = $profile->per_day_limit;
		if ($perMinute || $perDay) {
			if ($perMinute) {
				$usedMin = ModelProfileUsage::countForMinute($userId, (int)$profile->id);
				if ($usedMin >= $perMinute) {
					$this->messages[] = ['role'=>'assistant','content'=>'Rate limit reached for this minute ('.$perMinute.'). Try again shortly.'];
					$this->dispatch('messageReceived');
					throw new \RuntimeException('Minute limit');
				}
			}
			if ($perDay) {
				$usedDay = ModelProfileUsage::countForDay($userId, (int)$profile->id);
				if ($usedDay >= $perDay) {
					$this->messages[] = ['role'=>'assistant','content'=>'Daily rate limit reached ('.$perDay.'). Try again tomorrow.'];
					$this->dispatch('messageReceived');
					throw new \RuntimeException('Day limit');
				}
			}
			// Increment usage (optimistically) now
			ModelProfileUsage::incr($userId, (int)$profile->id);
		}
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

	protected function canViewAllChats(): bool
	{
		$user = auth()->user();
		if (! $user) return false;

		$adminRoles = (array) config('hf-chat.admin_roles', []);
		if (! empty($adminRoles)) {
			if (method_exists($user, 'hasAnyRole')) {
				if ($user->hasAnyRole($adminRoles)) return true;
			}
			$role = data_get($user, 'role');
			if ($role && in_array($role, $adminRoles, true)) return true;
		}

		if (property_exists($user, 'is_admin') && (bool) $user->is_admin) return true;
		return false;
	}

	protected function getHeaderActions(): array
	{
		$mode = $this->viewMode;
		return [
			Action::make('hf_conversations_btn')
				->label('Conversations')
				->icon('heroicon-o-table-cells')
				->color($mode === 'conversations' ? 'primary' : 'gray')
				->action(fn () => $this->showConversations())
				->extraAttributes(['id' => 'hf-conversations-btn', 'wire:key' => 'hf-conversations-btn', 'type' => 'button']),
			Action::make('hf_models_btn')
				->label('Models')
				->icon('heroicon-o-cog-8-tooth')
				->color($mode === 'profiles' ? 'primary' : 'gray')
				->action(fn () => $this->showProfiles())
				->extraAttributes(['id' => 'hf-models-btn', 'wire:key' => 'hf-models-btn', 'type' => 'button']),
			Action::make('hf_new_chat_btn')
				->label('New Chat')
				->icon('heroicon-o-plus')
				->color('primary')
				->action(function (): void { $this->newConversation(); $this->showChat(); })
				->extraAttributes(['id' => 'hf-new-chat-btn', 'wire:key' => 'hf-new-chat-btn', 'type' => 'button']),
		];
	}

	protected function buildPrompt(array $messages): string
	{
		$lines = [];
		foreach ($messages as $m) {
			$role = $m['role'] === 'assistant' ? 'Assistant' : 'User';
			$lines[] = "$role: {$m['content']}";
		}
		$lines[] = 'Assistant:';
		return implode("\n", $lines);
	}

	/**
	 * Build OpenAI-compatible messages array for HF v1/chat/completions.
	 * @param array<int,array{role:string,content:string}> $messages
	 * @return array<int,array{role:string,content:string}>
	 */
	protected function buildOpenAiMessages(array $messages, string $systemPrompt): array
	{
		$out = [];
		if ($systemPrompt !== '') {
			$out[] = ['role' => 'system', 'content' => $systemPrompt];
		}
		foreach ($messages as $m) {
			$role = $m['role'] === 'assistant' ? 'assistant' : 'user';
			$out[] = ['role' => $role, 'content' => (string) $m['content']];
		}
		return $out;
	}

	/**
	 * Save the API key input for the current model profile
	 */
	public function saveProfileApiKey(): void
	{
		if (!$this->selectedProfileId || !$this->profileApiKeyInput) {
			return;
		}

		$profile = ModelProfile::find($this->selectedProfileId);
		if (!$profile) {
			return;
		}

		// Update the profile with the new API key
		$profile->update(['api_key' => $this->profileApiKeyInput]);

		// Clear the input and hide prompt
		$this->profileApiKeyInput = null;
		$this->showProfileApiKeyPrompt = false;

		// Reload profiles to reflect the change
		$this->loadProfiles();

		// Show success message
		$this->messages[] = ['role' => 'assistant', 'content' => "✅ API key saved for '{$profile->name}' profile. You can now use this model."];
		$this->dispatch('messageReceived');
	}
}


