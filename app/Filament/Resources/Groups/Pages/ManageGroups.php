<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGroups extends ManageRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();

                    return $data;
                })
        ];
    }
}
