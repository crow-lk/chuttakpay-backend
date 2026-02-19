<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Color::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $color = Color::find($id);
        return $color
            ? response()->json($color)
            : response()->json(['message' => 'Color not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $color = Color::find($id);
        return $color
            ? response()->json($color)
            : response()->json(['message' => 'Color not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $color = Color::find($id);

        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $color->update($request->all());

        return response()->json(['message' => 'Color updated successfully', 'data' => $color]);
    }

    // DELETE
    public function destroy($id)
    {
        $color = Color::find($id);

        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $color->delete();
        return response()->json(['message' => 'Color deleted successfully']);
    }
}
