<?php

namespace App\Filament\Resources\SharedCredentials\Pages;

use App\Filament\Resources\SharedCredentials\SharedCredentialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSharedCredentials extends ManageRecords
{
    protected static string $resource = SharedCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
