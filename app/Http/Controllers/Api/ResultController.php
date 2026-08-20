<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Services\ResultService;
use App\Models\StudentResult;
use App\Models\StudentResultDetail;
use App\Models\AcademicSetting;
use App\Models\SchoolClass;
use App\Models\SubjectAssignment;
use App\Exports\ResultsTemplateExport;
use App\Imports\ResultsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    protected ResultService $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Download Excel template dynamically based on class category configs.
     */
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
        ]);

        $this->authorizeAccess($request);

        $schoolClass = SchoolClass::findOrFail($request->school_class_id);

        return Excel::download(
            new ResultsTemplateExport(
                $schoolClass->id,
                $request->class_arm_id,
                $request->subject_id,
                $schoolClass->category->value
            ),
            'results_template.xlsx'
        );
    }

    /**
     * Upload single student scores.
     */
    public function uploadSingle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'scores' => 'required|array',
            'scores.*.config_id' => 'required|uuid|exists:result_configurations,id|distinct',
            'scores.*.score' => 'required|numeric|min:0',
        ]);

        $this->authorizeAccess($request);

        $schoolClass = SchoolClass::findOrFail($validated['school_class_id']);
        
        $student = \App\Models\Student::findOrFail($validated['student_id']);
        if ($student->school_class_id !== $validated['school_class_id'] || $student->class_arm_id !== $validated['class_arm_id']) {
            return response()->json(['status' => 'error', 'message' => 'The student is not enrolled in the specified class and arm.'], 400);
        }

        $setting = AcademicSetting::with('academicSession')->first();
        if (!$setting || !$setting->academicSession) {
            return response()->json(['status' => 'error', 'message' => 'No active academic session found.'], 400);
        }

        DB::beginTransaction();
        try {
            $result = StudentResult::firstOrCreate(
                [
                    'academic_session_id' => $setting->academic_session_id,
                    'term' => $setting->academicSession->current_term,
                    'student_id' => $validated['student_id'],
                    'subject_id' => $validated['subject_id'],
                ],
                [
                    'school_class_id' => $validated['school_class_id'],
                    'class_arm_id' => $validated['class_arm_id'],
                    'recorded_by' => $request->user()->id,
                    'status' => 'draft',
                ]
            );

            $configs = \App\Models\ResultConfiguration::where('class_category', $schoolClass->category->value)
                ->pluck('percentage', 'id')
                ->toArray();

            foreach ($validated['scores'] as $scoreData) {
                $configId = $scoreData['config_id'];
                $score = $scoreData['score'];

                if (!isset($configs[$configId])) {
                    return response()->json(['status' => 'error', 'message' => "Invalid configuration ID for this class category."], 400);
                }
                if ($score > $configs[$configId]) {
                    return response()->json(['status' => 'error', 'message' => "Score {$score} exceeds the maximum allowed percentage ({$configs[$configId]}) for this assessment."], 400);
                }

                StudentResultDetail::updateOrCreate(
                    [
                        'student_result_id' => $result->id,
                        'result_configuration_id' => $scoreData['config_id'],
                    ],
                    [
                        'score' => $scoreData['score']
                    ]
                );
            }

            $this->resultService->calculateTotalAndGrade($result, $schoolClass->category->value);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Score saved successfully.',
                'data' => $result->load('details')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save scores: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload bulk manual (JSON grid array).
     */
    public function uploadBulkManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'configs' => 'required|array',
            'configs.*' => 'required|uuid|exists:result_configurations,id|distinct',
            'students' => 'required|array',
            'students.*.student_id' => 'required|uuid|exists:students,id',
            'students.*.scores' => 'required|array',
            'students.*.scores.*' => 'nullable|numeric|min:0',
        ]);

        $this->authorizeAccess($request);

        $schoolClass = SchoolClass::findOrFail($validated['school_class_id']);
        
        $setting = AcademicSetting::with('academicSession')->first();
        if (!$setting || !$setting->academicSession) {
            return response()->json(['status' => 'error', 'message' => 'No active academic session found.'], 400);
        }

        DB::beginTransaction();
        try {
            $configsList = \App\Models\ResultConfiguration::where('class_category', $schoolClass->category->value)
                ->pluck('percentage', 'id')
                ->toArray();

            foreach ($validated['students'] as $studentData) {
                $student = \App\Models\Student::find($studentData['student_id']);
                if (!$student || $student->school_class_id !== $validated['school_class_id'] || $student->class_arm_id !== $validated['class_arm_id']) {
                    return response()->json(['status' => 'error', 'message' => "Student {$studentData['student_id']} is not enrolled in the specified class and arm."], 400);
                }

                $result = StudentResult::firstOrCreate(
                    [
                        'academic_session_id' => $setting->academic_session_id,
                        'term' => $setting->academicSession->current_term,
                        'student_id' => $studentData['student_id'],
                        'subject_id' => $validated['subject_id'],
                    ],
                    [
                        'school_class_id' => $validated['school_class_id'],
                        'class_arm_id' => $validated['class_arm_id'],
                        'recorded_by' => $request->user()->id,
                        'status' => 'draft',
                    ]
                );

                foreach ($validated['configs'] as $index => $configId) {
                    $score = $studentData['scores'][$index] ?? null;

                    if ($score === null || $score === '') {
                        continue;
                    }

                    if (!isset($configsList[$configId])) {
                        return response()->json(['status' => 'error', 'message' => "Invalid configuration ID for this class category."], 400);
                    }
                    if ($score > $configsList[$configId]) {
                        return response()->json(['status' => 'error', 'message' => "Score {$score} exceeds the maximum allowed percentage ({$configsList[$configId]}) for this assessment."], 400);
                    }

                    StudentResultDetail::updateOrCreate(
                        [
                            'student_result_id' => $result->id,
                            'result_configuration_id' => $configId,
                        ],
                        [
                            'score' => (float) $score
                        ]
                    );
                }

                $this->resultService->calculateTotalAndGrade($result, $schoolClass->category->value);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk scores saved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to save bulk scores: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload via Excel.
     */
    public function uploadImport(Request $request): JsonResponse
    {
        $request->validate([
            'school_class_id' => 'required|uuid|exists:school_classes,id',
            'class_arm_id' => 'required|uuid|exists:class_arms,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $this->authorizeAccess($request);

        $schoolClass = SchoolClass::findOrFail($request->school_class_id);

        try {
            Excel::import(
                new ResultsImport(
                    $schoolClass->id,
                    $request->class_arm_id,
                    $request->subject_id,
                    $schoolClass->category->value,
                    $request->user()->id
                ), 
                $request->file('file')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Excel results imported successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to import: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Compute Rankings.
     */
    public function computeRanking(Request $request, \App\Services\RankingService $rankingService): JsonResponse
    {
        // Require specific permission to run massive school-wide calculations
        Gate::authorize('manage-results');

        try {
            $rankingService->compute();

            return response()->json([
                'status' => 'success',
                'message' => 'Computation and ranking completed.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to compute rankings: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ensure the user has permission to upload scores for this class/subject.
     */
    private function authorizeAccess(Request $request)
    {
        // First enforce the global permission
        Gate::authorize('upload-results');

        $user = $request->user();
        if ($user->hasRole('admin')) {
            return true;
        }

        // For teachers, verify SubjectAssignment
        $assigned = SubjectAssignment::where('user_id', $user->id)
            ->where('subject_id', $request->subject_id)
            ->where('school_class_id', $request->school_class_id)
            ->where('class_arm_id', $request->class_arm_id)
            ->exists();

        if (!$assigned) {
            throw ValidationException::withMessages([
                'subject_id' => 'You are not assigned to this subject and class arm.'
            ]);
        }
    }
}
