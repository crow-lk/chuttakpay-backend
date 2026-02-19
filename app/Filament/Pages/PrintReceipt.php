<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSale;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

class PrintReceipt extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public function getView(): string
    {
        return 'filament.pages.print-receipt';
    }

    public ?string $orderId = null;
    public Order $order;

    public $sales;

    public function mount(): void
    {
        // Check if orderId is passed as a route parameter or query parameter
        $this->orderId = Route::current()->parameter('orderId') ?? request()->query('orderId');

        if (! $this->orderId) {
            $this->redirect(route('filament.admin.pages.pos')); // Redirect if no orderId

            return;
        }

        $this->order = Order::findOrFail($this->orderId);

        $this->sales = OrderItem::where('order_id', $this->orderId)->get();

        if ($this->sales->isEmpty()) {
            $this->redirect(route('filament.admin.pages.pos'));
            return;
        }
    }

    public function getHeading(): string
    {
        return 'Receipt for Order #'.$this->orderId;
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.print-receipt';
    }

    public static function getRoute($orderId = null): string
    {
        if ($orderId) {
            return static::getUrl(['orderId' => $orderId]);
        }

        return static::getUrl();
    }
}

