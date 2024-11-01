<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Helper;
use App\Models\Problem;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $category = new Category();
        $category->name = $request->name;
        $category->save();

        return response()->json([
            'message' => 'Category created successfully',
        ], 201);
    }

    public function deleteCategory($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();
        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }

    public function storeHelper(Request $request)
    {
        $categoryQuery = $request->query('category');
        if(!$categoryQuery) {
            return response()->json(['message' => 'category is required'], 400);
        }

        $category = Category::where('name', $categoryQuery)->first();
        if(!$category) {
            return response()->json(['message' => 'invalid category'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['string', 'required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'error', $validator->errors()], 400);
        }

        $helper = new Helper();
        $helper->name = $request->name;
        $helper->category_id = $category->id;
        $helper->save();

        return response()->json([
            'message' => 'helper created successfully'
        ], 201);
    }

    public function deleteHelper($id) {
        $helper = Helper::find($id);

        if (!$helper) {
            return response()->json(['message' => 'helper not found'], 404);
        }

        $helper->delete();
        return response()->json([
            'message' => 'helper deleted successfully'
        ], 200);
    }

    public function storeProblem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'helper_id' => ['required', 'exists:helpers,id']
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        $helper = Helper::find($request->helper_id);
        if (!$helper) {
            return response()->json(['message' => 'helper not found'], 404);
        }

        $problem = new Problem();
        $problem->name = $request->name;
        $problem->helper_id = $helper->id;
        $problem->save();

        return response()->json([
            'message' => 'Problem created successfully',
        ], 201);
    }

    public function deleteProblem($id)
    {
        $problem = Problem::find($id);

        if (!$problem) {
            return response()->json(['message' => 'problem not found'], 404);
        }

        $problem->delete();
        return response()->json([
            'message' => 'Problem deleted successfully',
        ], 200);
    }

    public function categoryList()
    {
        $categories = Category::all();

        return response()->json([
            'data' => $categories
        ], 200);
    }

    public function helperList(Request $request)
    {
        $categoryQuery = $request->query('category');
        if (!$categoryQuery) {
            return response()->json(['message' => 'category is required'], 400);
        }

        $category = Category::where('name', $categoryQuery)->first();
        if (!$category) {
            return response()->json(['message' => 'invalid category'], 400);
        }

        $helpers = Helper::where('category_id', $category->id)->get();

        return response()->json(['data' => $helpers], 200);
    }

    public function problemList(Request $request)
    {
        $categoryQuery = $request->query('category');
        if (!$categoryQuery) {
            return response()->json(['message' => 'category is required'], 400);
        }

        $category = Category::where('name', $categoryQuery)->first();
        if (!$category) {
            return response()->json(['message' => 'invalid category'], 400);
        }

        $categoryId = $category->id;

        $problems = Problem::whereHas('helper', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })->get();

        if ($problems->isEmpty()) {
            return response()->json(['message' => 'No problems found for this category'], 404);
        }

        return response()->json($problems, 200);
    }
}