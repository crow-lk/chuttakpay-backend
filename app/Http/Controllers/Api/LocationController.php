<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Location::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $location = Location::find($id);
        return $location
            ? response()->json($location)
            : response()->json(['message' => 'Location not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $location = Location::find($id);
        return $location
            ? response()->json($location)
            : response()->json(['message' => 'Location not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $location->update($request->all());

        return response()->json(['message' => 'Location updated successfully', 'data' => $location]);
    }

    // DELETE
    public function destroy($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $location->delete();
        return response()->json(['message' => 'Location deleted successfully']);
    }
}
