<?php

namespace App\Filament\Actions;

use App\Filament\Pages\PrintReceipt;
use App\Models\Order;
use Filament\Actions\Action;

class PrintReceiptAction
{
    public static function make(): Action
    {
        return Action::make('print_receipt')
            ->label('Print Receipt')
            ->icon('heroicon-o-printer')
            ->url(fn (Order $record): string => PrintReceipt::getUrl(['orderId' => $record->order_id]))
            ->openUrlInNewTab();
    }
}
