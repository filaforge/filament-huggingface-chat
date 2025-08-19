<?php

namespace Filaforge\HuggingfaceChat\Resources\ModelProfileResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ModelProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('provider')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('model_id')->label('Model')->searchable()->limit(40),
                IconColumn::make('stream')->boolean(),
                ToggleColumn::make('is_active')->label('Active')->afterStateUpdated(fn($record,$state)=>$record->update(['is_active'=>$state]))->sortable(),
                TextColumn::make('per_minute_limit')->label('Min')->sortable(),
                TextColumn::make('per_day_limit')->label('Day')->sortable(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Model Profile')
                    ->modalDescription('Are you sure you want to delete this model profile? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Model profile deleted')
                            ->body('The model profile has been deleted successfully.')
                    ),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete HF Models')
                    ->modalDescription('Are you sure you want to delete the selected model profiles? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Model profiles deleted')
                            ->body('The selected model profiles have been deleted successfully.')
                    ),
            ]);
    }
}
