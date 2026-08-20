<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SchoolClass;
use App\Models\Student;

class TeacherPortalController extends Controller
{
    /**
     * Get the teacher's assigned classes with their subjects and counts.
     */
    public function myClasses(Request $request): JsonResponse
    {
        $teacher = $request->user();

        // 1. Get all subject assignments for this teacher, eager loading subject and schoolClass
        $assignments = \App\Models\SubjectAssignment::where('user_id', $teacher->id)
            ->with(['subject:id,name,code', 'schoolClass:id,name,category'])
            ->get();

        // 2. Group the assignments by class
        $classesMap = [];
        foreach ($assignments as $assignment) {
            if (!$assignment->schoolClass || !$assignment->subject) {
                continue;
            }

            $classId = $assignment->school_class_id;
            
            if (!isset($classesMap[$classId])) {
                $classesMap[$classId] = [
                    'id' => $assignment->schoolClass->id,
                    'name' => $assignment->schoolClass->name,
                    'category' => $assignment->schoolClass->category,
                    'subjects' => [],
                ];
            }

            // Avoid duplicate subjects if there happen to be any
            $subjectExists = false;
            foreach ($classesMap[$classId]['subjects'] as $existingSubj) {
                if ($existingSubj['id'] === $assignment->subject->id) {
                    $subjectExists = true;
                    break;
                }
            }

            if (!$subjectExists) {
                $classesMap[$classId]['subjects'][] = [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                    'code' => $assignment->subject->code,
                ];
            }
        }

        $classes = array_values($classesMap);

        // 3. Get total classes
        $totalClasses = count($classes);

        // 4. Get total students in those classes
        $classIds = array_keys($classesMap);
        $totalStudents = count($classIds) > 0 
            ? Student::whereIn('school_class_id', $classIds)->count() 
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'classes' => $classes,
                'counts' => [
                    'total_classes' => $totalClasses,
                    'total_students' => $totalStudents,
                ]
            ]
        ]);
    }
}
