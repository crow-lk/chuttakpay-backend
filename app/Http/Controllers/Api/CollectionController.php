<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Collection::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $collection = Collection::find($id);
        return $collection
            ? response()->json($collection)
            : response()->json(['message' => 'Collection not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $collection = Collection::find($id);
        return $collection
            ? response()->json($collection)
            : response()->json(['message' => 'Collection not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        $collection->update($request->all());

        return response()->json(['message' => 'Collection updated successfully', 'data' => $collection]);
    }

    // DELETE
    public function destroy($id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        $collection->delete();
        return response()->json(['message' => 'Collection deleted successfully']);
    }
}
