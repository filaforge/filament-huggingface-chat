# Filaforge HuggingFace Chat

A powerful Filament plugin that integrates HuggingFace AI chat capabilities directly into your admin panel.

## Features

- **HuggingFace AI Integration**: Chat with thousands of AI models from HuggingFace
- **Conversation Management**: Save, organize, and continue chat conversations
- **Model Selection**: Choose from a wide variety of AI models
- **Customizable Settings**: Configure API tokens, models, and chat parameters
- **Real-time Chat**: Live chat experience with streaming responses
- **Conversation History**: Keep track of all your AI conversations
- **Export Conversations**: Save and share chat transcripts
- **Role-based Access**: Configurable user permissions and access control
- **Multi-model Support**: Switch between different HuggingFace models
- **Context Awareness**: Maintain conversation context across sessions

## Installation

### 1. Install via Composer

```bash
composer require filaforge/huggingface-chat
```

### 2. Publish & Migrate

```bash
# Publish provider groups (config, views, migrations)
php artisan vendor:publish --provider="Filaforge\\HuggingfaceChat\\Providers\\HfChatServiceProvider"

# Run migrations
php artisan migrate
```

### 3. Register Plugin

Add the plugin to your Filament panel provider:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugin(\Filaforge\HuggingfaceChat\Providers\HfChatPanelPlugin::make());
}
```

## Setup

### Configuration

The plugin will automatically:
- Publish configuration files to `config/hf-chat.php`
- Publish view files to `resources/views/vendor/hf-chat/`
- Publish migration files to `database/migrations/`
- Register necessary routes and middleware

### HuggingFace API Configuration

Configure your HuggingFace API in the published config file:

```php
// config/hf-chat.php
return [
    'api_token' => env('HF_API_TOKEN'),
    'base_url' => env('HF_BASE_URL', 'https://api-inference.huggingface.co'),
    'default_model' => env('HF_MODEL_ID', 'meta-llama/Meta-Llama-3-8B-Instruct'),
    'max_length' => env('HF_MAX_LENGTH', 512),
    'temperature' => env('HF_TEMPERATURE', 0.7),
    'stream' => env('HF_STREAM', false),
    'timeout' => env('HF_TIMEOUT', 60),
    'use_openai_format' => env('HF_USE_OPENAI', true),
];
```

### Environment Variables

Add these to your `.env` file:

```env
HF_API_TOKEN=your_huggingface_api_token_here
HF_BASE_URL=https://api-inference.huggingface.co
HF_MODEL_ID=meta-llama/Meta-Llama-3-8B-Instruct
HF_MAX_LENGTH=512
HF_TEMPERATURE=0.7
HF_STREAM=false
HF_TIMEOUT=60
HF_USE_OPENAI=true
```

### Getting Your HuggingFace API Token

1. Visit [HuggingFace](https://huggingface.co/)
2. Create an account or sign in
3. Go to your profile settings
4. Navigate to "Access Tokens"
5. Generate a new token
6. Copy the token to your `.env` file

## Usage

### Accessing HuggingFace Chat

1. Navigate to your Filament admin panel
2. Look for the "HF Chat" menu item
3. Start chatting with AI models

### Starting a Conversation

1. **Select Model**: Choose from available HuggingFace models
2. **Type Your Message**: Enter your question or prompt
3. **Send Message**: Submit your message to the AI
4. **View Response**: See the AI's response
5. **Continue Chat**: Keep the conversation going

### Managing Conversations

1. **New Chat**: Start a fresh conversation
2. **Save Chat**: Automatically save important conversations
3. **Load Chat**: Resume previous conversations
4. **Export Chat**: Download conversation transcripts
5. **Delete Chat**: Remove unwanted conversations

### Advanced Features

- **Model Selection**: Switch between different HuggingFace models
- **Parameter Tuning**: Adjust temperature, max length, and other settings
- **Context Management**: Maintain conversation context across sessions
- **Streaming Responses**: Real-time AI responses (when supported)

## Troubleshooting

### Common Issues

- **API token errors**: Verify your HuggingFace API token is correct
- **Rate limiting**: Check your HuggingFace API rate limits and usage
- **Model not available**: Ensure the selected model is available and loaded
- **Connection timeouts**: Check network connectivity and timeout settings

### Debug Steps

1. Check the plugin configuration:
```bash
php artisan config:show hf-chat
```

2. Verify routes are registered:
```bash
php artisan route:list | grep hf-chat
```

3. Test API connectivity:
```bash
php artisan tinker
# Test your API token manually
```

4. Check environment variables:
```bash
php artisan tinker
echo env('HF_API_TOKEN');
```

5. Clear caches:
```bash
php artisan optimize:clear
```

6. Check logs for errors:
```bash
tail -f storage/logs/laravel.log
```

### API Error Codes

- **401 Unauthorized**: Invalid or expired API token
- **429 Too Many Requests**: Rate limit exceeded
- **503 Service Unavailable**: Model is currently loading or unavailable
- **Timeout**: Request took too long to complete

## Security Considerations

### Access Control

- **Role-based permissions**: Restrict access to authorized users only
- **API token security**: Never expose API tokens in client-side code
- **User isolation**: Ensure users can only access their own conversations
- **Audit logging**: Track all chat activities and API usage

### Best Practices

- Use environment variables for API tokens
- Implement proper user authentication
- Monitor API usage and costs
- Regularly rotate API tokens
- Set appropriate rate limits

## Uninstall

### 1. Remove Plugin Registration

Remove the plugin from your panel provider:
```php
// remove ->plugin(\Filaforge\HuggingfaceChat\Providers\HfChatPanelPlugin::make())
```

### 2. Roll Back Migrations (Optional)

```bash
php artisan migrate:rollback
# or roll back specific published files if needed
```

### 3. Remove Published Assets (Optional)

```bash
rm -f config/hf-chat.php
rm -rf resources/views/vendor/hf-chat
```

### 4. Remove Package and Clear Caches

```bash
composer remove filaforge/huggingface-chat
php artisan optimize:clear
```

### 5. Clean Up Environment Variables

Remove these from your `.env` file:
```env
HF_API_TOKEN=your_huggingface_api_token_here
HF_BASE_URL=https://api-inference.huggingface.co
HF_MODEL_ID=meta-llama/Meta-Llama-3-8B-Instruct
HF_MAX_LENGTH=512
HF_TEMPERATURE=0.7
HF_STREAM=false
HF_TIMEOUT=60
HF_USE_OPENAI=true
```

## Support

- **Documentation**: [GitHub Repository](https://github.com/filaforge/huggingface-chat)
- **Issues**: [GitHub Issues](https://github.com/filaforge/huggingface-chat/issues)
- **Discussions**: [GitHub Discussions](https://github.com/filaforge/huggingface-chat/discussions)

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This plugin is open-sourced software licensed under the [MIT license](LICENSE).

---

**Made with ❤️ by the Filaforge Team**






















