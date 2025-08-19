<?php

namespace Filaforge\HuggingfaceChat\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Support\Facades\Schema;
use Filaforge\HuggingfaceChat\Models\ModelProfile;

class HfChatServiceProvider extends PackageServiceProvider
{
	public static string $name = 'huggingface-chat';

	public function configurePackage(Package $package): void
	{
		$package
			->name(static::$name)
			->hasConfigFile('hf-chat')
			->hasViews()
			->hasTranslations()
			->hasMigrations()
			->hasInstallCommand(function (InstallCommand $command) {
				$command
					->publishConfigFile()
					->askToRunMigrations();
			});
	}

	public function packageBooted(): void
	{
		// Ensure migrations are loaded even if automatic registration fails in this monorepo context
		// (Some path repository setups can interfere with Spatie Package Tools' default hasMigrations behavior.)
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		FilamentAsset::register([
			Css::make('hf-chat', __DIR__ . '/../../resources/css/hf-chat.css'),
			Js::make('hf-chat', __DIR__ . '/../../resources/js/hf-chat.js'),
		], package: 'filaforge/huggingface-chat');

		// Seed default profiles if they don't exist (idempotent)
		try {
			if (Schema::hasTable('hf_model_profiles')) {
				// Load default profiles from config
				$defaultProfiles = config('hf-chat.default_profiles', []);
				
				foreach ($defaultProfiles as $profileConfig) {
					if (!ModelProfile::query()->where('model_id', $profileConfig['model_id'])->exists()) {
						ModelProfile::create($profileConfig);
					}
				}
			}
		} catch (\Throwable $e) {
			// Silently ignore seeding issues (e.g., during initial migration run before table exists fully)
		}
	}
}



