# Troubleshooting Guide

## Common Issues and Solutions

### 1. Model Not Found Error (400: model_not_found)

**Error**: `The requested model 'model-name' does not exist`

**Cause**: The model name is incorrect, the model is not available, or you don't have access.

**Solutions**:

1. **Use Verified Models**: Try these confirmed working models:
   - `microsoft/DialoGPT-medium` - Microsoft's conversational model
   - `google/flan-t5-large` - Google's instruction-following model  
   - `facebook/blenderbot-400M-distill` - Facebook's chat model

2. **Check Model Name**: Ensure the model name is exactly correct (case-sensitive)

3. **Verify Model Availability**: Visit the model page on HuggingFace Hub to confirm it exists

4. **Check Access Permissions**: Some models require special access or authentication

### 2. API Token Issues

**Error**: `Missing Hugging Face API token`

**Solutions**:
1. Get your API token from [HuggingFace Settings](https://huggingface.co/settings/tokens)
2. Set it in your environment: `HF_API_TOKEN=your_token_here`
3. Or save it in the HF Settings page

### 3. Model Loading Issues

**Error**: Model takes too long to load or times out

**Solutions**:
1. Increase timeout in settings (60-120 seconds)
2. Try smaller models (they load faster)
3. Use models that are frequently accessed (they stay "warm")

### 4. Response Quality Issues

**Problem**: Poor quality responses

**Solutions**:
1. Adjust the system prompt in HF Models
2. Try different models suited for your use case
3. Use conversation models for chat, instruction models for tasks

## Recommended HF Models

### For Conversation
- `microsoft/DialoGPT-medium` - Best for natural conversation
- `facebook/blenderbot-400M-distill` - Good personality, engaging

### For Instructions/Tasks  
- `google/flan-t5-large` - Excellent at following instructions
- `google/flan-t5-xl` - Even better but slower

### For Code/Technical
- Look for code-specific models or use general instruction models

## Model Profile Configuration Tips

1. **Base URL**: Usually `https://api-inference.huggingface.co`
2. **Stream**: Set to `false` for most models (not all support streaming)
3. **Timeout**: 60-120 seconds depending on model size
4. **System Prompt**: Customize for your use case

## Getting Help

If you continue having issues:

1. Check the [HuggingFace Status Page](https://status.huggingface.co/)
2. Try a different model to isolate the issue
3. Check your API token permissions
4. Consult the [HuggingFace Documentation](https://huggingface.co/docs)

## Error Codes Reference

- `400 model_not_found`: Model doesn't exist or no access
- `401 unauthorized`: Invalid or missing API token  
- `403 forbidden`: No permission to access model
- `429 rate_limited`: Too many requests, wait and retry
- `503 service_unavailable`: Model or service temporarily down

