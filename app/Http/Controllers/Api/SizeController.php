<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Size::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $size = Size::find($id);
        return $size
            ? response()->json($size)
            : response()->json(['message' => 'Size not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $size = Size::find($id);
        return $size
            ? response()->json($size)
            : response()->json(['message' => 'Size not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json(['message' => 'Size not found'], 404);
        }

        $size->update($request->all());

        return response()->json(['message' => 'Size updated successfully', 'data' => $size]);
    }

    // DELETE
    public function destroy($id)
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json(['message' => 'Size not found'], 404);
        }

        $size->delete();
        return response()->json(['message' => 'Size deleted successfully']);
    }
}
