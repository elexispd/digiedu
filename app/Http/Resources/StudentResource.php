<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'admission_number' => $this->admission_number,
            'photo' => $this->photo,
            'blood_group' => $this->blood_group,
            'genotype' => $this->genotype,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            
            // Academic details
            'academic_session_id' => $this->academic_session_id,
            'term' => $this->term,
            'admission_date' => $this->admission_date,
            
            // The true class category from the schoolClass relation (overrides any stale admission form data)
            'class_category' => $this->schoolClass ? $this->schoolClass->category->value : $this->class_category,
            
            'school_class_id' => $this->school_class_id,
            'class_arm_id' => $this->class_arm_id,
            'student_type' => $this->student_type,
            'house' => $this->house,
            'roll_number' => $this->roll_number,
            'current_status' => $this->current_status,
            
            // System stats
            'attendance_percentage' => (float) $this->attendance_percentage,
            'average_score' => (float) $this->average_score,
            
            // Flatten important user credentials
            'email' => $this->whenLoaded('user', fn() => $this->user->email),
            'account_status' => $this->whenLoaded('user', fn() => $this->user->status),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'guardians' => $this->whenLoaded('guardians'),
            'school_class' => $this->whenLoaded('schoolClass', function () {
                return [
                    'id' => $this->schoolClass->id,
                    'name' => $this->schoolClass->name,
                    'category' => $this->schoolClass->category->value,
                ];
            }),
            'class_arm' => $this->whenLoaded('classArm', function () {
                return [
                    'id' => $this->classArm->id,
                    'name' => $this->classArm->name,
                ];
            }),
            'previous_classes' => $this->formatPreviousClasses(),
            'documents' => $this->whenLoaded('documents')
        ];
    }

    /**
     * Format all of the student's previous classes, terms, and academic sessions.
     */
    protected function formatPreviousClasses(): array
    {
        $results = $this->relationLoaded('termResults')
            ? $this->termResults
            : $this->termResults()
                ->with([
                    'schoolClass:id,name',
                    'academicSession:id,name',
                    'classArm:id,name',
                ])
                ->get();

        if ($results->isEmpty()) {
            return [];
        }

        return $results->map(function ($prev) {
            $termRaw = $prev->term;
            $termName = match (strtolower((string) $termRaw)) {
                '1', 'first', 'first term' => 'First Term',
                '2', 'second', 'second term' => 'Second Term',
                '3', 'third', 'third term' => 'Third Term',
                default => (string) $termRaw,
            };

            $termId = match (strtolower((string) $termRaw)) {
                '1', 'first', 'first term' => '1',
                '2', 'second', 'second term' => '2',
                '3', 'third', 'third term' => '3',
                default => (string) $termRaw,
            };

            return [
                'class' => $prev->schoolClass ? [
                    'id' => $prev->schoolClass->id,
                    'name' => $prev->schoolClass->name,
                ] : null,
                'class_arm' => $prev->classArm ? [
                    'id' => $prev->classArm->id,
                    'name' => $prev->classArm->name,
                ] : null,
                'term' => [
                    'id' => $termId,
                    'name' => $termName,
                ],
                'session' => $prev->academicSession ? [
                    'id' => $prev->academicSession->id,
                    'name' => $prev->academicSession->name,
                ] : null,
            ];
        })->values()->all();
    }
}
