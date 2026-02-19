<?php

namespace App\Filament\Resources\Grns;

use App\Filament\Resources\Grns\Pages\CreateGrn;
use App\Filament\Resources\Grns\Pages\EditGrn;
use App\Filament\Resources\Grns\Pages\ListGrns;
use App\Filament\Resources\Grns\Schemas\GrnForm;
use App\Filament\Resources\Grns\Tables\GrnsTable;
use App\Models\Grn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GrnResource extends Resource
{
    protected static ?string $model = Grn::class;

    protected static ?string $navigationLabel = 'Goods Received Notes';

    protected static ?string $modelLabel = 'GRN';

    protected static ?string $pluralModelLabel = 'GRNs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return GrnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\GrnItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrns::route('/'),
            'create' => CreateGrn::route('/create'),
            'edit' => EditGrn::route('/{record}/edit'),
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
