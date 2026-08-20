<?php

namespace App\Services;

use App\Models\StudentResult;
use App\Models\StudentTermResult;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\AcademicSetting;
use App\Services\ResultService;

class RankingService
{
    protected ResultService $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Computes the rankings for all results in the current term.
     */
    public function compute(): void
    {
        $setting = AcademicSetting::with('academicSession')->first();
        if (!$setting || !$setting->academicSession) {
            throw new \Exception('No active academic session found.');
        }
        
        $sessionId = $setting->academic_session_id;
        $term = $setting->academicSession->current_term;

        DB::beginTransaction();
        try {
            // 1. Calculate subject positions
            // Group by school_class_id, class_arm_id, subject_id
            $subjects = StudentResult::where('academic_session_id', $sessionId)
                ->where('term', $term)
                ->select('school_class_id', 'class_arm_id', 'subject_id')
                ->distinct()
                ->get();

            // Cache class categories to avoid N+1 queries
            $classCategories = SchoolClass::pluck('category', 'id')->toArray();

            foreach ($subjects as $subjectGroup) {
                $results = StudentResult::with('details')->where('academic_session_id', $sessionId)
                    ->where('term', $term)
                    ->where('school_class_id', $subjectGroup->school_class_id)
                    ->where('class_arm_id', $subjectGroup->class_arm_id)
                    ->where('subject_id', $subjectGroup->subject_id)
                    ->orderByDesc('total_score')
                    ->get();

                $classCategory = $classCategories[$subjectGroup->school_class_id]->value ?? 'Primary';

                foreach ($results as $result) {
                    $this->resultService->calculateTotalAndGrade($result, $classCategory);
                }

                // Re-sort results by the newly calculated total_score just in case it changed
                $results = $results->sortByDesc('total_score')->values();

                $rank = 1;
                $prevScore = null;
                $displayRank = 1;

                foreach ($results as $result) {
                    if ($prevScore !== null && $prevScore == $result->total_score) {
                        // same rank
                    } else {
                        $displayRank = $rank;
                    }
                    $result->subject_position = $displayRank;
                    $result->status = 'published';
                    $result->save();
                    
                    $prevScore = $result->total_score;
                    $rank++;
                }
            }

            // 2. Aggregate into StudentTermResult
            $students = StudentResult::where('academic_session_id', $sessionId)
                ->where('term', $term)
                ->select('school_class_id', 'class_arm_id', 'student_id')
                ->distinct()
                ->get();

            foreach ($students as $studentData) {
                $studentResults = StudentResult::where('academic_session_id', $sessionId)
                    ->where('term', $term)
                    ->where('student_id', $studentData->student_id)
                    ->get();

                $totalScore = $studentResults->sum('total_score');
                $subjectCount = $studentResults->count();
                $average = $subjectCount > 0 ? $totalScore / $subjectCount : 0;

                StudentTermResult::updateOrCreate(
                    [
                        'academic_session_id' => $sessionId,
                        'term' => $term,
                        'student_id' => $studentData->student_id,
                    ],
                    [
                        'school_class_id' => $studentData->school_class_id,
                        'class_arm_id' => $studentData->class_arm_id,
                        'total_score' => $totalScore,
                        'average_score' => $average,
                        'status' => 'published'
                    ]
                );
            }

            // 3. Calculate Class Arm Positions
            $classArms = StudentTermResult::where('academic_session_id', $sessionId)
                ->where('term', $term)
                ->select('school_class_id', 'class_arm_id')
                ->distinct()
                ->get();

            foreach ($classArms as $arm) {
                $termResults = StudentTermResult::where('academic_session_id', $sessionId)
                    ->where('term', $term)
                    ->where('school_class_id', $arm->school_class_id)
                    ->where('class_arm_id', $arm->class_arm_id)
                    ->orderByDesc('average_score')
                    ->get();

                $rank = 1;
                $prevScore = null;
                $displayRank = 1;

                foreach ($termResults as $tr) {
                    if ($prevScore !== null && $prevScore == $tr->average_score) {
                        // same rank
                    } else {
                        $displayRank = $rank;
                    }
                    $tr->class_arm_position = $displayRank;
                    $tr->save();

                    $prevScore = $tr->average_score;
                    $rank++;
                }
            }

            // 4. Calculate Overall Class Positions
            $schoolClasses = StudentTermResult::where('academic_session_id', $sessionId)
                ->where('term', $term)
                ->select('school_class_id')
                ->distinct()
                ->get();

            foreach ($schoolClasses as $sClass) {
                $termResults = StudentTermResult::where('academic_session_id', $sessionId)
                    ->where('term', $term)
                    ->where('school_class_id', $sClass->school_class_id)
                    ->orderByDesc('average_score')
                    ->get();

                $rank = 1;
                $prevScore = null;
                $displayRank = 1;

                foreach ($termResults as $tr) {
                    if ($prevScore !== null && $prevScore == $tr->average_score) {
                        // same rank
                    } else {
                        $displayRank = $rank;
                    }
                    $tr->overall_class_position = $displayRank;
                    $tr->save();

                    $prevScore = $tr->average_score;
                    $rank++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
