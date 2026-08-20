<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\StudentResult;
use App\Models\StudentResultDetail;
use App\Models\AcademicSetting;
use App\Services\ResultService;
use Illuminate\Support\Facades\DB;

class ResultsImport implements ToCollection
{
    protected $schoolClassId;
    protected $classArmId;
    protected $subjectId;
    protected $classCategory;
    protected $recordedBy;

    public function __construct($schoolClassId, $classArmId, $subjectId, $classCategory, $recordedBy)
    {
        $this->schoolClassId = $schoolClassId;
        $this->classArmId = $classArmId;
        $this->subjectId = $subjectId;
        $this->classCategory = $classCategory;
        $this->recordedBy = $recordedBy;
    }

    public function collection(Collection $rows)
    {
        $headers = $rows->shift(); // Remove and get the first row (headings)
        
        $configIndexes = [];
        $admissionNoIndex = null;

        $configs = \App\Models\ResultConfiguration::where('class_category', $this->classCategory)
            ->pluck('id', 'assessment_type')
            ->toArray();

        $configsPercentage = \App\Models\ResultConfiguration::where('class_category', $this->classCategory)
            ->pluck('percentage', 'id')
            ->toArray();
        
        foreach ($headers as $index => $header) {
            if ($header === 'Admission No') {
                $admissionNoIndex = $index;
            } elseif (array_key_exists($header, $configs)) {
                $configIndexes[$index] = $configs[$header];
            }
        }
        
        if ($admissionNoIndex === null) {
            throw new \Exception('Invalid template: Admission No column missing.');
        }

        $setting = AcademicSetting::with('academicSession')->first();
        if (!$setting || !$setting->academicSession) {
            throw new \Exception('No active academic session found.');
        }

        $resultService = app(ResultService::class);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $admissionNo = $row[$admissionNoIndex] ?? null;
                if (!$admissionNo) continue;

                $student = \App\Models\Student::where('admission_number', $admissionNo)->first();
                if (!$student) continue;

                if ($student->school_class_id !== $this->schoolClassId || $student->class_arm_id !== $this->classArmId) {
                    throw new \Exception("Student with admission {$admissionNo} is not enrolled in this class and arm.");
                }

                $studentId = $student->id;
                
                $result = StudentResult::firstOrCreate(
                    [
                        'academic_session_id' => $setting->academic_session_id,
                        'term' => $setting->academicSession->current_term,
                        'student_id' => $studentId,
                        'subject_id' => $this->subjectId,
                    ],
                    [
                        'school_class_id' => $this->schoolClassId,
                        'class_arm_id' => $this->classArmId,
                        'recorded_by' => $this->recordedBy,
                        'status' => 'draft',
                    ]
                );

                foreach ($configIndexes as $index => $configId) {
                    $score = $row[$index] ?? null;
                    if ($score !== null && trim($score) !== '') {
                        $maxScore = $configsPercentage[$configId] ?? 0;
                        if ((float) $score > $maxScore) {
                            throw new \Exception("Score {$score} for student admission {$admissionNo} exceeds maximum allowed percentage ({$maxScore}) for this assessment.");
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
                }

                $resultService->calculateTotalAndGrade($result, $this->classCategory);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
