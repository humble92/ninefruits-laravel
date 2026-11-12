<?php

return [
    'resources' => [
        'courses' => [
            'label' => 'विषय',
            'plural_label' => 'विषयहरू',
        ],
        'course_versions' => [
            'label' => 'विषय संस्करण',
            'plural_label' => 'संस्करणहरू',
        ],
        'course_equivalencies' => [
            'label' => 'समकक्षता',
            'plural_label' => 'समकक्ष विषयहरू',
        ],
        'requirement_sets' => [
            'label' => 'स्नातक आवश्यकता',
            'plural_label' => 'स्नातक आवश्यकताहरू',
        ],
    ],
    'sections' => [
        'course' => 'विषय',
        'first_version' => 'पहिलो संस्करण',
        'requirements' => 'आवश्यकताहरू',
    ],
    'fields' => [
        'assessment_type' => 'परीक्षाको प्रकार',
        'code' => 'विषय कोड',
        'managing_unit' => 'व्यवस्थापन गर्ने एकाइ',
        'title' => 'शीर्षक',
        'description' => 'विवरण',
        'credits' => 'क्रेडिट',
        'lecture_hours' => 'व्याख्यान घण्टा',
        'lab_hours' => 'प्रयोगशाला घण्टा',
        'category' => 'विषयको वर्ग',
        'grading_basis' => 'ग्रेडिङ',
        'counts_toward_credit_limit' => 'क्रेडिट सीमामा गनिन्छ',
        'is_repeatable' => 'क्रेडिटका लागि फेरि लिन सकिन्छ',
        'requisites' => 'पूर्वशर्तहरू',
        'requisite_kind' => 'प्रकार',
        'group_number' => 'समूह',
        'required_course' => 'विषय',
        'minimum_grade' => 'न्यूनतम ग्रेड',
        'allows_concurrent' => 'एउटै सेमेस्टरमा लिन सकिन्छ',
        'effective_from' => 'नयाँ संस्करण लागू हुने मिति',
        'equivalent_course' => 'समकक्ष विषय',
        'is_mutual' => 'पारस्परिक',
        'note' => 'टिप्पणी',
        'program_role' => 'कार्यक्रमको भूमिका',
        'catalog_year_from' => 'पाठ्यक्रम वर्ष (देखि)',
        'catalog_year_to' => 'पाठ्यक्रम वर्ष (सम्म)',
        'total_credits' => 'जम्मा क्रेडिट',
        'requirement_kind' => 'आवश्यकता',
        'min_credits' => 'न्यूनतम क्रेडिट',
        'min_courses' => 'न्यूनतम विषय',
    ],
    'help' => [
        'counts_toward_credit_limit' => 'सेमेस्टरको क्रेडिट सीमाबाहिरका विषयका लागि बन्द गर्नुहोस् (जस्तै क्रेडिट बैंक विषयहरू)।',
        'is_repeatable' => 'फेरि लिँदा पुनः लिइएको नमानी फेरि क्रेडिट पाइन्छ (जस्तै शीर्षक बदलिने सेमिनार)।',
        'requisites' => 'एउटै समूहका विषयहरू विकल्प हुन् (तीमध्ये एउटा पर्याप्त); प्रत्येक समूह पूरा हुनुपर्छ।',
        'revise' => 'हालको संस्करण अघिल्लो दिन सकिन्छ; नयाँ संस्करण त्यसको र त्यसका पूर्वशर्तहरूको प्रतिलिपिबाट सुरु हुन्छ। पहिले नै सञ्चालित सेक्सनहरूले आफ्नै संस्करण राख्छन्।',
        'is_mutual' => 'दुवै एकअर्काका लागि गनिन्छन्। बन्द: समकक्ष विषय मात्र यसका लागि गनिन्छ।',
        'catalog_year_to' => 'खाली: अर्को सेट सुरु नभएसम्म पछिका पाठ्यक्रम वर्षहरूका लागि पनि।',
    ],
    'actions' => [
        'revise' => 'मितिदेखि संशोधन गर्नुहोस्',
        'add_requirement' => 'आवश्यकता थप्नुहोस्',
    ],
    'notifications' => [
        'revised' => 'नयाँ संस्करण बनाइयो।',
    ],
    'errors' => [
        'no_version_to_revise' => 'त्यो मितिमा लागू संस्करण छैन।',
        'revise_same_start' => 'त्यो मितिमा सुरु हुने संस्करण पहिले नै छ। बरु त्यसैलाई सम्पादन गर्नुहोस्।',
        'version_overlap' => 'यो अवधि विषयको अर्को संस्करणसँग खप्टिन्छ।',
        'requirement_set_overlap' => 'यी पाठ्यक्रम वर्षहरू कार्यक्रम र भूमिकाको अर्को आवश्यकता सेटसँग खप्टिन्छन्।',
    ],
    'import' => [
        'org_unit_required' => 'नयाँ विषयहरूलाई org_unit (व्यवस्थापन गर्ने एकाइको कोड) चाहिन्छ।',
    ],
    'or' => 'वा',
    'grading_bases' => [
        'graded' => 'अक्षर ग्रेड',
        'pass_fail' => 'उत्तीर्ण/अनुत्तीर्ण',
    ],
    'requisite_kinds' => [
        'prerequisite' => 'पूर्वआवश्यक विषय',
        'corequisite' => 'सहआवश्यक विषय',
    ],
    'program_roles' => [
        'primary' => 'मुख्य कार्यक्रम',
        'second_major' => 'दोस्रो मुख्य विषय',
        'minor' => 'माइनर',
    ],
    'requirement_kinds' => [
        'category_credits' => 'वर्गभित्रका क्रेडिट',
        'required_courses' => 'अनिवार्य विषयहरू',
        'course_group' => 'विषयहरूमध्ये छनोट',
        'assessment_pass' => 'उत्तीर्ण परीक्षा',
    ],
];
