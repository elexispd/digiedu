<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\ClassArm;
use App\Models\Subject;
use App\Models\AcademicSession;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use Laravel\Sanctum\Sanctum;

class TimetableTest extends TestCase
{
    protected User $admin;
    protected User $teacher;
    protected User $teacher2;
    protected SchoolClass $schoolClass;
    protected ClassArm $classArm;
    protected ClassArm $classArm2;
    protected Subject $subject;
    protected Subject $subject2;
    protected TimetablePeriod $period1;
    protected TimetablePeriod $period2;
    protected TimetablePeriod $breakPeriod;
    protected AcademicSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = AcademicSession::firstOrCreate(
            ['name' => '2025/2026'],
            ['is_current' => true, 'current_term' => 1]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.test@school.edu'],
            ['name' => 'Admin Tester', 'password' => bcrypt('password')]
        );
        if (!$this->admin->hasRole('admin')) {
            $this->admin->assignRole('admin');
        }

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher1.test@school.edu'],
            ['name' => 'Adaeze Mmaduka', 'password' => bcrypt('password')]
        );
        if (!$this->teacher->hasRole('teacher')) {
            $this->teacher->assignRole('teacher');
        }

        $this->teacher2 = User::firstOrCreate(
            ['email' => 'teacher2.test@school.edu'],
            ['name' => 'Folorunsho Toyin', 'password' => bcrypt('password')]
        );
        if (!$this->teacher2->hasRole('teacher')) {
            $this->teacher2->assignRole('teacher');
        }

        $this->schoolClass = SchoolClass::firstOrCreate(
            ['name' => 'JSS 1'],
            ['category' => 'Junior Secondary']
        );

        $this->classArm = ClassArm::firstOrCreate(
            ['school_class_id' => $this->schoolClass->id, 'name' => 'A'],
            ['class_capacity' => 40]
        );

        $this->classArm2 = ClassArm::firstOrCreate(
            ['school_class_id' => $this->schoolClass->id, 'name' => 'B'],
            ['class_capacity' => 40]
        );

        $this->subject = Subject::firstOrCreate(
            ['name' => 'Literature'],
            ['code' => 'LIT101', 'department' => 'Arts', 'weekly_class_hours' => 3]
        );

        $this->subject2 = Subject::firstOrCreate(
            ['name' => 'Chemistry'],
            ['code' => 'CHM101', 'department' => 'Sciences', 'weekly_class_hours' => 3]
        );

        $this->period1 = TimetablePeriod::firstOrCreate(
            ['name' => 'Period 1'],
            ['period_number' => 1, 'start_time' => '08:00 AM', 'end_time' => '08:45 AM', 'is_break' => false, 'order' => 1]
        );

        $this->period2 = TimetablePeriod::firstOrCreate(
            ['name' => 'Period 2'],
            ['period_number' => 2, 'start_time' => '08:45 AM', 'end_time' => '09:30 AM', 'is_break' => false, 'order' => 2]
        );

        $this->breakPeriod = TimetablePeriod::firstOrCreate(
            ['name' => 'School Break'],
            ['period_number' => null, 'start_time' => '12:00 PM', 'end_time' => '01:00 PM', 'is_break' => true, 'order' => 5]
        );
    }

    public function test_admin_can_manage_periods(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/timetables/periods');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => [['id', 'name', 'start_time', 'end_time', 'is_break']]]);

        $createRes = $this->postJson('/api/v1/timetables/periods', [
            'name' => 'Period 7 Test',
            'period_number' => 7,
            'start_time' => '02:00 PM',
            'end_time' => '02:45 PM',
            'is_break' => false,
        ]);
        $createRes->assertStatus(201);
        $periodId = $createRes->json('data.id');

        $updateRes = $this->putJson('/api/v1/timetables/periods/' . $periodId, [
            'name' => 'Period 7 Updated',
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Period 7 Updated');

        $deleteRes = $this->deleteJson('/api/v1/timetables/periods/' . $periodId);
        $deleteRes->assertStatus(200);
    }

    public function test_slot_creation_and_conflict_detection(): void
    {
        Sanctum::actingAs($this->admin);

        TimetableSlot::where('timetable_period_id', $this->period1->id)
            ->where('day_of_week', 'monday')
            ->delete();

        $res = $this->postJson('/api/v1/timetables', [
            'teacher_id' => $this->teacher->id,
            'class_arm_id' => $this->classArm->id,
            'subject_id' => $this->subject->id,
            'timetable_period_id' => $this->period1->id,
            'day_of_week' => 'monday',
            'room' => 'Room 101',
            'session_type' => 'lecture',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.room', 'Room 101');

        $slotId = $res->json('data.id');

        $classConflictRes = $this->postJson('/api/v1/timetables', [
            'teacher_id' => $this->teacher2->id,
            'class_arm_id' => $this->classArm->id,
            'subject_id' => $this->subject2->id,
            'timetable_period_id' => $this->period1->id,
            'day_of_week' => 'monday',
        ]);

        $classConflictRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('conflict.type', 'class_conflict');

        $teacherConflictRes = $this->postJson('/api/v1/timetables', [
            'teacher_id' => $this->teacher->id,
            'class_arm_id' => $this->classArm2->id,
            'subject_id' => $this->subject->id,
            'timetable_period_id' => $this->period1->id,
            'day_of_week' => 'monday',
        ]);

        $teacherConflictRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('conflict.type', 'teacher_conflict');

        $roomConflictRes = $this->postJson('/api/v1/timetables', [
            'teacher_id' => $this->teacher2->id,
            'class_arm_id' => $this->classArm2->id,
            'subject_id' => $this->subject2->id,
            'timetable_period_id' => $this->period1->id,
            'day_of_week' => 'monday',
            'room' => 'Room 101',
        ]);

        $roomConflictRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('conflict.type', 'room_conflict');

        $checkRes = $this->postJson('/api/v1/timetables/check-conflict', [
            'teacher_id' => $this->teacher->id,
            'class_arm_id' => $this->classArm2->id,
            'timetable_period_id' => $this->period1->id,
            'day_of_week' => 'monday',
        ]);

        $checkRes->assertStatus(200)
            ->assertJsonPath('data.has_conflict', true);

        TimetableSlot::where('id', $slotId)->delete();
    }

    public function test_timetable_views_and_grids(): void
    {
        Sanctum::actingAs($this->admin);

        $byTeacherRes = $this->getJson('/api/v1/timetables/by-teacher?teacher_id=' . $this->teacher->id);
        $byTeacherRes->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'selected_teacher' => ['id', 'name', 'staff_id', 'subjects', 'total_workload'],
                    'periods',
                    'grid',
                    'staff_list',
                ],
            ]);

        $byClassRes = $this->getJson('/api/v1/timetables/by-class?class_arm_id=' . $this->classArm->id);
        $byClassRes->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'selected_class' => ['id', 'name', 'full_title', 'student_count'],
                    'periods',
                    'grid',
                    'class_list',
                ],
            ]);

        $masterRes = $this->getJson('/api/v1/timetables/master-grid');
        $masterRes->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'teachers' => [
                        '*' => ['teacher_id', 'teacher_name', 'allocations', 'total_load', 'total_load_label'],
                    ],
                ],
            ]);
    }

    public function test_teacher_can_view_own_timetable(): void
    {
        Sanctum::actingAs($this->teacher);

        $res = $this->getJson('/api/v1/teacher/timetable');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'teacher' => ['id', 'name'],
                    'periods',
                    'grid',
                ],
            ]);
    }
}
