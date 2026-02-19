<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(StockMovement::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $stockMovement = StockMovement::find($id);
        return $stockMovement
            ? response()->json($stockMovement)
            : response()->json(['message' => 'StockMovement not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $stockMovement = StockMovement::find($id);
        return $stockMovement
            ? response()->json($stockMovement)
            : response()->json(['message' => 'StockMovement not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $stockMovement = StockMovement::find($id);

        if (!$stockMovement) {
            return response()->json(['message' => 'StockMovement not found'], 404);
        }

        $stockMovement->update($request->all());

        return response()->json(['message' => 'StockMovement updated successfully', 'data' => $stockMovement]);
    }

    // DELETE
    public function destroy($id)
    {
        $stockMovement = StockMovement::find($id);

        if (!$stockMovement) {
            return response()->json(['message' => 'StockMovement not found'], 404);
        }

        $stockMovement->delete();
        return response()->json(['message' => 'StockMovement deleted successfully']);
    }
}
