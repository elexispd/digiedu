<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Get primary analytic counts for the dashboard.
     */
    public function analyticCounts(): JsonResponse
    {
        $totalStudents = Student::count();
        $totalGuardians = Guardian::count();
        $totalTeachers = User::role('teacher')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_students' => $totalStudents,
                'total_guardians' => $totalGuardians,
                'total_teachers' => $totalTeachers
            ]
        ]);
    }

    /**
     * Get user status analytics grouped by role (students, guardians, teachers).
     */
    public function userStatusAnalytics(): JsonResponse
    {
        // Students (using current_status)
        $totalStudents = Student::count();
        $studentStatuses = Student::select('current_status as status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('current_status')
            ->get();
            
        $studentsData = $this->formatStatusData($studentStatuses, $totalStudents);

        // Guardians (using status)
        $totalGuardians = Guardian::count();
        $guardianStatuses = Guardian::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
            
        $guardiansData = $this->formatStatusData($guardianStatuses, $totalGuardians);

        // Teachers (using status)
        $totalTeachers = User::role('teacher')->count();
        $teacherStatuses = User::role('teacher')
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
            
        $teachersData = $this->formatStatusData($teacherStatuses, $totalTeachers);

        return response()->json([
            'status' => 'success',
            'data' => [
                'students' => $studentsData,
                'guardians' => $guardiansData,
                'teachers' => $teachersData
            ]
        ]);
    }
    
    /**
     * Helper to format status analytics data with percentages.
     */
    private function formatStatusData($statuses, $total)
    {
        $data = [
            'active' => ['total' => 0, 'percentage' => 0],
            'pending' => ['total' => 0, 'percentage' => 0],
            'suspended' => ['total' => 0, 'percentage' => 0],
        ];
        
        foreach ($statuses as $stat) {
            $statusStr = strtolower($stat->status ?? 'pending');
            
            // Map student's 'draft' to 'pending' if it appears
            if ($statusStr === 'draft') {
                $statusStr = 'pending';
            }
            
            if (!isset($data[$statusStr])) {
                $data[$statusStr] = ['total' => 0, 'percentage' => 0];
            }
            
            $data[$statusStr]['total'] += $stat->total;
        }
        
        // Calculate percentages
        if ($total > 0) {
            foreach ($data as $key => $values) {
                if (is_array($values) && isset($values['total'])) {
                    $data[$key]['percentage'] = round(($values['total'] / $total) * 100, 2);
                }
            }
        }
        
        $data['total'] = $total;
        
        return $data;
    }

    /**
     * Get teacher dashboard analytics.
     */
    public function teacherAnalytics(Request $request): JsonResponse
    {
        Gate::authorize('view-classes');

        $user = $request->user();

        // 1. Total subjects assigned (from subject_assignments pivot)
        $totalSubjects = $user->subjectAssignments()->distinct('subject_id')->count('subject_id');

        // 2. Total classes assigned (unique classes where they teach a subject)
        $assignedClassIds = $user->subjectAssignments()->distinct('school_class_id')->pluck('school_class_id');
        $totalClasses = $assignedClassIds->count();

        // 3. Total students in those assigned classes
        $totalStudents = 0;
        if ($totalClasses > 0) {
            $totalStudents = Student::whereIn('school_class_id', $assignedClassIds)->count();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_class_assigned' => $totalClasses,
                'total_students' => $totalStudents,
                'total_subjects_assigned' => $totalSubjects,
            ]
        ]);
    }
}
