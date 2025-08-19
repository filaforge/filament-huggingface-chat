<?php

namespace Filaforge\HuggingfaceChat\Providers;

use Filaforge\HuggingfaceChat\Pages\HfChatPage;
use Filaforge\HuggingfaceChat\Pages\HfConversationsPage;
use Filaforge\HuggingfaceChat\Pages\HfSettingsPage;
use Filaforge\HuggingfaceChat\Resources\ModelProfileResource;
use Filament\Contracts\Plugin as PluginContract;
use Filament\Panel;

class HfChatPanelPlugin implements PluginContract
{
	public static function make(): static
	{
		return app(static::class);
	}

	public function getId(): string
	{
		return 'huggingface-chat';
	}

	public function register(Panel $panel): void
	{
		// Register pages and resources
		$panel->pages([
			HfChatPage::class,
			HfConversationsPage::class,
		])->resources([
			ModelProfileResource::class,
		]);
	}

	public function boot(Panel $panel): void
	{
		// no-op
	}
}


