<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(PurchaseOrder::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::find($id);
        return $purchaseOrder
            ? response()->json($purchaseOrder)
            : response()->json(['message' => 'PurchaseOrder not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::find($id);
        return $purchaseOrder
            ? response()->json($purchaseOrder)
            : response()->json(['message' => 'PurchaseOrder not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::find($id);

        if (!$purchaseOrder) {
            return response()->json(['message' => 'PurchaseOrder not found'], 404);
        }

        $purchaseOrder->update($request->all());

        return response()->json(['message' => 'PurchaseOrder updated successfully', 'data' => $purchaseOrder]);
    }

    // DELETE
    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::find($id);

        if (!$purchaseOrder) {
            return response()->json(['message' => 'PurchaseOrder not found'], 404);
        }

        $purchaseOrder->delete();
        return response()->json(['message' => 'PurchaseOrder deleted successfully']);
    }
}
