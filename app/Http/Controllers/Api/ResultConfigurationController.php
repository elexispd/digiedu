<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResultConfiguration;
use App\Enums\ClassCategoryEnum;
use App\Http\Requests\BulkResultConfigurationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ResultConfigurationController extends Controller
{
    /**
     * Get result configurations for a specific category or class.
     */
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('manage-results') && !Gate::allows('upload-results') && !Gate::allows('view-results')) {
            abort(403, 'Unauthorized to view result configurations');
        }

        $category = $request->query('category');

        // If class_id is passed, resolve category from SchoolClass
        if (!$category && $classId = $request->query('class_id')) {
            $schoolClass = \App\Models\SchoolClass::find($classId);
            if ($schoolClass) {
                $category = $schoolClass->category instanceof \App\Enums\ClassCategoryEnum 
                    ? $schoolClass->category->value 
                    : $schoolClass->category;
            }
        }
        
        $configs = ResultConfiguration::when($category, function ($query, $category) {
            return $query->where('class_category', $category);
        })->get();

        return response()->json([
            'status' => 'success',
            'data' => $configs
        ]);
    }

    /**
     * Bulk save result configurations for a specific category.
     */
    public function bulkStore(BulkResultConfigurationRequest $request): JsonResponse
    {
        Gate::authorize('manage-results');

        $category = $request->validated('class_category');

        DB::beginTransaction();
        try {
            // Delete old configs for this category
            ResultConfiguration::where('class_category', $category)->delete();

            // Insert new configs
            $configs = collect($request->validated('configurations'))->map(function ($config) use ($category) {
                $config['id'] = (string) \Illuminate\Support\Str::uuid();
                $config['class_category'] = $category;
                $config['created_at'] = now();
                $config['updated_at'] = now();
                return $config;
            })->toArray();

            ResultConfiguration::insert($configs);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Result configurations saved successfully',
                'data' => ResultConfiguration::where('class_category', $category)->get()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save configurations'], 500);
        }
    }
}
