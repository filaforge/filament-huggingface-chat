<?php

namespace Filaforge\HuggingfaceChat\Resources\ModelProfileResource\Pages;

use Filaforge\HuggingfaceChat\Resources\ModelProfileResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditModelProfile extends EditRecord
{
    protected static string $resource = ModelProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
