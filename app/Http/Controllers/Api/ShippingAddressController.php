<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\StoreShippingAddressRequest;
use App\Http\Requests\Shipping\UpdateShippingAddressRequest;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->shippingAddresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($addresses);
    }

    public function store(StoreShippingAddressRequest $request): JsonResponse
    {
        $user = $request->user();

        $address = $user->shippingAddresses()->create($request->validated());

        if ($request->boolean('is_default')) {
            $this->setDefault($user, $address);
        }

        return response()->json([
            'message' => 'Shipping address created',
            'data' => $address->fresh(),
        ], 201);
    }

    public function update(UpdateShippingAddressRequest $request, ShippingAddress $shippingAddress): JsonResponse
    {
        $this->authorizeAddress($request, $shippingAddress);

        $shippingAddress->update($request->validated());

        if ($request->boolean('is_default')) {
            $this->setDefault($request->user(), $shippingAddress);
        }

        return response()->json([
            'message' => 'Shipping address updated',
            'data' => $shippingAddress->fresh(),
        ]);
    }

    public function destroy(Request $request, ShippingAddress $shippingAddress): JsonResponse
    {
        $this->authorizeAddress($request, $shippingAddress);

        $shippingAddress->delete();

        return response()->json([
            'message' => 'Shipping address deleted',
        ]);
    }

    public function makeDefault(Request $request, ShippingAddress $shippingAddress): JsonResponse
    {
        $this->authorizeAddress($request, $shippingAddress);

        $this->setDefault($request->user(), $shippingAddress);

        return response()->json([
            'message' => 'Default shipping address updated',
            'data' => $shippingAddress->fresh(),
        ]);
    }

    private function authorizeAddress(Request $request, ShippingAddress $shippingAddress): void
    {
        if ($shippingAddress->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function setDefault(User $user, ShippingAddress $shippingAddress): void
    {
        $user->shippingAddresses()
            ->where('id', '<>', $shippingAddress->id)
            ->update(['is_default' => false]);

        $shippingAddress->forceFill(['is_default' => true])->save();
    }
}
