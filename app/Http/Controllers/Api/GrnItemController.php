<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrnItem;
use Illuminate\Http\Request;

class GrnItemController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(GrnItem::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $grnItem = GrnItem::find($id);
        return $grnItem
            ? response()->json($grnItem)
            : response()->json(['message' => 'GrnItem not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $grnItem = GrnItem::find($id);
        return $grnItem
            ? response()->json($grnItem)
            : response()->json(['message' => 'GrnItem not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $grnItem = GrnItem::find($id);

        if (!$grnItem) {
            return response()->json(['message' => 'GrnItem not found'], 404);
        }

        $grnItem->update($request->all());

        return response()->json(['message' => 'GrnItem updated successfully', 'data' => $grnItem]);
    }

    // DELETE
    public function destroy($id)
    {
        $grnItem = GrnItem::find($id);

        if (!$grnItem) {
            return response()->json(['message' => 'GrnItem not found'], 404);
        }

        $grnItem->delete();
        return response()->json(['message' => 'GrnItem deleted successfully']);
    }
}
