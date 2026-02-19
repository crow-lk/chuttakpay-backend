<?php

namespace App\Filament\Resources\Grns\Tables;

use App\Models\Grn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GrnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn_number')
                    ->label('GRN Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchaseOrder.po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->placeholder('N/A'),

                TextColumn::make('received_date')
                    ->label('Received Date')
                    ->date()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'received',
                        'primary' => 'verified',
                    ])
                    ->alignment(Alignment::Center),

                TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable(),

                TextColumn::make('grnItems_count')
                    ->label('Items')
                    ->counts('grnItems')
                    ->alignment(Alignment::Center),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'received' => 'Received',
                        'verified' => 'Verified',
                    ])
                    ->multiple(),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('verify')
                    ->label('Mark as Verified')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Grn $record): bool => $record->status !== 'verified')
                    ->requiresConfirmation()
                    ->modalHeading('Mark GRN as Verified')
                    ->modalDescription('This will update stock levels for all items in this GRN. Are you sure?')
                    ->action(function (Grn $record): void {
                        $record->update(['status' => 'verified']);
                        $record->updateStockLevels();

                        Notification::make()
                            ->title('GRN Verified Successfully')
                            ->body("GRN {$record->grn_number} has been verified and stock levels updated.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
