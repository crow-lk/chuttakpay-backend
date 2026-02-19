<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Supplier::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $supplier = Supplier::find($id);
        return $supplier
            ? response()->json($supplier)
            : response()->json(['message' => 'Supplier not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $supplier = Supplier::find($id);
        return $supplier
            ? response()->json($supplier)
            : response()->json(['message' => 'Supplier not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $supplier->update($request->all());

        return response()->json(['message' => 'Supplier updated successfully', 'data' => $supplier]);
    }

    // DELETE
    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
