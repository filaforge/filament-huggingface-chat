# Filaforge HuggingFace Chat

[![Latest Version on Packagist](https://img.shields.io/packagist/v/filaforge/huggingface-chat.svg?style=flat-square)](https://packagist.org/packages/filaforge/huggingface-chat)
[![License](https://img.shields.io/packagist/l/filaforge/huggingface-chat.svg?style=flat-square)](https://packagist.org/packages/filaforge/huggingface-chat)

A comprehensive Filament panel plugin for chatting with AI models from multiple providers including HuggingFace, Ollama, and DeepSeek. Features conversation management, model profiles, and user-specific settings.

## Features

- 🤖 **Multi-Provider Support**: HuggingFace, Ollama, DeepSeek
- 💬 **Conversation Management**: Save, load, and organize chat history
- 🎛️ **HF Models**: Pre-configured model settings with easy switching
- 👤 **User-Specific Settings**: Individual API keys and preferences
- 📊 **Usage Tracking**: Monitor API usage and rate limits
- 🎨 **Modern UI**: Clean, responsive interface built with Filament
- 🔐 **Role-Based Access**: Configurable user permissions

## Screenshots

![Chat Interface](docs/screenshots/chat-interface.png)
![Settings Page](docs/screenshots/settings-page.png)
![Model Profiles](docs/screenshots/model-profiles.png)

## Installation

You can install the package via composer:

```bash
composer require filaforge/huggingface-chat
```

### Manual Plugin Registration

Register the plugin in your Filament panel provider:

```php
use Filaforge\HuggingfaceChat\Providers\HfChatPanelPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugins([
            HfChatPanelPlugin::make(),
        ]);
}
```

### Run Migrations

```bash
php artisan migrate
```

## Configuration

Publish and configure the config file:

```bash
php artisan vendor:publish --tag="huggingface-chat-config"
```

### Environment Variables

```bash
# HuggingFace Configuration
HF_API_TOKEN=your_hf_token_here
HF_MODEL_ID=meta-llama/Meta-Llama-3-8B-Instruct
HF_BASE_URL=https://api-inference.huggingface.co
HF_STREAM=false
HF_USE_OPENAI=true
HF_SYSTEM_PROMPT="You are a helpful assistant."
HF_TIMEOUT=60
```

### HF Models

The plugin automatically seeds common HF models:

- **GPT-OSS 120B (Fireworks)** - OpenAI-compatible endpoint
- **Llama 3 Latest (Ollama)** - Local Ollama instance
- **DeepSeek Chat** - DeepSeek API integration

## Usage

### Basic Chat

1. Navigate to the **HF Chat** page in your Filament panel
2. Select a model profile from the dropdown
3. Start chatting with the AI

### Managing Settings

1. Visit **HF Settings** to configure:
   - Global API tokens
   - Default model parameters
   - Request timeouts and streaming options

### HF Models

1. Access **HF Models** from the chat interface
2. Add, edit, or delete model configurations
3. Switch between profiles during conversations

### Conversation History

1. All conversations are automatically saved
2. Access **Conversations** to view chat history
3. Continue previous conversations or start new ones

## Advanced Configuration

### Role-Based Access

Configure user roles in the config file:

```php
'allow_roles' => ['admin', 'editor'],
'admin_roles' => ['admin'],
```

### Custom Model Providers

Add custom HF models programmatically:

```php
use Filaforge\HuggingfaceChat\Models\ModelProfile;

ModelProfile::create([
    'name' => 'Custom Model',
    'provider' => 'custom',
    'model_id' => 'custom/model-name',
    'base_url' => 'https://api.custom-provider.com',
    'api_key' => 'your-api-key',
    'stream' => true,
    'timeout' => 120,
    'system_prompt' => 'Custom system prompt...',
]);
```

## API Reference

### Models

- `Conversation` - Chat conversation storage
- `Setting` - User-specific settings
- `ModelProfile` - Model configuration profiles
- `ModelProfileUsage` - Usage tracking and rate limiting

### Pages

- `HfChatPage` - Main chat interface
- `HfSettingsPage` - Settings management
- `HfConversationsPage` - Conversation history

## Development

### Code Quality

This plugin follows Laravel and Filament best practices:

- PSR-4 autoloading
- Type hints and return types
- Proper error handling
- Comprehensive testing (coming soon)

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Filaforge Team](https://github.com/filaforge)
- [Filament Framework](https://filamentphp.com)
- [Laravel Framework](https://laravel.com)

## Troubleshooting

### Common Model Not Found Error

If you see: `The requested model 'model-name' does not exist`

**Quick Fix**: Use these verified working models:
- `microsoft/DialoGPT-medium`
- `google/flan-t5-large`  
- `facebook/blenderbot-400M-distill`

**Detailed Help**: See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for complete guide.

### API Token Issues

1. Get your token: [HuggingFace Settings](https://huggingface.co/settings/tokens)
2. Set environment variable: `HF_API_TOKEN=your_token_here`
3. Or save in HF Settings page

## Support

- 📧 Email: filaforger@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/filaforge/huggingface-chat/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/filaforge/huggingface-chat/discussions)
- 📖 Troubleshooting: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)






















