<?php

namespace App\Services;

use App\Models\GradeRule;
use App\Models\StudentResult;

class ResultService
{
    /**
     * Calculate total score and determine grade & remark.
     * 
     * @param StudentResult $studentResult
     * @param string $classCategory
     */
    public function calculateTotalAndGrade(StudentResult $studentResult, string $classCategory): void
    {
        // Calculate total score
        $totalScore = $studentResult->details()->sum('score');
        
        $studentResult->total_score = $totalScore;

        // Fetch Grade Rule
        $gradeRule = GradeRule::where('class_category', $classCategory)
            ->where('min_score', '<=', $totalScore)
            ->where('max_score', '>=', $totalScore)
            ->first();

        if ($gradeRule) {
            $studentResult->grade = $gradeRule->grade;
            $studentResult->remark = $gradeRule->remark;
        } else {
            $studentResult->grade = null;
            $studentResult->remark = null;
        }

        $studentResult->save();
    }
}
