<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Tax::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $tax = Tax::find($id);
        return $tax
            ? response()->json($tax)
            : response()->json(['message' => 'Tax not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $tax = Tax::find($id);
        return $tax
            ? response()->json($tax)
            : response()->json(['message' => 'Tax not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $tax = Tax::find($id);

        if (!$tax) {
            return response()->json(['message' => 'Tax not found'], 404);
        }

        $tax->update($request->all());

        return response()->json(['message' => 'Tax updated successfully', 'data' => $tax]);
    }

    // DELETE
    public function destroy($id)
    {
        $tax = Tax::find($id);

        if (!$tax) {
            return response()->json(['message' => 'Tax not found'], 404);
        }

        $tax->delete();
        return response()->json(['message' => 'Tax deleted successfully']);
    }
}
