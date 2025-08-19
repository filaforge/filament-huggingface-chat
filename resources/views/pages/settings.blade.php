<x-filament-panels::page>
	<div class="space-y-6">
		<x-filament::section>
			<x-slot name="heading">API Configuration</x-slot>
			<x-slot name="description">Configure your Hugging Face API token for authentication</x-slot>

			<form wire:submit.prevent="save" class="space-y-4">
				<div class="grid grid-cols-1 gap-4">
					<div>
						<x-filament::input.wrapper>
							<x-filament::input
								type="password"
								wire:model.defer="apiKey"
								placeholder="hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
								id="apiKey"
							/>
						</x-filament::input.wrapper>
						<x-filament::field-wrapper.helper-text>
							Your Hugging Face API token. You can find this in your HF account settings.
						</x-filament::field-wrapper.helper-text>
					</div>
				</div>

				<div class="flex justify-end">
					<x-filament::button type="submit" size="sm" icon="heroicon-o-key">
						Save API Token
					</x-filament::button>
				</div>
			</form>
		</x-filament::section>

		<x-filament::section>
			<x-slot name="heading">Model & Request Settings</x-slot>
			<x-slot name="description">Configure default model parameters and request settings</x-slot>

			<form wire:submit.prevent="saveSettingsForm" class="space-y-4" wire:key="hf-settings-form">
				{{ $this->form }}
				<div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
					<x-filament::button type="submit" icon="heroicon-o-check-circle">
						Save Settings
					</x-filament::button>
				</div>
			</form>
		</x-filament::section>
	</div>
</x-filament-panels::page>





