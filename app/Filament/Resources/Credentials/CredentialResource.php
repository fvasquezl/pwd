<?php

namespace App\Filament\Resources\Credentials;

use App\Filament\Resources\Credentials\Pages\ManageCredentials;
use App\Models\Credential;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->where('user_id', auth()->id())
                    )
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('username'),
                TextEntry::make('password'),
                TextEntry::make('description'),
                TextEntry::make('category.name'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('shared_with')
                    ->label('Compartido con')
                    ->formatStateUsing(function ($state, $record) {
                        $shares = $record->credentialShares ?? [];
                        if (empty($shares)) {
                            return '-';
                        }
                        return collect($shares)->map(function ($share) {
                            if ($share->shared_with_type === 'App\\Models\\User') {
                                return $share->sharedWith?->name;
                            }
                            if ($share->shared_with_type === 'App\\Models\\Group') {
                                return 'Grupo: ' . ($share->sharedWith?->name ?? '');
                            }
                            return '';
                        })->filter()->join("<br>");
                    })
                    ->html(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shared_with')
                    ->label('Compartido con')
                    ->formatStateUsing(function ($state, $record) {

                        $shares = $record->credentialShares ?? [];
                        if (!is_array($shares) && !($shares instanceof \Countable)) {
                            $shares = iterator_to_array($shares);
                        }
                        if (count($shares) === 0) {
                            return '-';
                        }
                        return collect($shares)->map(function ($share) {
                            if ($share->shared_with_type === 'App\\Models\\User') {
                                return $share->sharedWith?->name;
                            }
                            if ($share->shared_with_type === 'App\\Models\\Group') {
                                return 'Grupo: ' . ($share->sharedWith?->name ?? '');
                            }
                            return '';
                        })->filter()->join("<br>");
                    })
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('share')
                    ->label('Share')
                    ->icon('heroicon-o-share')
                    ->form([
                        Select::make('shared_with_type')
                            ->label('Tipo de destino')
                            ->options([
                                'App\\Models\\User' => 'Usuario',
                                'App\\Models\\Group' => 'Grupo',
                            ])
                            ->required(),
                        Select::make('shared_with_id')
                            ->label('Destino')
                            ->multiple()
                            ->options(function ($get, $record) {
                                $shares = $record->credentialShares ?? [];
                                if ($get('shared_with_type') === 'App\\Models\\User') {
                                    $ids = collect($shares)
                                        ->where('shared_with_type', 'App\\Models\\User')
                                        ->pluck('shared_with_id')
                                        ->all();
                                    $usuariosCompartidos = \App\Models\User::whereIn('id', $ids)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    $usuariosNuevos = \App\Models\User::where('id', '!=', Filament::auth()->user()->id)
                                        ->whereNotIn('id', $ids)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    // Agrupa los compartidos y los nuevos en optgroups
                                    $options = [];
                                    if (!empty($usuariosCompartidos)) {
                                        $options['Ya compartido'] = $usuariosCompartidos;
                                    }
                                    if (!empty($usuariosNuevos)) {
                                        $options['Nuevo'] = $usuariosNuevos;
                                    }
                                    return $options;
                                }
                                if ($get('shared_with_type') === 'App\\Models\\Group') {
                                    $ids = collect($shares)
                                        ->where('shared_with_type', 'App\\Models\\Group')
                                        ->pluck('shared_with_id')
                                        ->all();
                                    $gruposCompartidos = \App\Models\Group::whereIn('id', $ids)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    $gruposNuevos = \App\Models\Group::where('created_by', auth()->id())
                                        ->whereNotIn('id', $ids)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    $options = [];
                                    if (!empty($gruposCompartidos)) {
                                        $options['Ya compartido'] = $gruposCompartidos;
                                    }
                                    if (!empty($gruposNuevos)) {
                                        $options['Nuevo'] = $gruposNuevos;
                                    }
                                    return $options;
                                }
                                return [];
                            })
                            ->required()
                            ->searchable(),
                        Select::make('permission')
                            ->label('Permission')
                            ->options([
                                'read' => 'Read Only',
                                'write' => 'Read & Write',
                            ])
                            ->default('read')
                            ->required(),
                    ])
                    ->action(function (array $data, Credential $record) {
                        $existing = \App\Models\CredentialShare::where([
                            'credential_id' => $record->id,
                            'shared_with_type' => $data['shared_with_type'],
                            'shared_with_id' => $data['shared_with_id'],
                        ])->first();
                        if ($existing) {
                            $existing->delete(); // Elimina el destino si ya existe
                        } else {
                            \App\Models\CredentialShare::create([
                                'credential_id' => $record->id,
                                'shared_with_type' => $data['shared_with_type'],
                                'shared_with_id' => $data['shared_with_id'],
                                'shared_by_user_id' => Filament::auth()->user()->id,
                                'permission' => $data['permission'],
                            ]);
                        }
                    })
                    ->modalHeading('Compartir o eliminar destino'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['credentialShares.sharedWith']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCredentials::route('/'),
        ];
    }
}
