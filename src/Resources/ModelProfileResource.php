<?php

namespace Filaforge\HuggingfaceChat\Resources;

use Filaforge\HuggingfaceChat\Models\ModelProfile;
use Filaforge\HuggingfaceChat\Resources\ModelProfileResource\Pages;
use Filaforge\HuggingfaceChat\Resources\ModelProfileResource\Tables\ModelProfilesTable;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;

class ModelProfileResource extends Resource
{
    protected static ?string $model = ModelProfile::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'HF Chat';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'HF Models';
    protected static ?string $pluralModelLabel = 'HF Models';
    protected static ?string $modelLabel = 'Model Profile';
    protected static ?string $slug = 'hf-chat-models';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->schema([
            TextInput::make('name')->label('Profile Name')->required()->maxLength(190)->columnSpanFull(),
            TextInput::make('provider')->default('huggingface')->maxLength(100)->required(),
            TextInput::make('model_id')->label('Model ID')->required()->maxLength(190)->helperText('e.g. meta-llama/Meta-Llama-3-8B-Instruct'),
            TextInput::make('base_url')->label('Base URL')->placeholder('https://api-inference.huggingface.co')->columnSpanFull(),
            TextInput::make('api_key')->label('API Key Override')->password()->revealable()->helperText('Leave blank to use user key')->columnSpanFull(),
            Toggle::make('stream')->default(true),
            Toggle::make('is_active')->label('Active')->default(true),
            TextInput::make('timeout')->numeric()->default(60)->minValue(5)->maxValue(600),
            Textarea::make('system_prompt')->rows(3)->columnSpanFull(),
            KeyValue::make('extra')->keyLabel('Key')->valueLabel('Value')->columnSpanFull()->reorderable(),
            TextInput::make('per_minute_limit')->numeric()->minValue(1)->label('Per-Minute Limit')->helperText('Max messages per user per minute (blank = unlimited)'),
            TextInput::make('per_day_limit')->numeric()->minValue(1)->label('Per-Day Limit')->helperText('Max messages per user per day (blank = unlimited)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ModelProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModelProfiles::route('/'),
            'create' => Pages\CreateModelProfile::route('/create'),
            'edit' => Pages\EditModelProfile::route('/{record}/edit'),
        ];
    }
}
