<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use Illuminate\Http\Request;

class GrnController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Grn::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $grn = Grn::find($id);
        return $grn
            ? response()->json($grn)
            : response()->json(['message' => 'Grn not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $grn = Grn::find($id);
        return $grn
            ? response()->json($grn)
            : response()->json(['message' => 'Grn not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $grn = Grn::find($id);

        if (!$grn) {
            return response()->json(['message' => 'Grn not found'], 404);
        }

        $grn->update($request->all());

        return response()->json(['message' => 'Grn updated successfully', 'data' => $grn]);
    }

    // DELETE
    public function destroy($id)
    {
        $grn = Grn::find($id);

        if (!$grn) {
            return response()->json(['message' => 'Grn not found'], 404);
        }

        $grn->delete();
        return response()->json(['message' => 'Grn deleted successfully']);
    }
}
