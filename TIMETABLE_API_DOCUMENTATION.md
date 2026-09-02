# Timetable Module API Documentation

This document provides a comprehensive reference of all endpoints, authorization requirements, request payloads, and response payloads for the Timetable Module.

---

## 1. Authentication & Base Headers

All requests require Bearer token authentication via Laravel Sanctum:

`http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
`

### Authorization Rules
* **Admin**: Has full access to all endpoints.
* **Teacher**: Has access to view endpoints (iew-timetable ability) and their own timetable (GET /api/v1/teacher/timetable).
* **Parent / Guardian**: Has access to view their students' timetables (GET /api/v1/guardian/students/{studentId}/timetable).
* **Custom Roles**: Can be granted iew-timetable and/or manage-timetable via Spatie permissions.

---

## 2. Periods Management (Admin)

### 2.1 List All Periods
Retrieves all configured school periods and breaks in chronological order.

* **Method**: GET
* **URL**: /api/v1/timetables/periods
* **Permission**: iew-timetable
* **Response 200 OK**:
`json
{
  status: success,
  data: [
    {
      id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
      name: Period 1,
      period_number: 1,
      start_time: 08:00 AM,
      end_time: 08:45 AM,
      is_break: false,
      order: 1
    },
    {
      id: 01a061d0-c91e-73f5-b9ac-64e5733be966,
      name: Period 2,
      period_number: 2,
      start_time: 08:45 AM,
      end_time: 09:30 AM,
      is_break: false,
      order: 2
    },
    {
      id: 01a061d0-c92d-7003-b130-512afa6598f9,
      name: School Break,
      period_number: null,
      start_time: 12:00 PM,
      end_time: 01:00 PM,
      is_break: true,
      order: 5
    }
  ]
}
`

---

### 2.2 Create a Period
* **Method**: POST
* **URL**: /api/v1/timetables/periods
* **Permission**: manage-timetable
* **Request Payload**:
`json
{
  name: Period 7,
  period_number: 7,
  start_time: 02:00 PM,
  end_time: 02:45 PM,
  is_break: false,
  order: 8
}
`
* **Response 201 Created**:
`json
{
  status: success,
  message: Timetable period created successfully,
  data: {
    id: 01a061e0-7988-7243-982a-923f71c48e89,
    name: Period 7,
    period_number: 7,
    start_time: 02:00 PM,
    end_time: 02:45 PM,
    is_break: false,
    order: 8,
    created_at: 2026-09-02T11:45:00.000000Z,
    updated_at: 2026-09-02T11:45:00.000000Z
  }
}
`

---

### 2.3 Reorder Periods
* **Method**: POST
* **URL**: /api/v1/timetables/periods/reorder
* **Permission**: manage-timetable
* **Request Payload**:
`json
{
  orders: [
    { id: 01a061d0-c90c-7318-b36b-f45ae165af6b, order: 1 },
    { id: 01a061d0-c91e-73f5-b9ac-64e5733be966, order: 2 },
    { id: 01a061d0-c92d-7003-b130-512afa6598f9, order: 3 }
  ]
}
`
* **Response 200 OK**:
`json
{
  status: success,
  message: Periods reordered successfully,
  data: [ ... ]
}
`

---

### 2.4 Update a Period
* **Method**: PUT
* **URL**: /api/v1/timetables/periods/{id}
* **Permission**: manage-timetable
* **Request Payload**:
`json
{
  name: Period 1 (Morning Devotion Included),
  start_time: 07:50 AM,
  end_time: 08:45 AM
}
`
* **Response 200 OK**:
`json
{
  status: success,
  message: Timetable period updated successfully,
  data: {
    id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
    name: Period 1 (Morning Devotion Included),
    period_number: 1,
    start_time: 07:50 AM,
    end_time: 08:45 AM,
    is_break: false,
    order: 1
  }
}
`

---

### 2.5 Delete a Period
* **Method**: DELETE
* **URL**: /api/v1/timetables/periods/{id}
* **Permission**: manage-timetable
* **Response 200 OK**:
`json
{
  status: success,
  message: Timetable period deleted successfully
}
`
* **Error Response 422 Unprocessable Entity (if period contains active slots)**:
`json
{
  status: error,
  message: Cannot delete period 'Period 1' because it currently has 5 scheduled timetable slot(s). Remove or reschedule the slots first.
}
`

---

## 3. Timetable Views

### 3.1 View 1: Timetable by Teacher (Image 1)
Returns the selected teacher's profile, subjects, weekly workload count, and Monday-to-Friday schedule grid.

* **Method**: GET
* **URL**: /api/v1/timetables/by-teacher
* **Query Parameters**:
  * 	eacher_id *(optional, UUID)*: Target teacher. Defaults to the first teacher if not provided.
  * search *(optional, string)*: Filter staff by name.
  * cademic_session_id *(optional, UUID)*: Defaults to active session.
  * 	erm *(optional, int 1-3)*: Defaults to active term.
* **Response 200 OK**:
`json
{
  status: success,
  data: {
    selected_teacher: {
      id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
      name: Adaeze Mmaduka,
      staff_id: STF/2018/042,
      subjects: Literature,
      total_workload: 3
    },
    session: {
      academic_session_id: 019f6cf7-16a4-71ea-b726-b93d79d63b4e,
      term: 1
    },
    periods: [ ... ],
    grid: [
      {
        period_id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
        period_name: Period 1,
        period_number: 1,
        time: 08:00 AM - 08:45 AM,
        is_break: false,
        days: {
          monday: null,
          tuesday: null,
          wednesday: null,
          thursday: null,
          friday: null
        }
      },
      {
        period_id: 01a061d0-c91e-73f5-b9ac-64e5733be966,
        period_name: Period 2,
        period_number: 2,
        time: 08:45 AM - 09:30 AM,
        is_break: false,
        days: {
          monday: {
            id: 01a06200-a111-73f1-a1b2-111122223333,
            class_name: JSS 1 A,
            school_class_id: 019f6ce6-7b8f-7165-8fea-b605c99858d4,
            class_arm_id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
            subject_id: 019f8bed-9954-714f-85d8-0124c77319e6,
            subject_name: Literature,
            room: Room 101,
            session_type: LECTURE
          },
          tuesday: null,
          wednesday: null,
          thursday: null,
          friday: null
        }
      }
    ],
    staff_list: [
      { id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7, name: Adaeze Mmaduka, email: adaeze@school.edu },
      { id: 01a01fd0-f457-715c-917a-ffa68a95ef47, name: Folorunsho Toyin, email: folorunsho@school.edu }
    ]
  }
}
`

---

### 3.2 View 2: Timetable by Class (Image 2)
Returns the schedule grid for a specific class arm (e.g. JSS 1A), including student count and assigned subjects/teachers.

* **Method**: GET
* **URL**: /api/v1/timetables/by-class
* **Query Parameters**:
  * class_arm_id *(optional, UUID)*: Defaults to first class arm if omitted.
  * cademic_session_id *(optional, UUID)*
  * 	erm *(optional, int)*
* **Response 200 OK**:
`json
{
  status: success,
  data: {
    selected_class: {
      id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
      school_class_id: 019f6ce6-7b8f-7165-8fea-b605c99858d4,
      name: JSS 1 A,
      full_title: Junior Secondary School 1A,
      student_count: 32
    },
    session: { academic_session_id: ..., term: 1 },
    periods: [ ... ],
    grid: [
      {
        period_id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
        period_name: Period 1,
        period_number: 1,
        time: 08:00 AM - 08:45 AM,
        is_break: false,
        days: {
          monday: {
            id: 01a06200-...,
            subject_id: 019f8bed-...,
            subject_name: Chemistry,
            teacher_id: 01a01fd0-...,
            teacher_name: Folorunsho Toyin,
            room: Room 101,
            session_type: LECTURE
          },
          tuesday: null,
          wednesday: null,
          thursday: null,
          friday: null
        }
      }
    ],
    class_list: [
      {
        id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
        school_class_id: 019f6ce6-7b8f-7165-8fea-b605c99858d4,
        class_name: JSS 1,
        arm_name: A,
        display_name: JSS 1 A
      }
    ]
  }
}
`

---

### 3.3 View 3: Master Staff Allocation Grid (Image 3)
Matrix view showing all teachers with their assigned periods for Monday through Friday and their total workload count.

* **Method**: GET
* **URL**: /api/v1/timetables/master-grid
* **Query Parameters**:
  * search *(optional, string)*: Filter teachers by name.
  * cademic_session_id *(optional, UUID)*
  * 	erm *(optional, int)*
* **Response 200 OK**:
`json
{
  status: success,
  data: {
    session: { academic_session_id: ..., term: 1 },
    teachers: [
      {
        teacher_id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
        teacher_name: Adewale Funke,
        teacher_email: adewale@school.edu,
        allocations: {
          monday: [
            {
              slot_id: ...,
              period: P1,
              class: JSS1A,
              subject: English Language,
              display: P1 • JSS1A (English Language)
            }
          ],
          tuesday: [],
          wednesday: [
            {
              slot_id: ...,
              period: P1,
              class: SS1A,
              subject: English Language,
              display: P1 • SS1A (English Language)
            }
          ],
          thursday: [
            {
              slot_id: ...,
              period: P1,
              class: JSS1A,
              subject: English Language,
              display: P1 • JSS1A (English Language)
            }
          ],
          friday: []
        },
        total_load: 3,
        total_load_label: 3 Periods
      }
    ]
  }
}
`

---

## 4. Conflict Detection & Slot Assignment (Image 4)

### 4.1 Live Conflict Check (for Modal Validation)
Calls the automated conflict engine to check for collisions as the admin selects dropdown options in the modal before clicking Assign.

* **Method**: POST
* **URL**: /api/v1/timetables/check-conflict
* **Permission**: iew-timetable
* **Request Payload**:
`json
{
  teacher_id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
  class_arm_id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
  timetable_period_id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
  day_of_week: monday,
  subject_id: 019f8bed-9954-714f-85d8-0124c77319e6,
  room: Room 101,
  session_type: lecture,
  exclude_slot_id: null
}
`

* **Response 200 OK (No Conflict)**:
`json
{
  status: success,
  data: {
    has_conflict: false,
    conflict: null
  }
}
`

* **Response 200 OK (Conflict Detected)**:
`json
{
  status: success,
  data: {
    has_conflict: true,
    conflict: {
      type: class_conflict,
      message: Class Conflict: JSS 1 A already has a scheduled class "Mathematics" with Jane Doe on Monday during P1.,
      conflicting_slot: {
        id: 01a06200-...,
        class_arm: { id: ..., name: A, school_class: { id: ..., name: JSS 1 } },
        subject: { id: ..., name: Mathematics },
        teacher: { id: ..., name: Jane Doe },
        period: { id: ..., name: Period 1, period_number: 1 }
      }
    }
  }
}
`

#### Conflict Types:
1. class_conflict: Class Conflict: {Class} already has a scheduled class {Subject} with {Teacher} on {Day} during {Period}.
2. 	eacher_conflict: Teacher Conflict: {Teacher} is already assigned to {Class} for {Subject} on {Day} during {Period}.
3. oom_conflict: Room Conflict: Room {Room} is already occupied by {Class} on {Day} during {Period}.
4. reak_conflict: Scheduling Conflict: {Period} is reserved for school break and cannot be assigned for classes.

---

### 4.2 Assign a Timetable Slot
Assigns a teacher to a class, subject, day, and period with automated conflict detection.

* **Method**: POST
* **URL**: /api/v1/timetables
* **Permission**: manage-timetable
* **Request Payload**:
`json
{
  teacher_id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
  class_arm_id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
  subject_id: 019f8bed-9954-714f-85d8-0124c77319e6,
  timetable_period_id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
  day_of_week: monday,
  room: Room 101,
  session_type: lecture
}
`
* **Response 201 Created**:
`json
{
  status: success,
  message: Timetable slot assigned successfully,
  data: {
    id: 01a06200-7bb2-710a-81a1-123456789abc,
    teacher_id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
    school_class_id: 019f6ce6-7b8f-7165-8fea-b605c99858d4,
    class_arm_id: 019f6ce6-7b94-7213-9c4b-53b21639756f,
    subject_id: 019f8bed-9954-714f-85d8-0124c77319e6,
    timetable_period_id: 01a061d0-c90c-7318-b36b-f45ae165af6b,
    day_of_week: monday,
    session_type: lecture,
    room: Room 101,
    academic_session_id: 019f6cf7-16a4-71ea-b726-b93d79d63b4e,
    term: 1,
    teacher: { id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7, name: Jane Doe, email: jane@school.edu },
    school_class: { id: 019f6ce6-7b8f-7165-8fea-b605c99858d4, name: JSS 1, category: Junior Secondary },
    class_arm: { id: 019f6ce6-7b94-7213-9c4b-53b21639756f, school_class_id: 019f6ce6-7b8f-7165-8fea-b605c99858d4, name: A },
    subject: { id: 019f8bed-9954-714f-85d8-0124c77319e6, name: Mathematics, code: MTH101 },
    period: { id: 01a061d0-c90c-7318-b36b-f45ae165af6b, name: Period 1, period_number: 1, start_time: 08:00 AM, end_time: 08:45 AM, is_break: false, order: 1 }
  }
}
`
* **Response 422 Unprocessable Entity (Conflict Blocked)**:
`json
{
  status: error,
  message: Scheduling conflict detected,
  conflict: {
    type: teacher_conflict,
    message: Teacher Conflict: Jane Doe is already assigned to JSS 1 A for "Mathematics" on Monday during P1.,
    conflicting_slot: { ... }
  }
}
`

---

### 4.3 Get Single Timetable Slot
* **Method**: GET
* **URL**: /api/v1/timetables/{id}
* **Permission**: iew-timetable
* **Response 200 OK**: Returns single slot with relations.

---

### 4.4 Update a Timetable Slot
* **Method**: PUT
* **URL**: /api/v1/timetables/{id}
* **Permission**: manage-timetable
* **Request Payload** (all fields optional):
`json
{
  room: Room 102,
  session_type: practical
}
`
* **Response 200 OK**:
`json
{
  status: success,
  message: Timetable slot updated successfully,
  data: { ... }
}
`

---

### 4.5 Delete a Timetable Slot
* **Method**: DELETE
* **URL**: /api/v1/timetables/{id}
* **Permission**: manage-timetable
* **Response 200 OK**:
`json
{
  status: success,
  message: Timetable slot deleted successfully
}
`

---

## 5. Portal Endpoints

### 5.1 Teacher's Own Timetable
Fetches the logged-in teacher's personal schedule.

* **Method**: GET
* **URL**: /api/v1/teacher/timetable
* **Auth**: Logged in user with role 	eacher
* **Query Parameters**:
  * cademic_session_id *(optional)*
  * 	erm *(optional)*
* **Response 200 OK**:
`json
{
  status: success,
  data: {
    teacher: {
      id: 019f6fb5-abba-7008-bc2c-d6508eaa32e7,
      name: Adaeze Mmaduka
    },
    session: { academic_session_id: ..., term: 1 },
    periods: [ ... ],
    grid: [ ... ]
  }
}
`

---

### 5.2 Guardian / Parent Student Timetable
Fetches the student's class timetable for an authenticated guardian.

* **Method**: GET
* **URL**: /api/v1/guardian/students/{studentId}/timetable
* **Auth**: Logged in user with role parent
* **Query Parameters**:
  * cademic_session_id *(optional)*
  * 	erm *(optional)*
* **Response 200 OK**:
`json
{
  status: success,
  data: {
    student: {
      id: 019f6f...,
      name: Chukwudi Okafor,
      class_name: JSS 1 A
    },
    session: { academic_session_id: ..., term: 1 },
    periods: [ ... ],
    grid: [ ... ]
  }
}
`
