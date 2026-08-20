<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OnboardingProgressController extends Controller
{
    /**
     * Get the current onboarding progress state.
     */
    public function index(): JsonResponse
    {
        // Get or create the tracker (there should only be one)
        $progress = OnboardingProgress::firstOrCreate([], [
            'current_step' => 1,
            'completed_steps' => [],
            'is_completed' => false
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $progress
        ]);
    }

    /**
     * Update the onboarding progress state.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_step' => 'sometimes|integer|min:1',
            'new_completed_step' => 'sometimes|string',
            'is_completed' => 'sometimes|boolean'
        ]);

        $progress = OnboardingProgress::firstOrCreate([], [
            'current_step' => 1,
            'completed_steps' => [],
            'is_completed' => false
        ]);

        $completedSteps = $progress->completed_steps ?? [];
        
        if (isset($validated['new_completed_step']) && !in_array($validated['new_completed_step'], $completedSteps)) {
            $completedSteps[] = $validated['new_completed_step'];
        }

        $progress->update([
            'current_step' => $validated['current_step'] ?? $progress->current_step,
            'completed_steps' => array_values(array_unique($completedSteps)),
            'is_completed' => $validated['is_completed'] ?? $progress->is_completed
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding progress updated',
            'data' => $progress
        ]);
    }
}
