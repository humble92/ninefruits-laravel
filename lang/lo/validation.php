<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'ຕ້ອງຍອມຮັບຊ່ອງ :attribute.',
    'accepted_if' => 'ຕ້ອງຍອມຮັບຊ່ອງ :attribute ເມື່ອ :other ເປັນ :value.',
    'active_url' => 'ຊ່ອງ :attribute ຕ້ອງເປັນ URL ທີ່ຖືກຕ້ອງ.',
    'after' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີຫຼັງຈາກ :date.',
    'after_or_equal' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີຫຼັງຈາກ ຫຼື ເທົ່າກັບ :date.',
    'alpha' => 'ຊ່ອງ :attribute ຕ້ອງມີແຕ່ຕົວອັກສອນເທົ່ານັ້ນ.',
    'alpha_dash' => 'ຊ່ອງ :attribute ຕ້ອງມີແຕ່ຕົວອັກສອນ, ຕົວເລກ, ຂີດກາງ ແລະ ຂີດລຸ່ມເທົ່ານັ້ນ.',
    'alpha_num' => 'ຊ່ອງ :attribute ຕ້ອງມີແຕ່ຕົວອັກສອນ ແລະ ຕົວເລກເທົ່ານັ້ນ.',
    'any_of' => 'ຊ່ອງ :attribute ບໍ່ຖືກຕ້ອງ.',
    'array' => 'ຊ່ອງ :attribute ຕ້ອງເປັນອາເຣ.',
    'array_keys' => 'ຊ່ອງ :attribute ຕ້ອງມີແຕ່ຄີຕໍ່ໄປນີ້ເທົ່ານັ້ນ: :values.',
    'ascii' => 'ຊ່ອງ :attribute ຕ້ອງມີແຕ່ຕົວອັກສອນ, ຕົວເລກ ແລະ ສັນຍາລັກແບບໜຶ່ງໄບຕ໌ເທົ່ານັ້ນ.',
    'base64' => 'ຊ່ອງ :attribute ຕ້ອງເປັນສະຕຣິງ Base64 ທີ່ຖືກຕ້ອງ.',
    'before' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີກ່ອນ :date.',
    'before_or_equal' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີກ່ອນ ຫຼື ເທົ່າກັບ :date.',
    'between' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີລະຫວ່າງ :min ຫາ :max ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດລະຫວ່າງ :min ຫາ :max ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າລະຫວ່າງ :min ຫາ :max.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີລະຫວ່າງ :min ຫາ :max ຕົວອັກສອນ.',
    ],
    'boolean' => 'ຊ່ອງ :attribute ຕ້ອງເປັນ true ຫຼື false.',
    'can' => 'ຊ່ອງ :attribute ມີຄ່າທີ່ບໍ່ໄດ້ຮັບອະນຸຍາດ.',
    'confirmed' => 'ການຢືນຢັນຊ່ອງ :attribute ບໍ່ກົງກັນ.',
    'contains' => 'ຊ່ອງ :attribute ຂາດຄ່າທີ່ຈຳເປັນ.',
    'current_password' => 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ.',
    'date' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີທີ່ຖືກຕ້ອງ.',
    'date_equals' => 'ຊ່ອງ :attribute ຕ້ອງເປັນວັນທີເທົ່າກັບ :date.',
    'date_format' => 'ຊ່ອງ :attribute ຕ້ອງກົງກັບຮູບແບບ :format.',
    'decimal' => 'ຊ່ອງ :attribute ຕ້ອງມີທົດສະນິຍົມ :decimal ຕຳແໜ່ງ.',
    'declined' => 'ຕ້ອງປະຕິເສດຊ່ອງ :attribute.',
    'declined_if' => 'ຕ້ອງປະຕິເສດຊ່ອງ :attribute ເມື່ອ :other ເປັນ :value.',
    'different' => 'ຊ່ອງ :attribute ແລະ :other ຕ້ອງແຕກຕ່າງກັນ.',
    'digits' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຕົວເລກ :digits ຫຼັກ.',
    'digits_between' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຕົວເລກລະຫວ່າງ :min ຫາ :max ຫຼັກ.',
    'dimensions' => 'ຊ່ອງ :attribute ມີຂະໜາດຮູບພາບທີ່ບໍ່ຖືກຕ້ອງ.',
    'distinct' => 'ຊ່ອງ :attribute ມີຄ່າຊ້ຳກັນ.',
    'doesnt_contain' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີຄ່າໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'doesnt_end_with' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ລົງທ້າຍດ້ວຍຄ່າໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'doesnt_start_with' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ເລີ່ມຕົ້ນດ້ວຍຄ່າໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'email' => 'ຊ່ອງ :attribute ຕ້ອງເປັນທີ່ຢູ່ອີເມວທີ່ຖືກຕ້ອງ.',
    'encoding' => 'ຊ່ອງ :attribute ຕ້ອງເຂົ້າລະຫັດເປັນ :encoding.',
    'ends_with' => 'ຊ່ອງ :attribute ຕ້ອງລົງທ້າຍດ້ວຍຄ່າໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'enum' => ':attribute ທີ່ເລືອກບໍ່ຖືກຕ້ອງ.',
    'exists' => ':attribute ທີ່ເລືອກບໍ່ຖືກຕ້ອງ.',
    'extensions' => 'ຊ່ອງ :attribute ຕ້ອງມີນາມສະກຸນໄຟລ໌ໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'file' => 'ຊ່ອງ :attribute ຕ້ອງເປັນໄຟລ໌.',
    'filled' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າ.',
    'gt' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີຫຼາຍກວ່າ :value ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດໃຫຍ່ກວ່າ :value ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າຫຼາຍກວ່າ :value.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີຫຼາຍກວ່າ :value ຕົວອັກສອນ.',
    ],
    'gte' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີ :value ລາຍການ ຫຼື ຫຼາຍກວ່າ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດໃຫຍ່ກວ່າ ຫຼື ເທົ່າກັບ :value ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າຫຼາຍກວ່າ ຫຼື ເທົ່າກັບ :value.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີ :value ຕົວອັກສອນ ຫຼື ຫຼາຍກວ່າ.',
    ],
    'hex_color' => 'ຊ່ອງ :attribute ຕ້ອງເປັນລະຫັດສີເລກຖານສິບຫົກທີ່ຖືກຕ້ອງ.',
    'image' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຮູບພາບ.',
    'in' => ':attribute ທີ່ເລືອກບໍ່ຖືກຕ້ອງ.',
    'in_array' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່ໃນ :other.',
    'in_array_keys' => 'ຊ່ອງ :attribute ຕ້ອງມີຢ່າງໜ້ອຍໜຶ່ງຄີຕໍ່ໄປນີ້: :values.',
    'integer' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຈຳນວນເຕັມ.',
    'ip' => 'ຊ່ອງ :attribute ຕ້ອງເປັນທີ່ຢູ່ IP ທີ່ຖືກຕ້ອງ.',
    'ipv4' => 'ຊ່ອງ :attribute ຕ້ອງເປັນທີ່ຢູ່ IPv4 ທີ່ຖືກຕ້ອງ.',
    'ipv6' => 'ຊ່ອງ :attribute ຕ້ອງເປັນທີ່ຢູ່ IPv6 ທີ່ຖືກຕ້ອງ.',
    'json' => 'ຊ່ອງ :attribute ຕ້ອງເປັນສະຕຣິງ JSON ທີ່ຖືກຕ້ອງ.',
    'list' => 'ຊ່ອງ :attribute ຕ້ອງເປັນລາຍການ.',
    'lowercase' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຕົວພິມນ້ອຍ.',
    'lt' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີໜ້ອຍກວ່າ :value ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດນ້ອຍກວ່າ :value ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່ານ້ອຍກວ່າ :value.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີໜ້ອຍກວ່າ :value ຕົວອັກສອນ.',
    ],
    'lte' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີບໍ່ເກີນ :value ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດນ້ອຍກວ່າ ຫຼື ເທົ່າກັບ :value ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່ານ້ອຍກວ່າ ຫຼື ເທົ່າກັບ :value.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີບໍ່ເກີນ :value ຕົວອັກສອນ.',
    ],
    'mac_address' => 'ຊ່ອງ :attribute ຕ້ອງເປັນທີ່ຢູ່ MAC ທີ່ຖືກຕ້ອງ.',
    'max' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີບໍ່ເກີນ :max ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດບໍ່ເກີນ :max ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າບໍ່ເກີນ :max.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີບໍ່ເກີນ :max ຕົວອັກສອນ.',
    ],
    'max_digits' => 'ຊ່ອງ :attribute ຕ້ອງມີບໍ່ເກີນ :max ຫຼັກ.',
    'mimes' => 'ຊ່ອງ :attribute ຕ້ອງເປັນໄຟລ໌ປະເພດ: :values.',
    'mimetypes' => 'ຊ່ອງ :attribute ຕ້ອງເປັນໄຟລ໌ປະເພດ: :values.',
    'min' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີຢ່າງໜ້ອຍ :min ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດຢ່າງໜ້ອຍ :min ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າຢ່າງໜ້ອຍ :min.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີຢ່າງໜ້ອຍ :min ຕົວອັກສອນ.',
    ],
    'min_digits' => 'ຊ່ອງ :attribute ຕ້ອງມີຢ່າງໜ້ອຍ :min ຫຼັກ.',
    'missing' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີ.',
    'missing_if' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີເມື່ອ :other ເປັນ :value.',
    'missing_unless' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີ ເວັ້ນແຕ່ :other ເປັນ :value.',
    'missing_with' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີເມື່ອມີ :values.',
    'missing_with_all' => 'ຊ່ອງ :attribute ຕ້ອງບໍ່ມີເມື່ອມີ :values ທັງໝົດ.',
    'multiple_of' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຈຳນວນທະວີຄູນຂອງ :value.',
    'not_in' => ':attribute ທີ່ເລືອກບໍ່ຖືກຕ້ອງ.',
    'not_regex' => 'ຮູບແບບຂອງຊ່ອງ :attribute ບໍ່ຖືກຕ້ອງ.',
    'numeric' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຕົວເລກ.',
    'password' => [
        'letters' => 'ຊ່ອງ :attribute ຕ້ອງມີຕົວອັກສອນຢ່າງໜ້ອຍໜຶ່ງຕົວ.',
        'mixed' => 'ຊ່ອງ :attribute ຕ້ອງມີຕົວພິມໃຫຍ່ ແລະ ຕົວພິມນ້ອຍຢ່າງໜ້ອຍຢ່າງລະໜຶ່ງຕົວ.',
        'numbers' => 'ຊ່ອງ :attribute ຕ້ອງມີຕົວເລກຢ່າງໜ້ອຍໜຶ່ງຕົວ.',
        'symbols' => 'ຊ່ອງ :attribute ຕ້ອງມີສັນຍາລັກຢ່າງໜ້ອຍໜຶ່ງຕົວ.',
        'uncompromised' => ':attribute ທີ່ໃຫ້ມາເຄີຍປາກົດໃນຂໍ້ມູນຮົ່ວໄຫຼ. ກະລຸນາເລືອກ :attribute ອື່ນ.',
    ],
    'present' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່.',
    'present_if' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່ເມື່ອ :other ເປັນ :value.',
    'present_unless' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່ ເວັ້ນແຕ່ :other ເປັນ :value.',
    'present_with' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່ເມື່ອມີ :values.',
    'present_with_all' => 'ຊ່ອງ :attribute ຕ້ອງມີຢູ່ເມື່ອມີ :values ທັງໝົດ.',
    'prohibited' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດ.',
    'prohibited_if' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດເມື່ອ :other ເປັນ :value.',
    'prohibited_if_accepted' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດເມື່ອຍອມຮັບ :other.',
    'prohibited_if_declined' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດເມື່ອປະຕິເສດ :other.',
    'prohibited_unless' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດ ເວັ້ນແຕ່ :other ຢູ່ໃນ :values.',
    'prohibits' => 'ຊ່ອງ :attribute ບໍ່ອະນຸຍາດໃຫ້ມີ :other.',
    'regex' => 'ຮູບແບບຂອງຊ່ອງ :attribute ບໍ່ຖືກຕ້ອງ.',
    'required' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute.',
    'required_array_keys' => 'ຊ່ອງ :attribute ຕ້ອງມີລາຍການສຳລັບ: :values.',
    'required_if' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອ :other ເປັນ :value.',
    'required_if_accepted' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອຍອມຮັບ :other.',
    'required_if_declined' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອປະຕິເສດ :other.',
    'required_unless' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເວັ້ນແຕ່ :other ຢູ່ໃນ :values.',
    'required_with' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອມີ :values.',
    'required_with_all' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອມີ :values ທັງໝົດ.',
    'required_without' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອບໍ່ມີ :values.',
    'required_without_all' => 'ຈຳເປັນຕ້ອງປ້ອນຊ່ອງ :attribute ເມື່ອບໍ່ມີ :values ໃດໆເລີຍ.',
    'same' => 'ຊ່ອງ :attribute ຕ້ອງກົງກັບ :other.',
    'size' => [
        'array' => 'ຊ່ອງ :attribute ຕ້ອງມີ :size ລາຍການ.',
        'file' => 'ຊ່ອງ :attribute ຕ້ອງມີຂະໜາດ :size ກິໂລໄບຕ໌.',
        'numeric' => 'ຊ່ອງ :attribute ຕ້ອງມີຄ່າ :size.',
        'string' => 'ຊ່ອງ :attribute ຕ້ອງມີ :size ຕົວອັກສອນ.',
    ],
    'starts_with' => 'ຊ່ອງ :attribute ຕ້ອງເລີ່ມຕົ້ນດ້ວຍຄ່າໃດໜຶ່ງຕໍ່ໄປນີ້: :values.',
    'string' => 'ຊ່ອງ :attribute ຕ້ອງເປັນສະຕຣິງ.',
    'timezone' => 'ຊ່ອງ :attribute ຕ້ອງເປັນເຂດເວລາທີ່ຖືກຕ້ອງ.',
    'unique' => ':attribute ນີ້ຖືກໃຊ້ແລ້ວ.',
    'uploaded' => 'ອັບໂຫຼດ :attribute ບໍ່ສຳເລັດ.',
    'uppercase' => 'ຊ່ອງ :attribute ຕ້ອງເປັນຕົວພິມໃຫຍ່.',
    'url' => 'ຊ່ອງ :attribute ຕ້ອງເປັນ URL ທີ່ຖືກຕ້ອງ.',
    'ulid' => 'ຊ່ອງ :attribute ຕ້ອງເປັນ ULID ທີ່ຖືກຕ້ອງ.',
    'uuid' => 'ຊ່ອງ :attribute ຕ້ອງເປັນ UUID ທີ່ຖືກຕ້ອງ.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
