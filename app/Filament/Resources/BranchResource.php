<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Operations Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Branch';

    protected static ?string $pluralModelLabel = 'Branches';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Enter the basic details of the branch')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter branch name'),

                                Forms\Components\Select::make('type')
                                    ->options(Branch::getTypes())
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Select branch type'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Branch Code')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Auto-generated code')
                                    ->helperText('This code is automatically generated based on name and type')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->required()
                                    ->default(true)
                                    ->helperText('Enable this to make the branch active for operations'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Contact Information')
                    ->description('Branch contact details and location')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('+1234567890'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('branch@example.com')
                                    ->unique(ignoreRecord: true),
                            ]),

                        Forms\Components\Textarea::make('address')
                            ->label('Full Address')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Enter complete address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Location Coordinates')
                    ->description('GPS coordinates for mapping and delivery routing')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('coordinates.lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., 40.7128')
                                    ->helperText('Enter latitude coordinate')
                                    ->suffixAction(
                                        Action::make('getLocation')
                                            ->label('Get from Map')
                                            ->icon('heroicon-o-map')
                                            ->action(function (Set $set) {
                                                // This would typically integrate with a map service
                                                Notification::make()
                                                    ->title('Location Feature')
                                                    ->body('Map integration would be implemented here')
                                                    ->info()
                                                    ->send();
                                            })
                                    ),

                                Forms\Components\TextInput::make('coordinates.lng')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('e.g., -74.0060')
                                    ->helperText('Enter longitude coordinate'),
                            ]),

                        Placeholder::make('coordinates_display')
                            ->label('Coordinates Preview')
                            ->content(function (Get $get): string {
                                $lat = $get('coordinates.lat');
                                $lng = $get('coordinates.lng');

                                if ($lat && $lng) {
                                    return "📍 {$lat}, {$lng}";
                                }

                                return 'No coordinates set';
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),

                Section::make('Media & Documents')
                    ->description('Upload branch images and documents')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('branch_image')
                            ->label('Branch Image')
                            ->collection('images')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('450')
                            ->maxSize(2048)
                            ->helperText('Upload a high-quality image of the branch (max 2MB)')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('branch_documents')
                            ->label('Branch Documents')
                            ->collection('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->helperText('Upload relevant documents like licenses, permits, etc. (max 5MB each)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('branch_image')
                    ->label('Image')
                    ->collection('images')
                    ->circular()
                    ->size(40),

                TextColumn::make('name')
                    ->label('Branch Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Branch code copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pharmacy' => 'success',
                        'warehouse' => 'warning',
                        'branch' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('address')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return $state;
                    }),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Branch Type')
                    ->options(Branch::getTypes())
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All branches')
                    ->trueLabel('Active branches')
                    ->falseLabel('Inactive branches'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn(Branch $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn(Branch $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn(Branch $record): string => $record->is_active ? 'danger' : 'success')
                        ->action(function (Branch $record): void {
                            $record->update(['is_active' => !$record->is_active]);

                            Notification::make()
                                ->title('Status Updated')
                                ->body("Branch '{$record->name}' has been " . ($record->is_active ? 'activated' : 'deactivated'))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title('Branches Activated')
                                ->body(count($records) . ' branches have been activated')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title('Branches Deactivated')
                                ->body(count($records) . ' branches have been deactivated')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 ? 'success' : 'danger';
    }
}
