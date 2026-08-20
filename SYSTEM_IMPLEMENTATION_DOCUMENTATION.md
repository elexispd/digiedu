# Digiedu Backend System Implementation Documentation

This document presents a comprehensive, complete, and un-truncated reference of all endpoints, models, middleware, services, and feature modules implemented in the **Digiedu** school management system.

---

## 1. System Architecture & Tech Stack

- **Framework**: Laravel RESTful API Engine
- **Authentication**: Laravel Sanctum (Bearer Tokens)
- **Role & Permission Management**: Spatie Laravel-Permission (`admin`, `teacher`, `parent`, `student`)
- **Imports & File Processing**: Maatwebsite Excel Processor (`GuardiansImport`, `StudentsImport`)
- **Messaging & Notifications**: Unified Database-backed Realtime Messaging System & Transactional Mails/SMS (`SmsService`, `ActivationOtpMail`, `ResetPasswordOtpMail`)
- **Middlewares**: `auth:sanctum`, `role:{role}`, `active` (`EnsureUserIsActive`), `verified` (`EnsureEmailIsVerified`)

---

## 2. Core Data Models

| Model Class | Database Table | Key Attributes & Description |
| :--- | :--- | :--- |
| [User](file:///c:/laragon/www/digiedu/app/Models/User.php) | `users` | Core user entity (Admins, Teachers). Associated with Spatie roles and subject assignments. |
| [Role](file:///c:/laragon/www/digiedu/app/Models/Role.php) | `roles` | System roles (Admin, Teacher, Parent/Guardian, Student). |
| [Permission](file:///c:/laragon/www/digiedu/app/Models/Permission.php) | `permissions` | Fine-grained access permissions assigned to roles. |
| [SchoolProfile](file:///c:/laragon/www/digiedu/app/Models/SchoolProfile.php) | `school_profiles` | School identity, logo, address, contact info, and branding. |
| [AcademicSession](file:///c:/laragon/www/digiedu/app/Models/AcademicSession.php) | `academic_sessions` | Academic years (e.g. 2025/2026) and current active session toggle. |
| [AcademicSetting](file:///c:/laragon/www/digiedu/app/Models/AcademicSetting.php) | `academic_settings` | School-wide configurations (active session, current term, pass mark). |
| [GradeRule](file:///c:/laragon/www/digiedu/app/Models/GradeRule.php) | `grade_rules` | Grading boundaries (e.g. 70-100 = 'A', Excellent). |
| [ResultConfiguration](file:///c:/laragon/www/digiedu/app/Models/ResultConfiguration.php) | `result_configurations` | Continuous Assessment (CA) and Exam weightage configurations. |
| [OnboardingProgress](file:///c:/laragon/www/digiedu/app/Models/OnboardingProgress.php) | `onboarding_progresses` | Tracks completed setup steps for school administration. |
| [SchoolClass](file:///c:/laragon/www/digiedu/app/Models/SchoolClass.php) | `school_classes` | Class grade levels categorized by Nursery, Primary, Junior, Senior Secondary. |
| [ClassArm](file:///c:/laragon/www/digiedu/app/Models/ClassArm.php) | `class_arms` | Class subdivisions/sections (e.g. JSS1 A, JSS1 Gold). |
| [Subject](file:///c:/laragon/www/digiedu/app/Models/Subject.php) | `subjects` | Academic subjects (Maths, English, Science) with unique codes. |
| [SubjectAssignment](file:///c:/laragon/www/digiedu/app/Models/SubjectAssignment.php) | `subject_assignments` | Teacher-to-Subject-to-Class allocation pivot. |
| [Student](file:///c:/laragon/www/digiedu/app/Models/Student.php) | `students` | Student profile, admission details, class placement, status (`active`, `pending`, `draft`, `suspended`). |
| [StudentDocument](file:///c:/laragon/www/digiedu/app/Models/StudentDocument.php) | `student_documents` | Verification documents uploaded during student onboarding. |
| [Guardian](file:///c:/laragon/www/digiedu/app/Models/Guardian.php) | `guardians` | Parent/Guardian profiles, contact info, activation status, and linked wards. |
| [StudentAttendance](file:///c:/laragon/www/digiedu/app/Models/StudentAttendance.php) | `student_attendances` | Daily/session presence tracking (`present`, `absent`, `late`, `excused`). |
| [ConductLog](file:///c:/laragon/www/digiedu/app/Models/ConductLog.php) | `conduct_logs` | Behavioral records, commendations, warnings, and disciplinary points. |
| [ExtracurricularActivity](file:///c:/laragon/www/digiedu/app/Models/ExtracurricularActivity.php) | `extracurricular_activities` | Clubs, sports, and student enrollment rosters. |
| [Announcement](file:///c:/laragon/www/digiedu/app/Models/Announcement.php) | `announcements` | School-wide broadcast announcements targeted by role. |
| [Conversation](file:///c:/laragon/www/digiedu/app/Models/Conversation.php) | `conversations` | Messaging threads connecting users/guardians. |
| [Message](file:///c:/laragon/www/digiedu/app/Models/Message.php) | `messages` | Direct chat messages, timestamps, and read indicators. |
| [StudentResult](file:///c:/laragon/www/digiedu/app/Models/StudentResult.php) | `student_results` | Aggregated termly scores, total, grade, and subject rank for a specific subject. |
| [StudentResultDetail](file:///c:/laragon/www/digiedu/app/Models/StudentResultDetail.php) | `student_result_details` | Dynamic score breakdown mapping to CA/Exam configurations. |
| [StudentTermResult](file:///c:/laragon/www/digiedu/app/Models/StudentTermResult.php) | `student_term_results` | Overall report card aggregates and class positions for the term. |

---

## 3. Complete API Endpoints Reference

### 🔐 Authentication & Profile (`/api/v1/auth`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Guest | [LoginController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/LoginController.php) | Authenticate user & issue Sanctum token |
| `POST` | `/api/v1/auth/forgot-password` | Guest | [ForgotPasswordController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/ForgotPasswordController.php) | Dispatch password reset OTP email/SMS |
| `POST` | `/api/v1/auth/reset-password` | Guest | [ResetPasswordController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/ResetPasswordController.php) | Reset account password using OTP/token |
| `GET` | `/api/v1/auth/profile` | `auth:sanctum` | [ProfileController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/ProfileController.php) | Retrieve authenticated user profile |
| `PUT` | `/api/v1/auth/profile` | `auth:sanctum` | [ProfileController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/ProfileController.php) | Update user profile details |
| `PUT` | `/api/v1/auth/profile/password` | `auth:sanctum` | [ProfileController@changePassword](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/Auth/ProfileController.php) | Update account password |

---

### 🛡️ System Roles & Permissions (`/api/v1/roles`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/roles` | `auth:sanctum`, `role:admin` | [RolesController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RolesController.php) | List all roles with attached permissions |
| `POST` | `/api/v1/roles` | `auth:sanctum`, `role:admin` | [RolesController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RolesController.php) | Create new custom role |
| `GET` | `/api/v1/roles/{id}` | `auth:sanctum`, `role:admin` | [RolesController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RolesController.php) | View specific role details |
| `PUT` | `/api/v1/roles/{id}` | `auth:sanctum`, `role:admin` | [RolesController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RolesController.php) | Update role & sync permissions |
| `DELETE` | `/api/v1/roles/{id}` | `auth:sanctum`, `role:admin` | [RolesController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RolesController.php) | Delete role |

---

### 🏫 Classes & Class Arms (`/api/v1/classes`, `/api/v1/arms`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/classes/categories` | `auth:sanctum`, `role:admin` | [SchoolClassController@getCategories](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | Get available class categories (`ClassCategoryEnum`) |
| `GET` | `/api/v1/classes` | `auth:sanctum`, `role:admin` | [SchoolClassController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | List all school classes with arms |
| `POST` | `/api/v1/classes` | `auth:sanctum`, `role:admin` | [SchoolClassController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | Create new class |
| `GET` | `/api/v1/classes/{id}` | `auth:sanctum`, `role:admin` | [SchoolClassController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | View single class details |
| `PUT` | `/api/v1/classes/{id}` | `auth:sanctum`, `role:admin` | [SchoolClassController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | Update class details |
| `DELETE` | `/api/v1/classes/{id}` | `auth:sanctum`, `role:admin` | [SchoolClassController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolClassController.php) | Delete class |
| `POST` | `/api/v1/classes/{classId}/arms` | `auth:sanctum`, `role:admin` | [ClassArmController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ClassArmController.php) | Add arm/section to class |
| `PUT` | `/api/v1/arms/{id}` | `auth:sanctum`, `role:admin` | [ClassArmController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ClassArmController.php) | Update class arm details |
| `DELETE` | `/api/v1/arms/{id}` | `auth:sanctum`, `role:admin` | [ClassArmController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ClassArmController.php) | Remove class arm |

---

### 📅 Academic Sessions & Terms (`/api/v1/sessions`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/sessions/terms` | `auth:sanctum`, `role:admin` | [AcademicSessionController@getTerms](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | List valid term enums (`TermEnum`) |
| `GET` | `/api/v1/sessions` | `auth:sanctum`, `role:admin` | [AcademicSessionController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | List all academic sessions |
| `POST` | `/api/v1/sessions` | `auth:sanctum`, `role:admin` | [AcademicSessionController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | Create new academic session |
| `PUT` | `/api/v1/sessions/{id}` | `auth:sanctum`, `role:admin` | [AcademicSessionController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | Update session details |
| `DELETE` | `/api/v1/sessions/{id}` | `auth:sanctum`, `role:admin` | [AcademicSessionController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | Delete session |
| `POST` | `/api/v1/sessions/{id}/set-current` | `auth:sanctum`, `role:admin` | [AcademicSessionController@setCurrent](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSessionController.php) | Set active academic session |

---

### ⚙️ Onboarding & School Settings
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/school-profile` | `auth:sanctum`, `role:admin` | [SchoolProfileController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolProfileController.php) | Get school profile info |
| `POST` | `/api/v1/school-profile` | `auth:sanctum`, `role:admin` | [SchoolProfileController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolProfileController.php) | Save initial school profile |
| `PUT` | `/api/v1/school-profile` | `auth:sanctum`, `role:admin` | [SchoolProfileController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SchoolProfileController.php) | Update school profile |
| `GET` | `/api/v1/onboarding-progress` | `auth:sanctum`, `role:admin` | [OnboardingProgressController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/OnboardingProgressController.php) | Track onboarding step completion |
| `PUT` | `/api/v1/onboarding-progress` | `auth:sanctum`, `role:admin` | [OnboardingProgressController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/OnboardingProgressController.php) | Mark onboarding steps completed |
| `GET` | `/api/v1/academic-settings` | `auth:sanctum`, `role:admin` | [AcademicSettingController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSettingController.php) | Fetch academic configurations |
| `PUT` | `/api/v1/academic-settings` | `auth:sanctum`, `role:admin` | [AcademicSettingController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AcademicSettingController.php) | Update current term & pass score |
| `GET` | `/api/v1/grade-rules` | `auth:sanctum`, `role:admin` | [GradeRuleController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GradeRuleController.php) | List grading rules |
| `POST` | `/api/v1/grade-rules/bulk` | `auth:sanctum`, `role:admin` | [GradeRuleController@bulkStore](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GradeRuleController.php) | Save grade scale definitions in bulk |
| `DELETE` | `/api/v1/grade-rules/{id}` | `auth:sanctum`, `role:admin` | [GradeRuleController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GradeRuleController.php) | Delete grade rule |
| `GET` | `/api/v1/result-configurations` | `auth:sanctum`, `role:admin` | [ResultConfigurationController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultConfigurationController.php) | Fetch result weightage breakdown |
| `POST` | `/api/v1/result-configurations/bulk` | `auth:sanctum`, `role:admin` | [ResultConfigurationController@bulkStore](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultConfigurationController.php) | Save CA & Exam weightage setup |

---

### 👨‍💼 Administrator Account Management (`/api/v1/administrators`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/administrators` | `auth:sanctum`, `role:admin` | [AdministratorController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AdministratorController.php) | List administrative staff |
| `POST` | `/api/v1/administrators` | `auth:sanctum`, `role:admin` | [AdministratorController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AdministratorController.php) | Single admin account creation |
| `POST` | `/api/v1/administrators/bulk` | `auth:sanctum`, `role:admin` | [AdministratorController@bulkStore](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AdministratorController.php) | Bulk admin provisioning |
| `PUT` | `/api/v1/administrators/{id}` | `auth:sanctum`, `role:admin` | [AdministratorController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AdministratorController.php) | Update administrator profile |
| `DELETE` | `/api/v1/administrators/{id}` | `auth:sanctum`, `role:admin` | [AdministratorController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AdministratorController.php) | Remove administrator account |

---

### 👨‍👩‍👧 Guardian Management & Parent Portal (`/api/v1/guardians`, `/api/v1/guardian`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/guardians/search` | `auth:sanctum`, `role:admin` | [GuardianController@search](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Live search guardians by name/phone |
| `POST` | `/api/v1/guardians/import` | `auth:sanctum`, `role:admin` | [GuardianController@import](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Excel batch import guardians (`GuardiansImport`) |
| `POST` | `/api/v1/guardians/{id}/students` | `auth:sanctum`, `role:admin` | [GuardianController@assignStudents](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Link student wards to guardian |
| `POST` | `/api/v1/guardians/{id}/send-reset-pin` | `auth:sanctum`, `role:admin` | [GuardianController@sendResetPin](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Send activation/reset PIN |
| `POST` | `/api/v1/guardians/{id}/reset-password` | `auth:sanctum`, `role:admin` | [GuardianController@resetPassword](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Reset guardian password directly |
| `GET/POST/PUT/DELETE` | `/api/v1/guardians` | `auth:sanctum`, `role:admin` | [GuardianController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianController.php) | Standard Guardian CRUD endpoints |
| `POST` | `/api/v1/guardian/activation/send-code` | `auth:sanctum`, `role:parent` | [GuardianPortalController@sendActivationCode](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Send activation OTP email/SMS |
| `POST` | `/api/v1/guardian/activation/verify` | `auth:sanctum`, `role:parent` | [GuardianPortalController@verifyActivationCode](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Verify activation OTP & activate guardian |
| `PUT` | `/api/v1/guardian/preferences` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@updatePreferences](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Update notification preferences |
| `GET` | `/api/v1/guardian/my-students` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@myStudents](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Get list of linked wards |
| `GET` | `/api/v1/guardian/students/{student}/dashboard` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@studentDashboard](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Overview summary for specific ward |
| `GET` | `/api/v1/guardian/students/{student}/behaviors` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@studentBehaviors](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Conduct & behavior history for ward |
| `GET` | `/api/v1/guardian/students/{student}/performance` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@studentAcademicPerformance](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Academic scores & subject performance |
| `GET` | `/api/v1/guardian/students/{student}/attendance` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@studentAttendance](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Detailed attendance logs for ward |
| `GET` | `/api/v1/guardian/announcements` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@announcements](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Get parent-targeted announcements |
| `PUT` | `/api/v1/guardian/announcements/read-all` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@markAllAnnouncementsRead](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Mark all announcements as read |
| `PUT` | `/api/v1/guardian/announcements/{id}/read` | `auth:sanctum`, `role:parent`, `active` | [GuardianPortalController@markAnnouncementRead](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/GuardianPortalController.php) | Mark single announcement read |

---

### 📚 Subjects & Teacher Allocations (`/api/v1/subjects`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/subjects/search` | `auth:sanctum`, `role:admin` | [SubjectController@search](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SubjectController.php) | Search subjects by code or title |
| `POST` | `/api/v1/subjects/{subject}/assign` | `auth:sanctum`, `role:admin` | [SubjectController@assignTeachers](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SubjectController.php) | Assign teacher to subject and class arm |
| `POST` | `/api/v1/subjects/{subject}/unassign` | `auth:sanctum`, `role:admin` | [SubjectController@unassignTeachers](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SubjectController.php) | Remove teacher assignment from subject |
| `GET` | `/api/v1/subjects/{subject}/teachers` | `auth:sanctum`, `role:admin` | [SubjectController@teachers](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SubjectController.php) | List assigned teachers for a subject |
| `GET/POST/PUT/DELETE` | `/api/v1/subjects` | `auth:sanctum`, `role:admin` | [SubjectController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/SubjectController.php) | Standard Subject CRUD endpoints |

---

### 🎓 Student Management & Multi-Step Registration (`/api/v1/students`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/students` | `auth:sanctum`, `role:admin` | [StudentController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentController.php) | Filterable student roster |
| `GET` | `/api/v1/students/{id}` | `auth:sanctum`, `role:admin` | [StudentController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentController.php) | Full student profile details |
| `PUT` | `/api/v1/students/{id}` | `auth:sanctum`, `role:admin` | [StudentController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentController.php) | Update student record |
| `DELETE` | `/api/v1/students/{id}` | `auth:sanctum`, `role:admin` | [StudentController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentController.php) | Delete student record |
| `POST` | `/api/v1/students` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@storeBasicInfo](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | **Step 1**: Personal & Bio details |
| `POST` | `/api/v1/students/import` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@import](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | Excel batch import students (`StudentsImport`) |
| `PUT` | `/api/v1/students/{id}/academic` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@updateAcademicInfo](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | **Step 2**: Class & registration placement |
| `PUT` | `/api/v1/students/{id}/guardians` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@updateGuardians](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | **Step 3**: Parent/Guardian relationship link |
| `POST` | `/api/v1/students/{id}/documents` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@storeDocument](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | **Step 4**: Upload birth certificate / health files |
| `PUT` | `/api/v1/students/{id}/account-setup` | `auth:sanctum`, `role:admin` | [StudentRegistrationController@updateAccountSetup](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentRegistrationController.php) | **Step 5**: User credentials & status activation |

---

### 👥 Role-Based User Management (`/api/v1/users/{role}`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/users/{role}` | `auth:sanctum`, `role:admin` | [RoleUserController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RoleUserController.php) | List users dynamically by role slug (e.g. `teacher`) |
| `GET` | `/api/v1/users/{role}/{id}` | `auth:sanctum`, `role:admin` | [RoleUserController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RoleUserController.php) | View user details by role |
| `PUT` | `/api/v1/users/{role}/{id}` | `auth:sanctum`, `role:admin` | [RoleUserController@update](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RoleUserController.php) | Update user profile by role |
| `DELETE` | `/api/v1/users/{role}/{id}` | `auth:sanctum`, `role:admin` | [RoleUserController@destroy](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/RoleUserController.php) | Remove user account by role |

---

### 📝 Student Attendance Tracking (`/api/v1/attendance`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/attendance/mark` | `auth:sanctum`, `role:admin` | [StudentAttendanceController@markAttendance](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentAttendanceController.php) | Batch mark daily attendance for class arm |
| `GET` | `/api/v1/attendance/metrics` | `auth:sanctum`, `role:admin` | [StudentAttendanceController@metrics](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentAttendanceController.php) | Overall presence/absence rate metrics |
| `GET` | `/api/v1/attendance/weekly-metrics` | `auth:sanctum`, `role:admin` | [StudentAttendanceController@weeklyMetrics](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentAttendanceController.php) | Day-by-day weekly attendance trend |
| `GET` | `/api/v1/attendance/records` | `auth:sanctum`, `role:admin` | [StudentAttendanceController@records](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentAttendanceController.php) | Filterable attendance logs |
| `GET` | `/api/v1/attendance/watchlist` | `auth:sanctum`, `role:admin` | [StudentAttendanceController@watchlist](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/StudentAttendanceController.php) | Frequent absenteeism flag list |

---

### 🏅 Conduct & Behavior Logs (`/api/v1/conducts`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/conducts/insights` | `auth:sanctum`, `role:admin` | [ConductLogController@insights](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ConductLogController.php) | Behavior category breakdown & insights |
| `GET` | `/api/v1/students/{student}/conducts` | `auth:sanctum`, `role:admin` | [ConductLogController@studentConducts](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ConductLogController.php) | Conduct entries for single student |
| `GET/POST/PUT/DELETE` | `/api/v1/conducts` | `auth:sanctum`, `role:admin` | [ConductLogController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ConductLogController.php) | Create, view, update, delete behavior logs |

---

### ⚽ Extracurricular Activities (`/api/v1/activities`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/activities/{id}/enroll` | `auth:sanctum`, `role:admin` | [ExtracurricularActivityController@enrollStudents](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ExtracurricularActivityController.php) | Enroll students in club/activity |
| `POST` | `/api/v1/activities/{id}/unenroll` | `auth:sanctum`, `role:admin` | [ExtracurricularActivityController@unenrollStudents](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ExtracurricularActivityController.php) | Remove students from activity |
| `GET` | `/api/v1/activities/{id}/roster` | `auth:sanctum`, `role:admin` | [ExtracurricularActivityController@roster](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ExtracurricularActivityController.php) | Get student roster for activity |
| `GET/POST/PUT/DELETE` | `/api/v1/activities` | `auth:sanctum`, `role:admin` | [ExtracurricularActivityController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ExtracurricularActivityController.php) | Standard Activity CRUD endpoints |

---

### 📢 School Broadcast Announcements (`/api/v1/announcements`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET/POST/PUT/DELETE` | `/api/v1/announcements` | `auth:sanctum`, `role:admin` | [AnnouncementController](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/AnnouncementController.php) | Manage broadcast announcements |

---

### 🧑‍🏫 Teacher Portal (`/api/v1/teacher`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/teacher/dashboard` | `auth:sanctum`, `role:teacher` | [DashboardController@teacherAnalytics](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/DashboardController.php) | Teacher assigned class & student metrics |
| `GET` | `/api/v1/teacher/classes` | `auth:sanctum`, `role:teacher` | [TeacherPortalController@myClasses](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/TeacherPortalController.php) | Allocated classes, subjects, and student counts |

---

### 💬 Unified Cross-Role Messaging (`/api/v1/messages`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/messages/unread-count` | `auth:sanctum` | [MessageController@unreadCount](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Total unread message badge count |
| `GET` | `/api/v1/messages/contacts` | `auth:sanctum` | [MessageController@contacts](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Role-restricted contact directory |
| `GET` | `/api/v1/messages` | `auth:sanctum` | [MessageController@index](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Active conversation threads |
| `POST` | `/api/v1/messages` | `auth:sanctum` | [MessageController@store](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Dispatch new message / start conversation |
| `GET` | `/api/v1/messages/{id}` | `auth:sanctum` | [MessageController@show](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Get message thread history |
| `PUT` | `/api/v1/messages/{id}/read` | `auth:sanctum` | [MessageController@markRead](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/MessageController.php) | Mark thread as read |

---

### 📊 Dashboard Analytics Engine (`/api/v1/dashboard`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/dashboard/counts` | `auth:sanctum`, `role:admin` | [DashboardController@analyticCounts](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/DashboardController.php) | Global system counts (Students, Guardians, Teachers) |
| `GET` | `/api/v1/dashboard/status-analytics` | `auth:sanctum`, `role:admin` | [DashboardController@userStatusAnalytics](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/DashboardController.php) | Distribution percentages for active, pending, suspended accounts |

---

### 📈 Student Results & Ranking (`/api/v1/results`)
| Method | Endpoint | Auth / Middleware | Controller & Method | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/results/template` | `auth:sanctum` | [ResultController@downloadTemplate](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultController.php) | Generate & download dynamic Excel score sheet template |
| `POST` | `/api/v1/results/single` | `auth:sanctum` | [ResultController@uploadSingle](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultController.php) | Upload/Update manual subject scores for a single student |
| `POST` | `/api/v1/results/bulk-manual` | `auth:sanctum` | [ResultController@uploadBulkManual](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultController.php) | Upload multiple students via JSON payload |
| `POST` | `/api/v1/results/import` | `auth:sanctum` | [ResultController@uploadImport](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultController.php) | Excel/CSV file bulk score upload |
| `POST` | `/api/v1/results/compute` | `auth:sanctum`, `role:admin` | [ResultController@computeRanking](file:///c:/laragon/www/digiedu/app/Http/Controllers/Api/ResultController.php) | Process and compute Subject & Class Positions dynamically |

---

## 4. Key Performance & Eager Loading Optimizations

In adherence to system rules, all relationship queries enforce strict column selection to optimize query execution and payload size:
- **Teachers / Staff**: Eager loading uses selective fields like `with('user:id,name,email')` or `with('coordinator:id,name')`.
- **Subject Allocations**: Uses selective eager loading `with(['subject:id,name,code', 'schoolClass:id,name,category'])`.
- **Memory Efficiency**: Large data sets (e.g. attendance metrics & status analytics) use raw DB aggregation queries to maintain peak response speed.

---

## 5. Security & Verification Summary

1. **Token Sanctum Guards**: Every protected route requires a valid Bearer Token.
2. **Role Middleware Enforcement**: Spatie's `role:admin`, `role:teacher`, `role:parent` middleware guarantees strict boundary isolation across administrative operations, teacher tasks, and parent access.
3. **Account Status Middleware**: The custom `active` (`EnsureUserIsActive`) middleware enforces that parents and users cannot access protected portal endpoints until activation and OTP verification are complete.
