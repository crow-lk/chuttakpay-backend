<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Brand::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $brand = Brand::find($id);
        return $brand
            ? response()->json($brand)
            : response()->json(['message' => 'Brand not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $brand = Brand::find($id);
        return $brand
            ? response()->json($brand)
            : response()->json(['message' => 'Brand not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $brand->update($request->all());

        return response()->json(['message' => 'Brand updated successfully', 'data' => $brand]);
    }

    // DELETE
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $brand->delete();
        return response()->json(['message' => 'Brand deleted successfully']);
    }
}
