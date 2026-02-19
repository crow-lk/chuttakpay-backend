<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(ProductVariant::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $productVariant = ProductVariant::find($id);
        return $productVariant
            ? response()->json($productVariant)
            : response()->json(['message' => 'ProductVariant not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $productVariant = ProductVariant::find($id);
        return $productVariant
            ? response()->json($productVariant)
            : response()->json(['message' => 'ProductVariant not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $productVariant = ProductVariant::find($id);

        if (!$productVariant) {
            return response()->json(['message' => 'ProductVariant not found'], 404);
        }

        $productVariant->update($request->all());

        return response()->json(['message' => 'ProductVariant updated successfully', 'data' => $productVariant]);
    }

    // DELETE
    public function destroy($id)
    {
        $productVariant = ProductVariant::find($id);

        if (!$productVariant) {
            return response()->json(['message' => 'ProductVariant not found'], 404);
        }

        $productVariant->delete();
        return response()->json(['message' => 'ProductVariant deleted successfully']);
    }
}
