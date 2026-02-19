<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\GrnController;
use App\Http\Controllers\Api\GrnItemController;
use App\Http\Controllers\Api\HeroImageSettingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MintpayStatusController;
use App\Http\Controllers\Api\PayHereWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\SizeController;
use App\Http\Controllers\Api\SocialLoginSettingController;
use App\Http\Controllers\Api\StockLevelController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\WelcomePopupSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/social/{provider}', [AuthController::class, 'social']);
Route::get('settings/social-login', [SocialLoginSettingController::class, 'show']);
Route::get('settings/hero-image', [HeroImageSettingController::class, 'show']);
Route::get('settings/welcome-popup', [WelcomePopupSettingController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('shipping-addresses', [ShippingAddressController::class, 'index']);
    Route::post('shipping-addresses', [ShippingAddressController::class, 'store']);
    Route::put('shipping-addresses/{shippingAddress}', [ShippingAddressController::class, 'update']);
    Route::delete('shipping-addresses/{shippingAddress}', [ShippingAddressController::class, 'destroy']);
    Route::post('shipping-addresses/{shippingAddress}/make-default', [ShippingAddressController::class, 'makeDefault']);
});

Route::get('cart', [CartController::class, 'show']);
Route::post('cart/items', [CartController::class, 'store']);
Route::put('cart/items/{cartItem}', [CartController::class, 'update']);
Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);
Route::post('cart/clear', [CartController::class, 'clear']);
Route::post('cart/merge', [CartController::class, 'merge'])->middleware('auth:sanctum');
Route::post('checkout/payments', [CheckoutController::class, 'initiate']);
Route::post('checkout/orders', [CheckoutController::class, 'placeOrder']);
Route::post('payments/payhere/notify', PayHereWebhookController::class)->name('api.payments.payhere.notify');
Route::get('payments/mintpay/status/{payment}', MintpayStatusController::class)->name('api.payments.mintpay.status');

// BRANDS
Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/{id}', [BrandController::class, 'show']);
Route::get('brands/{id}/edit', [BrandController::class, 'edit']);
Route::post('brands', [BrandController::class, 'store']);
Route::put('brands/{id}', [BrandController::class, 'update']);
Route::delete('brands/{id}', [BrandController::class, 'destroy']);

// CATEGORIES
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);
Route::get('categories/{id}/edit', [CategoryController::class, 'edit']);
Route::post('categories', [CategoryController::class, 'store']);
Route::put('categories/{id}', [CategoryController::class, 'update']);
Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

// COLLECTIONS
Route::get('collections', [CollectionController::class, 'index']);
Route::get('collections/{id}', [CollectionController::class, 'show']);
Route::get('collections/{id}/edit', [CollectionController::class, 'edit']);
Route::post('collections', [CollectionController::class, 'store']);
Route::put('collections/{id}', [CollectionController::class, 'update']);
Route::delete('collections/{id}', [CollectionController::class, 'destroy']);

// COLORS
Route::get('colors', [ColorController::class, 'index']);
Route::get('colors/{id}', [ColorController::class, 'show']);
Route::get('colors/{id}/edit', [ColorController::class, 'edit']);
Route::post('colors', [ColorController::class, 'store']);
Route::put('colors/{id}', [ColorController::class, 'update']);
Route::delete('colors/{id}', [ColorController::class, 'destroy']);

// LOCATIONS
Route::get('locations', [LocationController::class, 'index']);
Route::get('locations/{id}', [LocationController::class, 'show']);
Route::get('locations/{id}/edit', [LocationController::class, 'edit']);
Route::post('locations', [LocationController::class, 'store']);
Route::put('locations/{id}', [LocationController::class, 'update']);
Route::delete('locations/{id}', [LocationController::class, 'destroy']);

// PRODUCTS
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('products/{id}/edit', [ProductController::class, 'edit']);
Route::post('products', [ProductController::class, 'store']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);

// PRODUCT IMAGES
Route::get('product-images', [ProductImageController::class, 'index']);
Route::get('product-images/{id}', [ProductImageController::class, 'show']);
Route::get('product-images/{id}/edit', [ProductImageController::class, 'edit']);
Route::post('product-images', [ProductImageController::class, 'store']);
Route::put('product-images/{id}', [ProductImageController::class, 'update']);
Route::delete('product-images/{id}', [ProductImageController::class, 'destroy']);

// PRODUCT VARIANTS
Route::get('product-variants', [ProductVariantController::class, 'index']);
Route::get('product-variants/{id}', [ProductVariantController::class, 'show']);
Route::get('product-variants/{id}/edit', [ProductVariantController::class, 'edit']);
Route::post('product-variants', [ProductVariantController::class, 'store']);
Route::put('product-variants/{id}', [ProductVariantController::class, 'update']);
Route::delete('product-variants/{id}', [ProductVariantController::class, 'destroy']);

// SIZES
Route::get('sizes', [SizeController::class, 'index']);
Route::get('sizes/{id}', [SizeController::class, 'show']);
Route::get('sizes/{id}/edit', [SizeController::class, 'edit']);
Route::post('sizes', [SizeController::class, 'store']);
Route::put('sizes/{id}', [SizeController::class, 'update']);
Route::delete('sizes/{id}', [SizeController::class, 'destroy']);

// TAXES
Route::get('taxes', [TaxController::class, 'index']);
Route::get('taxes/{id}', [TaxController::class, 'show']);
Route::get('taxes/{id}/edit', [TaxController::class, 'edit']);
Route::post('taxes', [TaxController::class, 'store']);
Route::put('taxes/{id}', [TaxController::class, 'update']);
Route::delete('taxes/{id}', [TaxController::class, 'destroy']);

// GRN
Route::get('grn', [GrnController::class, 'index']);
Route::get('grn/{id}', [GrnController::class, 'show']);
Route::get('grn/{id}/edit', [GrnController::class, 'edit']);
Route::post('grn', [GrnController::class, 'store']);
Route::put('grn/{id}', [GrnController::class, 'update']);
Route::delete('grn/{id}', [GrnController::class, 'destroy']);

// GRN ITEMS
Route::get('grn-items', [GrnItemController::class, 'index']);
Route::get('grn-items/{id}', [GrnItemController::class, 'show']);
Route::get('grn-items/{id}/edit', [GrnItemController::class, 'edit']);
Route::post('grn-items', [GrnItemController::class, 'store']);
Route::put('grn-items/{id}', [GrnItemController::class, 'update']);
Route::delete('grn-items/{id}', [GrnItemController::class, 'destroy']);

// STOCK LEVELS
Route::get('stock-levels', [StockLevelController::class, 'index']);
Route::get('stock-levels/{id}', [StockLevelController::class, 'show']);
Route::get('stock-levels/{id}/edit', [StockLevelController::class, 'edit']);
Route::post('stock-levels', [StockLevelController::class, 'store']);
Route::put('stock-levels/{id}', [StockLevelController::class, 'update']);
Route::delete('stock-levels/{id}', [StockLevelController::class, 'destroy']);

// STOCK MOVEMENTS
Route::get('stock-movements', [StockMovementController::class, 'index']);
Route::get('stock-movements/{id}', [StockMovementController::class, 'show']);
Route::get('stock-movements/{id}/edit', [StockMovementController::class, 'edit']);
Route::post('stock-movements', [StockMovementController::class, 'store']);
Route::put('stock-movements/{id}', [StockMovementController::class, 'update']);
Route::delete('stock-movements/{id}', [StockMovementController::class, 'destroy']);

// SUPPLIERS
Route::get('suppliers', [SupplierController::class, 'index']);
Route::get('suppliers/{id}', [SupplierController::class, 'show']);
Route::get('suppliers/{id}/edit', [SupplierController::class, 'edit']);
Route::post('suppliers', [SupplierController::class, 'store']);
Route::put('suppliers/{id}', [SupplierController::class, 'update']);
Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);

// PAYMENTS
Route::get('payments', [PaymentController::class, 'index']);
Route::get('payments/{id}', [PaymentController::class, 'show']);
Route::get('payments/{id}/edit', [PaymentController::class, 'edit']);
Route::post('payments', [PaymentController::class, 'store']);
Route::put('payments/{id}', [PaymentController::class, 'update']);
Route::delete('payments/{id}', [PaymentController::class, 'destroy']);

// PAYMENT METHODS
Route::get('payment-methods', [PaymentMethodController::class, 'index']);
Route::get('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show']);
Route::get('payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit']);
Route::post('payment-methods', [PaymentMethodController::class, 'store']);
Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
