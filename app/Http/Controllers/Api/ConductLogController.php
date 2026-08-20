<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConductLog;
use App\Models\Student;
use App\Http\Requests\StoreConductLogRequest;
use App\Http\Requests\UpdateConductLogRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConductLogController extends Controller
{
    /**
     * Display a listing of all conduct logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ConductLog::with(['student', 'teacher']);

        if ($request->has('type') && in_array($request->type, ['merit', 'discipline'])) {
            $query->where('type', $request->type);
        }

        $logs = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }

    /**
     * Store a newly created conduct log.
     */
    public function store(StoreConductLogRequest $request): JsonResponse
    {
        $log = ConductLog::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Conduct log created successfully',
            'data' => $log->load(['student', 'teacher'])
        ], 201);
    }

    /**
     * Display the specified conduct log.
     */
    public function show(ConductLog $conduct): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $conduct->load(['student', 'teacher'])
        ]);
    }

    /**
     * Update the specified conduct log.
     */
    public function update(UpdateConductLogRequest $request, ConductLog $conduct): JsonResponse
    {
        $conduct->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Conduct log updated successfully',
            'data' => $conduct->load(['student', 'teacher'])
        ]);
    }

    /**
     * Remove the specified conduct log.
     */
    public function destroy(ConductLog $conduct): JsonResponse
    {
        $conduct->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Conduct log deleted successfully'
        ]);
    }

    /**
     * Get a specific student's conduct logs along with summary counts.
     */
    public function studentConducts(Request $request, string $studentId): JsonResponse
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        // Get overall counts for the student regardless of current filter
        $total = ConductLog::where('student_id', $studentId)->count();
        $totalMerit = ConductLog::where('student_id', $studentId)->where('type', 'merit')->count();
        $totalDiscipline = ConductLog::where('student_id', $studentId)->where('type', 'discipline')->count();

        // Query list of conducts
        $query = ConductLog::with('teacher')->where('student_id', $studentId);

        if ($request->has('type') && in_array($request->type, ['merit', 'discipline'])) {
            $query->where('type', $request->type);
        }

        $logs = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => [
                'counts' => [
                    'total' => $total,
                    'merit' => $totalMerit,
                    'discipline' => $totalDiscipline,
                ],
                'logs' => $logs
            ]
        ]);
    }

    /**
     * Get school-wide behavior insights (percentages of merit vs discipline).
     */
    public function insights(): JsonResponse
    {
        $total = ConductLog::count();
        $merits = ConductLog::where('type', 'merit')->count();
        $disciplines = ConductLog::where('type', 'discipline')->count();

        $meritPercentage = $total > 0 ? round(($merits / $total) * 100, 2) : 0;
        $disciplinePercentage = $total > 0 ? round(($disciplines / $total) * 100, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_logs' => $total,
                'positive_percentage' => $meritPercentage,
                'corrective_percentage' => $disciplinePercentage,
                'counts' => [
                    'merit' => $merits,
                    'discipline' => $disciplines
                ]
            ]
        ]);
    }
}
