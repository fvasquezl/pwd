<?php

namespace App\Filament\Resources\Credentials;

use App\Filament\Resources\Credentials\Pages\CreateCredential;
use App\Filament\Resources\Credentials\Pages\EditCredential;
use App\Filament\Resources\Credentials\Pages\ListCredentials;
use App\Filament\Resources\Credentials\RelationManagers\CredentialShareRelationManager;
use App\Models\Credential;
use App\Models\Group;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

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
                        Select::make('sharedUsers')
                            ->label('Share with Users')
                            ->options(User::where('id', '!=', auth()->id())->pluck('name', 'id'))
                            ->default(fn(Credential $record) => $record->sharedUsers()->pluck('users.id')->toArray())
                            ->multiple()
                            ->searchable(),
                        Select::make('sharedGroups')
                            ->label('Share with Groups')
                            ->options(Group::where('created_by', auth()->id())->pluck('name', 'id'))
                            ->default(fn(Credential $record) => $record->sharedGroups()->pluck('groups.id')->toArray())
                            ->multiple()
                            ->searchable(),
                    ])
                    ->action(function (array $data, Credential $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // 1. Delete all existing shares for this credential
                            $record->credentialShares()->delete();

                            // 2. Create new shares for selected users
                            foreach ($data['sharedUsers'] ?? [] as $userId) {
                                $record->credentialShares()->create([
                                    'shared_with_type' => User::class,
                                    'shared_with_id' => $userId,
                                    'shared_by_user_id' => auth()->id(),
                                    'permission' => 'read', // Or your desired default
                                ]);
                            }

                            // 3. Create new shares for selected groups
                            foreach ($data['sharedGroups'] ?? [] as $groupId) {
                                $record->credentialShares()->create([
                                    'shared_with_type' => Group::class,
                                    'shared_with_id' => $groupId,
                                    'shared_by_user_id' => auth()->id(),
                                    'permission' => 'read', // Or your desired default
                                ]);
                            }
                        });
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
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['credentialShares.sharedWith']);
    }

    public static function getRelations(): array
    {
        return [
            CredentialShareRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCredentials::route('/'),
            'create' => CreateCredential::route('/create'),
            'edit' => EditCredential::route('/{record}/edit'),
        ];
    }
}
