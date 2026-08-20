<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSetting;
use App\Http\Requests\UpdateAcademicSettingRequest;
use Illuminate\Http\JsonResponse;

class AcademicSettingController extends Controller
{
    /**
     * Get the global academic settings.
     */
    public function index(): JsonResponse
    {
        $setting = AcademicSetting::with('academicSession')->first();

        return response()->json([
            'status' => 'success',
            'data' => $setting
        ]);
    }

    /**
     * Update the academic settings.
     */
    public function update(UpdateAcademicSettingRequest $request): JsonResponse
    {
        $setting = AcademicSetting::first();

        if (!$setting) {
            $setting = AcademicSetting::create($request->validated());
        } else {
            $setting->update($request->validated());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Academic settings updated successfully',
            'data' => $setting->load('academicSession')
        ]);
    }
}
