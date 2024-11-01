<?php

namespace App\Http\Controllers\API\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Helper;
use App\Models\Mitra;
use App\Models\MitraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MitraController extends Controller
{
    public function storeMitra(Request $request)
    {
        $user = $request->user();

        $mitraUser = Mitra::where('owner_identifier', $user->identifier)->first();
        if ($mitraUser) {
            return response()->json([
                'message' => 'You already have a Mitra on your account'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'category_id' => ['required', 'integer', 'exists:categories,id'], // Added category_id validation
            'helper_ids' => ['required', 'array'], // Expecting an array of helper_ids
            'helper_ids.*' => ['integer', 'exists:helpers,id'], // Validate each helper_id
            'nomor_rekening' => ['required', 'numeric', 'unique:mitras,nomor_rekening']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 400);
        }

        // Check if all helper_ids belong to the same category
        $helperIds = $request->helper_ids;
        $categoryId = $request->category_id;

        // Fetch all helpers that match the provided ids
        $helpers = Helper::whereIn('id', $helperIds)->get();

        // Check if all helpers belong to the same category
        $categoryMismatch = $helpers->contains(function ($helper) use ($categoryId) {
            return $helper->category_id != $categoryId; // Check if any helper has a different category_id
        });

        if ($categoryMismatch) {
            return response()->json([
                'message' => 'All selected helpers must belong to the same category.'
            ], 400);
        }

        $mitra = new Mitra();
        $mitra->name = $request->name;
        $mitra->owner_identifier = $user->identifier;
        $mitra->latitude = $request->latitude;
        $mitra->longitude = $request->longitude;
        $mitra->category_id = $categoryId;
        $mitra->nomor_rekening = $request->nomor_rekening;
        $mitra->save();

        // Attach helper_ids to mitra using the pivot table
        $mitra->helpers()->attach($request->helper_ids);

        return response()->json([
            'message' => 'Successfully created',
            'mitra' => $mitra->load('helpers'), // Load helpers relation for response
        ], 200);
    }

    public function updateMitra(Request $request, $id)
    {
        $mitra = Mitra::find($id);
        $user = $request->user();

        if ($user->phone_number_verified_at == null) {
            return response()->json([
                'message' => "You Phone Number Isn't Verify yet"
            ], 403);
        }

        if (!$mitra) {
            return response()->json([
                'message' => 'Mitra not found',
            ], 404);
        }

        if ($mitra->owner_identifier != $user->identifier) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'helper_ids' => ['array'],
            'helper_ids.*' => ['integer', 'exists:helpers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 400);
        }

        $mitra->name = $request->name;
        $mitra->latitude = $request->latitude;
        $mitra->longitude = $request->longitude;
        $mitra->save();

        // Update helper_ids in pivot table
        if ($request->has('helper_ids')) {
            $mitra->helpers()->sync($request->helper_ids); // Syncing helper_ids
        }

        return response()->json([
            'message' => 'Updated Successfully',
            'mitra' => $mitra->load('helpers'),
        ], 201);
    }

    // public function deleteMitra($id)
    // {
    //     $mitra = Mitra::find($id);

    //     if ($mitra) {
    //         $mitra->delete();

    //         return response()->json([
    //             'message' => 'Data deleted successfully'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Data not found'
    //     ], 404);
    // }

    public function mitraList(Request $request, $id = null)
    {
        if ($id != null) {
            $mitra = Mitra::find($id);
            if(!$mitra) {
                return response()->json(['message' => 'mitra not found'], 404);
            }
        }

        $categoryQuery = $request->query('category');
        if ($categoryQuery) {

            $category = Category::where('name', $categoryQuery)->first();
            if (!$category) {
                return response()->json([
                    'message' => 'invalid category'
                ], 400);
            }

            $mitras = Mitra::where('category_id', $category->id)->get();
            return response()->json(
                $mitras->map(function ($mitra) {
                    return [
                        'id' => $mitra->id,
                        'owner_identifier' => $mitra->owner_identifier,
                        'name' => $mitra->name,
                        'saldo' => $mitra->saldo,
                        'latitude' => $mitra->latitude,
                        'longitude' => $mitra->longitude,
                        'category' => $mitra->category->name,
                    ];
                }), 200);
        }

        $mitras = Mitra::all()->makeHidden(['created_at', 'updated_at']);
        return response()->json(
            $mitras->map(function ($mitra) {
                return [
                    'id' => $mitra->id,
                    'owner_identifier' => $mitra->owner_identifier,
                    'name' => $mitra->name,
                    'saldo' => $mitra->saldo,
                    'latitude' => $mitra->latitude,
                    'longitude' => $mitra->longitude,
                    'category' => $mitra->category->name,
                ];
            }), 200);
    }
}
