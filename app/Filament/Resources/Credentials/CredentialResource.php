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
                            ->options(function ($get) {
                                if ($get('shared_with_type') === 'App\\Models\\User') {
                                    return \App\Models\User::where('id', '!=', Filament::auth()->user()->id)
                                        ->pluck('name', 'id');
                                }
                                if ($get('shared_with_type') === 'App\\Models\\Group') {
                                    return \App\Models\Group::where('created_by', auth()->id())->pluck('name', 'id');
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
                        \App\Models\CredentialShare::updateOrCreate(
                            [
                                'credential_id' => $record->id,
                                'shared_with_type' => $data['shared_with_type'],
                                'shared_with_id' => $data['shared_with_id'],
                            ],
                            [
                                'shared_by_user_id' => Filament::auth()->user()->id,
                                'permission' => $data['permission'],
                            ]
                        );
                    })
                    ->modalHeading('Share Credential'),
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
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCredentials::route('/'),
        ];
    }
}
