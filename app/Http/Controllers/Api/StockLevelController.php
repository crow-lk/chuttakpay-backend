<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockLevel;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(StockLevel::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $stockLevel = StockLevel::find($id);
        return $stockLevel
            ? response()->json($stockLevel)
            : response()->json(['message' => 'StockLevel not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $stockLevel = StockLevel::find($id);
        return $stockLevel
            ? response()->json($stockLevel)
            : response()->json(['message' => 'StockLevel not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $stockLevel = StockLevel::find($id);

        if (!$stockLevel) {
            return response()->json(['message' => 'StockLevel not found'], 404);
        }

        $stockLevel->update($request->all());

        return response()->json(['message' => 'StockLevel updated successfully', 'data' => $stockLevel]);
    }

    // DELETE
    public function destroy($id)
    {
        $stockLevel = StockLevel::find($id);

        if (!$stockLevel) {
            return response()->json(['message' => 'StockLevel not found'], 404);
        }

        $stockLevel->delete();
        return response()->json(['message' => 'StockLevel deleted successfully']);
    }
}
