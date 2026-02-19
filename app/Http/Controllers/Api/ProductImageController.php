<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(ProductImage::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $productImage = ProductImage::find($id);
        return $productImage
            ? response()->json($productImage)
            : response()->json(['message' => 'ProductImage not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $productImage = ProductImage::find($id);
        return $productImage
            ? response()->json($productImage)
            : response()->json(['message' => 'ProductImage not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json(['message' => 'ProductImage not found'], 404);
        }

        $productImage->update($request->all());

        return response()->json(['message' => 'ProductImage updated successfully', 'data' => $productImage]);
    }

    // DELETE
    public function destroy($id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json(['message' => 'ProductImage not found'], 404);
        }

        $productImage->delete();
        return response()->json(['message' => 'ProductImage deleted successfully']);
    }
}
