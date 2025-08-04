<?php

namespace App\Filament\Resources\SharedCredentials;

use App\Filament\Resources\SharedCredentials\Pages\ManageSharedCredentials;
use App\Models\Credential;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SharedCredentialResource extends Resource
{
    protected static ?string $model = \App\Models\CredentialShare::class;

    protected static ?string $navigationLabel = 'Shared with Me';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('credential.username')
                    ->label('Usuario'),
                \Filament\Tables\Columns\TextColumn::make('credential.password')
                    ->label('Contraseña'),
                \Filament\Tables\Columns\TextColumn::make('credential.category.name')
                    ->label('Categoría'),
                \Filament\Tables\Columns\TextColumn::make('sharedBy.name')
                    ->label('Compartido por'),
                \Filament\Tables\Columns\TextColumn::make('permission')
                    ->label('Permiso'),
                \Filament\Tables\Columns\TextColumn::make('sharedWith.name')
                    ->label('Compartido con')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->shared_with_type === 'App\\Models\\User') {
                            return $record->sharedWith?->name;
                        }
                        if ($record->shared_with_type === 'App\\Models\\Group') {
                            return 'Grupo: ' . ($record->sharedWith?->name ?? '');
                        }
                        return '';
                    }),
            ])
            ->filters([
                // Puedes agregar filtros personalizados aquí
            ])
            ->recordActions([
                // Solo lectura, no mostrar acciones de edición/borrado
            ])
            ->toolbarActions([
                // Sin acciones masivas
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('shared_by_user_id', '!=', auth()->id())
            ->where(function ($query) {
                // Compartido directamente con el usuario
                $query->where(function ($q) {
                    $q->where('shared_with_type', 'App\\Models\\User')
                        ->where('shared_with_id', auth()->id());
                });
                // Compartido con grupo al que pertenece el usuario
                $query->orWhere(function ($q) {
                    $q->where('shared_with_type', 'App\\Models\\Group')
                        ->whereIn('shared_with_id', function ($subQ) {
                            $subQ->select('group_id')
                                ->from('group_user')
                                ->where('user_id', auth()->id());
                        });
                });
            });
    }


    public static function getPages(): array
    {
        return [
            'index' => ManageSharedCredentials::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
