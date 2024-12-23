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
    public function createOrUpdateMitra(Request $request)
    {
        $user = $request->user();

        $mitra = Mitra::where('owner_identifier', $user->identifier)->first();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'helper_ids' => ['required', 'array'],
            'helper_ids.*' => ['integer', 'exists:helpers,id'],
        ];

        if (!$mitra) {
            $rules['nomor_rekening'] = ['required', 'numeric', 'unique:mitras,nomor_rekening'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $helperIds = $request->helper_ids;
        $categoryId = $request->category_id;

        $helpers = Helper::whereIn('id', $helperIds)->get();
        $categoryMismatch = $helpers->contains(function ($helper) use ($categoryId) {
            return $helper->category_id != $categoryId;
        });

        if ($categoryMismatch) {
            return response()->json([
                'message' => 'All selected helpers must belong to the same category.',
            ], 400);
        }

        if (!$mitra) {
            $mitra = new Mitra();
            $mitra->owner_identifier = $user->identifier;
            $mitra->nomor_rekening = $request->nomor_rekening;
        }

        $mitra->name = $request->name;
        $mitra->latitude = $request->latitude;
        $mitra->longitude = $request->longitude;
        $mitra->category_id = $categoryId;
        $mitra->save();

        $mitra->helpers()->sync($helperIds);

        return response()->json([
            'message' => $mitra->wasRecentlyCreated ? 'Successfully created' : 'Updated Successfully',
            'mitra' => $mitra->load('helpers'),
        ], $mitra->wasRecentlyCreated ? 200 : 201);
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
    //     ], 400);
    // }

    public function mitraList(Request $request)
    {
        $user = $request->user();
        if ($user->role == 'mitra') {
            $mitra = Mitra::where('owner_identifier', $user->identifier)->first();

            if (!$mitra) {
                return response()->json(['message' => 'mitra not found'], 400);
            }

            $mitra->load(['helpers' => function ($query) {
                $query->select('helpers.id as helper_id', 'helpers.name');
            }]);

            return response()->json([
                'id' => $mitra->id,
                'name' => $mitra->name,
                'latitude' => $mitra->latitude,
                'longitude' => $mitra->longitude,
                'saldo' => $mitra->saldo,
                'nomor_rekening' => $mitra->nomor_rekening,
                'category' => $mitra->category->name,
                'is_verified' => $mitra->is_verified,
                'helpers' => $mitra->helpers,
            ]);
        } else if ($user->role == 'admin') {
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
                    $mitras->map(
                        function ($mitra) {
                            return [
                                'id' => $mitra->id,
                                'owner_identifier' => $mitra->owner_identifier,
                                'name' => $mitra->name,
                                'saldo' => $mitra->saldo,
                                'latitude' => $mitra->latitude,
                                'longitude' => $mitra->longitude,
                                'category' => $mitra->category->name,
                            ];
                        }
                    ),
                    200
                );
            }

            $mitras = Mitra::all()->makeHidden(['created_at', 'updated_at']);
            return response()->json(
                $mitras->map(
                    function ($mitra) {
                        return [
                            'id' => $mitra->id,
                            'owner_identifier' => $mitra->owner_identifier,
                            'name' => $mitra->name,
                            'saldo' => $mitra->saldo,
                            'latitude' => $mitra->latitude,
                            'longitude' => $mitra->longitude,
                            'category' => $mitra->category->name,
                        ];
                    }
                ),
                200
            );
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
}
