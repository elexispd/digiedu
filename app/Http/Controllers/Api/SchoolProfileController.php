<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolProfile;
use App\Http\Requests\StoreSchoolProfileRequest;
use App\Http\Requests\UpdateSchoolProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SchoolProfileController extends Controller
{
    /**
     * Get the school profile.
     */
    public function index(): JsonResponse
    {
        $profile = SchoolProfile::first();

        return response()->json([
            'status' => 'success',
            'data' => $profile
        ]);
    }

    /**
     * Store the school profile.
     */
    public function store(StoreSchoolProfileRequest $request): JsonResponse
    {
        if (SchoolProfile::exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'School profile already exists. Use the update endpoint.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $profile = SchoolProfile::create($request->validated());
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'School profile created successfully',
                'data' => $profile
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save profile'], 500);
        }
    }

    /**
     * Update the school profile.
     */
    public function update(UpdateSchoolProfileRequest $request): JsonResponse
    {
        $profile = SchoolProfile::first();

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'School profile not found.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $profile->update($request->validated());
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'School profile updated successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update profile'], 500);
        }
    }
}
