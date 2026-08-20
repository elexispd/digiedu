<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GradeRule;
use App\Enums\ClassCategoryEnum;
use App\Http\Requests\BulkGradeRuleRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GradeRuleController extends Controller
{
    /**
     * Get grade rules for a specific category.
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        
        $rules = GradeRule::when($category, function ($query, $category) {
            return $query->where('class_category', $category);
        })->orderBy('min_score', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $rules
        ]);
    }

    /**
     * Bulk save grade rules for a specific category.
     */
    public function bulkStore(BulkGradeRuleRequest $request): JsonResponse
    {
        $category = $request->validated('class_category');

        DB::beginTransaction();
        try {
            // Delete old rules for this category
            GradeRule::where('class_category', $category)->delete();

            // Insert new rules
            $rules = collect($request->validated('rules'))->map(function ($rule) use ($category) {
                $rule['id'] = (string) \Illuminate\Support\Str::uuid();
                $rule['class_category'] = $category;
                $rule['created_at'] = now();
                $rule['updated_at'] = now();
                return $rule;
            })->toArray();

            GradeRule::insert($rules);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Grade rules saved successfully',
                'data' => GradeRule::where('class_category', $category)->orderBy('min_score', 'desc')->get()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save rules'], 500);
        }
    }

    /**
     * Delete a single grade rule.
     */
    public function destroy(string $id): JsonResponse
    {
        $rule = GradeRule::find($id);

        if (!$rule) {
            return response()->json(['status' => 'error', 'message' => 'Grade rule not found'], 404);
        }

        $rule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Grade rule deleted successfully'
        ]);
    }
}
