<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\ResultConfiguration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResultsTemplateExport implements FromCollection, WithHeadings
{
    protected $schoolClassId;
    protected $classArmId;
    protected $subjectId;
    protected $classCategory;

    public function __construct($schoolClassId, $classArmId, $subjectId, $classCategory)
    {
        $this->schoolClassId = $schoolClassId;
        $this->classArmId = $classArmId;
        $this->subjectId = $subjectId;
        $this->classCategory = $classCategory;
    }

    public function collection()
    {
        $students = Student::where('school_class_id', $this->schoolClassId)
            ->where('class_arm_id', $this->classArmId)
            // ->where('current_status', 'active') // if you want only active
            ->get();
            
        $configs = ResultConfiguration::where('class_category', $this->classCategory)->get();

        $data = collect();

        foreach ($students as $student) {
            $row = [
                'Admission No' => $student->admission_number,
                'Student Name' => $student->first_name . ' ' . $student->last_name,
            ];
            
            foreach ($configs as $config) {
                $row[$config->assessment_type] = '';
            }
            
            $data->push($row);
        }

        return $data;
    }

    public function headings(): array
    {
        $configs = ResultConfiguration::where('class_category', $this->classCategory)->get();
        $headings = ['Admission No', 'Student Name'];
        
        foreach ($configs as $config) {
            $headings[] = $config->assessment_type;
        }
        
        return $headings;
    }
}
