<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimetablePeriod;
use App\Http\Requests\StoreTimetablePeriodRequest;
use App\Http\Requests\UpdateTimetablePeriodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimetablePeriodController extends Controller
{
    /**
     * Display a listing of all timetable periods.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('view-timetable');

        $periods = TimetablePeriod::orderBy('order', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $periods,
        ]);
    }

    /**
     * Store a newly created period.
     */
    public function store(StoreTimetablePeriodRequest $request): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $validated = $request->validated();

        if (!isset($validated['order'])) {
            $maxOrder = TimetablePeriod::max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        $period = TimetablePeriod::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable period created successfully',
            'data' => $period,
        ], 201);
    }

    /**
     * Display the specified period.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('view-timetable');

        $period = TimetablePeriod::find($id);

        if (!$period) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable period not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $period,
        ]);
    }

    /**
     * Update the specified period.
     */
    public function update(UpdateTimetablePeriodRequest $request, string $id): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $period = TimetablePeriod::find($id);

        if (!$period) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable period not found',
            ], 404);
        }

        $period->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable period updated successfully',
            'data' => $period,
        ]);
    }

    /**
     * Remove the specified period.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $period = TimetablePeriod::withCount('slots')->find($id);

        if (!$period) {
            return response()->json([
                'status' => 'error',
                'message' => 'Timetable period not found',
            ], 404);
        }

        if ($period->slots_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete period '{$period->name}' because it currently has {$period->slots_count} scheduled timetable slot(s). Remove or reschedule the slots first.",
            ], 422);
        }

        $period->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Timetable period deleted successfully',
        ]);
    }

    /**
     * Reorder periods.
     */
    public function reorder(Request $request): JsonResponse
    {
        Gate::authorize('manage-timetable');

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|uuid|exists:timetable_periods,id',
            'orders.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->orders as $item) {
            TimetablePeriod::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        $periods = TimetablePeriod::orderBy('order', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Periods reordered successfully',
            'data' => $periods,
        ]);
    }
}
