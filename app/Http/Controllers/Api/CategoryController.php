<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json(Category::all());
    }

    // SHOW SINGLE
    public function show($id)
    {
        $category = Category::find($id);
        return $category
            ? response()->json($category)
            : response()->json(['message' => 'Category not found'], 404);
    }

    // EDIT
    public function edit($id)
    {
        $category = Category::find($id);
        return $category
            ? response()->json($category)
            : response()->json(['message' => 'Category not found'], 404);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->update($request->all());

        return response()->json(['message' => 'Category updated successfully', 'data' => $category]);
    }

    // DELETE
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
