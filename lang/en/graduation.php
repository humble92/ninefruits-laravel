<?php

return [
    'resources' => [
        'audits' => ['label' => 'Degree audit', 'plural_label' => 'Degree audits'],
    ],
    'fields' => [
        'is_satisfied' => 'Satisfied',
        'earned_credits' => 'Earned credits',
        'audited_at' => 'Audited',
        'min_year_level' => 'From year level',
        'waived' => 'Waived',
        'courses' => 'courses',
        'missing' => 'Not passed',
    ],
    'satisfied' => ['yes' => 'Satisfied', 'no' => 'Not satisfied'],
    'issues' => [
        'no_requirement_set' => 'No graduation requirements are defined for catalog year :year.',
        'requirement' => 'Requirement not met: :name',
        'total_credits' => 'Total credits :earned of :required required.',
        'gpa' => 'Cumulative GPA :gpa below the minimum :minimum.',
    ],
    'errors' => [
        'not_enrolled' => 'Only an enrolled student can graduate.',
        'no_audit' => 'No stored degree audit: run the audit first.',
        'audit_not_satisfied' => 'The latest degree audit is not satisfied.',
    ],
    'actions' => [
        'audit_now' => 'Degree audit',
        'save_audit' => 'Save this audit',
        'run_audits' => 'Run audits',
        'open' => 'Open',
        'rerun' => 'Audit again',
        'waive' => 'Waive a requirement',
        'graduate' => 'Graduate',
    ],
    'notifications' => [
        'audit_saved' => 'Degree audit saved.',
        'audits_run' => ':students student(s) audited, :satisfied satisfied.',
        'waived' => 'Requirement waived.',
        'graduated' => ':count student(s) graduated, :errors not processed.',
    ],
    'portal' => [
        'title' => 'Graduation requirements',
        'intro' => 'Your progress against the requirements of catalog year :year, evaluated now. Ask your department about waivers.',
    ],
    'reports' => [
        'title' => 'Reports',
        'report' => 'Report',
        'rows' => ':count row(s) (first 500 on screen; the CSV has them all)',
        'download_csv' => 'Download CSV',
        'empty' => 'No rows. Choose the filters above.',
        'names' => [
            'students' => 'Student list',
            'headcount' => 'Headcount by unit, program, year and status',
            'term_records' => 'Term grade summary (warnings, honors)',
            'graduation_candidates' => 'Graduation candidates (latest audit)',
            'status_changes' => 'Status changes',
            'section_enrollments' => 'Enrollment per section',
            'not_registered' => 'Students not registered in a term',
        ],
    ],
    'report_columns' => [
        'student_number' => 'Student number', 'student_name' => 'Name', 'program_name' => 'Program', 'program_code' => 'Program code', 'program_role' => 'Role',
        'org_unit_name' => 'Unit', 'org_unit_code' => 'Unit code', 'year_level' => 'Year', 'status' => 'Status', 'admission_date' => 'Admitted', 'graduation_date' => 'Graduated',
        'degree_number' => 'Degree number', 'students' => 'Students', 'term_code' => 'Term', 'attempted_credits' => 'Attempted', 'earned_credits' => 'Earned', 'term_gpa' => 'Term GPA',
        'cumulative_earned_credits' => 'Cumulative earned', 'cumulative_gpa' => 'Cumulative GPA', 'academic_standing' => 'Standing', 'warning_count' => 'Warnings', 'is_honors' => 'Honors',
        'honors_rank' => 'Honors rank', 'is_satisfied' => 'Satisfied', 'required_credits' => 'Required', 'audited_at' => 'Audited', 'type_name' => 'Change', 'from_status' => 'From',
        'to_status' => 'To', 'effective_date' => 'Effective', 'decided_at' => 'Decided', 'course_code' => 'Course', 'course_title' => 'Title', 'section_code' => 'Section',
        'instructors' => 'Instructors', 'capacity' => 'Capacity', 'enrolled' => 'Enrolled', 'waiting' => 'Waiting',
    ],
];
