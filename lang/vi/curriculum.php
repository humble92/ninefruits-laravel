<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'học phần',
            'plural_label' => 'học phần',
        ],
        'course_versions' => [
            'label' => 'phiên bản học phần',
            'plural_label' => 'phiên bản',
        ],
        'course_equivalencies' => [
            'label' => 'tương đương',
            'plural_label' => 'học phần tương đương',
        ],
        'requirement_sets' => [
            'label' => 'điều kiện tốt nghiệp',
            'plural_label' => 'điều kiện tốt nghiệp',
        ],
    ],
    'sections' => [
        'course' => 'Học phần',
        'first_version' => 'Phiên bản đầu tiên',
        'requirements' => 'Điều kiện',
    ],
    'fields' => [
        'assessment_type' => 'Loại kỳ thi',
        'code' => 'Mã học phần',
        'managing_unit' => 'Đơn vị quản lý',
        'title' => 'Tên học phần',
        'description' => 'Mô tả',
        'credits' => 'Số tín chỉ',
        'lecture_hours' => 'Số giờ lý thuyết',
        'lab_hours' => 'Số giờ thực hành',
        'category' => 'Loại học phần',
        'grading_basis' => 'Hình thức đánh giá',
        'counts_toward_credit_limit' => 'Tính vào giới hạn tín chỉ',
        'is_repeatable' => 'Được học lại để tích lũy thêm tín chỉ',
        'requisites' => 'Điều kiện học phần',
        'requisite_kind' => 'Loại',
        'group_number' => 'Nhóm',
        'required_course' => 'Học phần',
        'minimum_grade' => 'Điểm tối thiểu',
        'allows_concurrent' => 'Được học cùng học kỳ',
        'effective_from' => 'Phiên bản mới từ ngày',
        'equivalent_course' => 'Học phần tương đương',
        'is_mutual' => 'Hai chiều',
        'note' => 'Ghi chú',
        'program_role' => 'Vai trò chương trình',
        'catalog_year_from' => 'Áp dụng từ khóa',
        'catalog_year_to' => 'Áp dụng đến khóa',
        'total_credits' => 'Tổng số tín chỉ',
        'requirement_kind' => 'Điều kiện',
        'min_credits' => 'Số tín chỉ tối thiểu',
        'min_courses' => 'Số học phần tối thiểu',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'Tắt đối với học phần nằm ngoài giới hạn tín chỉ của học kỳ (ví dụ học phần ngân hàng tín chỉ).',
        'is_repeatable' => 'Học lại sẽ được tích lũy thêm tín chỉ (ví dụ seminar có chủ đề thay đổi) thay vì tính là học lại để cải thiện điểm.',
        'requisites' => 'Các học phần trong cùng một nhóm là lựa chọn thay thế (chỉ cần một học phần); mọi nhóm đều phải được đáp ứng.',
        'revise' => 'Phiên bản hiện tại kết thúc vào ngày hôm trước; phiên bản mới bắt đầu bằng bản sao của nó và các điều kiện học phần. Các lớp học phần đã mở vẫn giữ phiên bản cũ.',
        'is_mutual' => 'Mỗi học phần được tính cho học phần kia. Tắt: chỉ học phần tương đương được tính cho học phần này.',
        'catalog_year_to' => 'Để trống: áp dụng cả cho các khóa sau cho đến khi có bộ điều kiện khác.',
    ],
    'actions' => [
        'revise' => 'Sửa đổi từ một ngày',
        'add_requirement' => 'Thêm điều kiện',
    ],
    'notifications' => [
        'revised' => 'Đã tạo phiên bản mới.',
    ],
    'errors' => [
        'no_version_to_revise' => 'Không có phiên bản nào có hiệu lực vào ngày đó.',
        'revise_same_start' => 'Đã có phiên bản bắt đầu vào ngày đó. Hãy chỉnh sửa phiên bản đó.',
        'version_overlap' => 'Khoảng thời gian này trùng với một phiên bản khác của học phần.',
        'requirement_set_overlap' => 'Các khóa này trùng với một bộ điều kiện khác của cùng chương trình và vai trò.',
    ],
    'import' => [
        'org_unit_required' => 'Học phần mới cần có org_unit (mã đơn vị quản lý).',
    ],
    'or' => 'hoặc',
    'grading_bases' => [
        'graded' => 'Điểm chữ',
        'pass_fail' => 'Đạt/không đạt',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'Học phần tiên quyết',
        'corequisite' => 'Học phần song hành',
    ],
    'program_roles' => [
        'primary' => 'Ngành chính',
        'second_major' => 'Ngành thứ hai',
        'minor' => 'Ngành phụ',
    ],
    'requirement_kinds' => [
        'category_credits' => 'Số tín chỉ theo loại học phần',
        'required_courses' => 'Học phần bắt buộc',
        'course_group' => 'Lựa chọn từ nhóm học phần',
        'assessment_pass' => 'Đạt kỳ thi',
    ],
];
