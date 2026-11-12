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

    'accepted' => ':attribute स्वीकार गर्नुपर्छ।',
    'accepted_if' => ':other :value हुँदा :attribute स्वीकार गर्नुपर्छ।',
    'active_url' => ':attribute मान्य URL हुनुपर्छ।',
    'after' => ':attribute :date पछिको मिति हुनुपर्छ।',
    'after_or_equal' => ':attribute :date वा त्यसपछिको मिति हुनुपर्छ।',
    'alpha' => ':attribute मा अक्षर मात्र हुनुपर्छ।',
    'alpha_dash' => ':attribute मा अक्षर, अङ्क, ड्यास र अन्डरस्कोर मात्र हुनुपर्छ।',
    'alpha_num' => ':attribute मा अक्षर र अङ्क मात्र हुनुपर्छ।',
    'any_of' => ':attribute अमान्य छ।',
    'array' => ':attribute एरे हुनुपर्छ।',
    'array_keys' => ':attribute मा यी कुञ्जीहरू मात्र हुनुपर्छ: :values।',
    'ascii' => ':attribute मा एक-बाइट अक्षर, अङ्क र चिह्न मात्र हुनुपर्छ।',
    'base64' => ':attribute मान्य Base64 स्ट्रिङ हुनुपर्छ।',
    'before' => ':attribute :date अघिको मिति हुनुपर्छ।',
    'before_or_equal' => ':attribute :date वा त्यसअघिको मिति हुनुपर्छ।',
    'between' => [
        'array' => ':attribute मा :min देखि :max वटा वस्तु हुनुपर्छ।',
        'file' => ':attribute :min देखि :max किलोबाइटबीच हुनुपर्छ।',
        'numeric' => ':attribute :min देखि :max बीच हुनुपर्छ।',
        'string' => ':attribute :min देखि :max अक्षरबीच हुनुपर्छ।',
    ],
    'boolean' => ':attribute सत्य वा असत्य हुनुपर्छ।',
    'can' => ':attribute मा अनधिकृत मान छ।',
    'confirmed' => ':attribute को पुष्टि मेल खाँदैन।',
    'contains' => ':attribute मा आवश्यक मान छैन।',
    'current_password' => 'पासवर्ड गलत छ।',
    'date' => ':attribute मान्य मिति हुनुपर्छ।',
    'date_equals' => ':attribute :date बराबरको मिति हुनुपर्छ।',
    'date_format' => ':attribute :format ढाँचासँग मेल खानुपर्छ।',
    'decimal' => ':attribute मा :decimal दशमलव स्थान हुनुपर्छ।',
    'declined' => ':attribute अस्वीकार गर्नुपर्छ।',
    'declined_if' => ':other :value हुँदा :attribute अस्वीकार गर्नुपर्छ।',
    'different' => ':attribute र :other फरक हुनुपर्छ।',
    'digits' => ':attribute :digits अङ्कको हुनुपर्छ।',
    'digits_between' => ':attribute :min देखि :max अङ्कबीच हुनुपर्छ।',
    'dimensions' => ':attribute को तस्बिर आकार अमान्य छ।',
    'distinct' => ':attribute मा दोहोरो मान छ।',
    'doesnt_contain' => ':attribute मा यीमध्ये कुनै पनि हुनु हुँदैन: :values।',
    'doesnt_end_with' => ':attribute यीमध्ये कुनैबाट अन्त्य हुनु हुँदैन: :values।',
    'doesnt_start_with' => ':attribute यीमध्ये कुनैबाट सुरु हुनु हुँदैन: :values।',
    'email' => ':attribute मान्य इमेल ठेगाना हुनुपर्छ।',
    'encoding' => ':attribute :encoding मा इन्कोड गरिएको हुनुपर्छ।',
    'ends_with' => ':attribute यीमध्ये कुनै एकबाट अन्त्य हुनुपर्छ: :values।',
    'enum' => 'छानिएको :attribute अमान्य छ।',
    'exists' => 'छानिएको :attribute अमान्य छ।',
    'extensions' => ':attribute को एक्सटेन्सन यीमध्ये एक हुनुपर्छ: :values।',
    'file' => ':attribute फाइल हुनुपर्छ।',
    'filled' => ':attribute मा मान हुनुपर्छ।',
    'gt' => [
        'array' => ':attribute मा :value भन्दा बढी वस्तु हुनुपर्छ।',
        'file' => ':attribute :value किलोबाइटभन्दा ठूलो हुनुपर्छ।',
        'numeric' => ':attribute :value भन्दा ठूलो हुनुपर्छ।',
        'string' => ':attribute :value अक्षरभन्दा लामो हुनुपर्छ।',
    ],
    'gte' => [
        'array' => ':attribute मा :value वा बढी वस्तु हुनुपर्छ।',
        'file' => ':attribute :value किलोबाइट वा बढी हुनुपर्छ।',
        'numeric' => ':attribute :value वा बढी हुनुपर्छ।',
        'string' => ':attribute :value अक्षर वा बढी हुनुपर्छ।',
    ],
    'hex_color' => ':attribute मान्य हेक्साडेसिमल रङ हुनुपर्छ।',
    'image' => ':attribute तस्बिर हुनुपर्छ।',
    'in' => 'छानिएको :attribute अमान्य छ।',
    'in_array' => ':attribute :other मा हुनुपर्छ।',
    'in_array_keys' => ':attribute मा यीमध्ये कम्तीमा एउटा कुञ्जी हुनुपर्छ: :values।',
    'integer' => ':attribute पूर्णाङ्क हुनुपर्छ।',
    'ip' => ':attribute मान्य IP ठेगाना हुनुपर्छ।',
    'ipv4' => ':attribute मान्य IPv4 ठेगाना हुनुपर्छ।',
    'ipv6' => ':attribute मान्य IPv6 ठेगाना हुनुपर्छ।',
    'json' => ':attribute मान्य JSON स्ट्रिङ हुनुपर्छ।',
    'list' => ':attribute सूची हुनुपर्छ।',
    'lowercase' => ':attribute साना अक्षरमा हुनुपर्छ।',
    'lt' => [
        'array' => ':attribute मा :value भन्दा कम वस्तु हुनुपर्छ।',
        'file' => ':attribute :value किलोबाइटभन्दा सानो हुनुपर्छ।',
        'numeric' => ':attribute :value भन्दा सानो हुनुपर्छ।',
        'string' => ':attribute :value अक्षरभन्दा छोटो हुनुपर्छ।',
    ],
    'lte' => [
        'array' => ':attribute मा :value भन्दा बढी वस्तु हुनु हुँदैन।',
        'file' => ':attribute :value किलोबाइट वा कम हुनुपर्छ।',
        'numeric' => ':attribute :value वा कम हुनुपर्छ।',
        'string' => ':attribute :value अक्षर वा कम हुनुपर्छ।',
    ],
    'mac_address' => ':attribute मान्य MAC ठेगाना हुनुपर्छ।',
    'max' => [
        'array' => ':attribute मा :max भन्दा बढी वस्तु हुनु हुँदैन।',
        'file' => ':attribute :max किलोबाइटभन्दा ठूलो हुनु हुँदैन।',
        'numeric' => ':attribute :max भन्दा ठूलो हुनु हुँदैन।',
        'string' => ':attribute :max अक्षरभन्दा लामो हुनु हुँदैन।',
    ],
    'max_digits' => ':attribute मा :max भन्दा बढी अङ्क हुनु हुँदैन।',
    'mimes' => ':attribute यी प्रकारको फाइल हुनुपर्छ: :values।',
    'mimetypes' => ':attribute यी प्रकारको फाइल हुनुपर्छ: :values।',
    'min' => [
        'array' => ':attribute मा कम्तीमा :min वस्तु हुनुपर्छ।',
        'file' => ':attribute कम्तीमा :min किलोबाइट हुनुपर्छ।',
        'numeric' => ':attribute कम्तीमा :min हुनुपर्छ।',
        'string' => ':attribute कम्तीमा :min अक्षरको हुनुपर्छ।',
    ],
    'min_digits' => ':attribute मा कम्तीमा :min अङ्क हुनुपर्छ।',
    'missing' => ':attribute हुनु हुँदैन।',
    'missing_if' => ':other :value हुँदा :attribute हुनु हुँदैन।',
    'missing_unless' => ':other :value नभएसम्म :attribute हुनु हुँदैन।',
    'missing_with' => ':values हुँदा :attribute हुनु हुँदैन।',
    'missing_with_all' => ':values हुँदा :attribute हुनु हुँदैन।',
    'multiple_of' => ':attribute :value को गुणज हुनुपर्छ।',
    'not_in' => 'छानिएको :attribute अमान्य छ।',
    'not_regex' => ':attribute को ढाँचा अमान्य छ।',
    'numeric' => ':attribute सङ्ख्या हुनुपर्छ।',
    'password' => [
        'letters' => ':attribute मा कम्तीमा एउटा अक्षर हुनुपर्छ।',
        'mixed' => ':attribute मा कम्तीमा एउटा ठूलो र एउटा सानो अक्षर हुनुपर्छ।',
        'numbers' => ':attribute मा कम्तीमा एउटा अङ्क हुनुपर्छ।',
        'symbols' => ':attribute मा कम्तीमा एउटा चिह्न हुनुपर्छ।',
        'uncompromised' => 'दिइएको :attribute डेटा चुहावटमा देखिएको छ। कृपया फरक :attribute रोज्नुहोस्।',
    ],
    'present' => ':attribute उपस्थित हुनुपर्छ।',
    'present_if' => ':other :value हुँदा :attribute उपस्थित हुनुपर्छ।',
    'present_unless' => ':other :value नभएसम्म :attribute उपस्थित हुनुपर्छ।',
    'present_with' => ':values हुँदा :attribute उपस्थित हुनुपर्छ।',
    'present_with_all' => ':values हुँदा :attribute उपस्थित हुनुपर्छ।',
    'prohibited' => ':attribute निषेधित छ।',
    'prohibited_if' => ':other :value हुँदा :attribute निषेधित छ।',
    'prohibited_if_accepted' => ':other स्वीकार गरिँदा :attribute निषेधित छ।',
    'prohibited_if_declined' => ':other अस्वीकार गरिँदा :attribute निषेधित छ।',
    'prohibited_unless' => ':other :values मा नभएसम्म :attribute निषेधित छ।',
    'prohibits' => ':attribute ले :other उपस्थित हुन दिँदैन।',
    'regex' => ':attribute को ढाँचा अमान्य छ।',
    'required' => ':attribute अनिवार्य छ।',
    'required_array_keys' => ':attribute मा यिनका प्रविष्टि हुनुपर्छ: :values।',
    'required_if' => ':other :value हुँदा :attribute अनिवार्य छ।',
    'required_if_accepted' => ':other स्वीकार गरिँदा :attribute अनिवार्य छ।',
    'required_if_declined' => ':other अस्वीकार गरिँदा :attribute अनिवार्य छ।',
    'required_unless' => ':other :values मा नभएसम्म :attribute अनिवार्य छ।',
    'required_with' => ':values हुँदा :attribute अनिवार्य छ।',
    'required_with_all' => ':values हुँदा :attribute अनिवार्य छ।',
    'required_without' => ':values नहुँदा :attribute अनिवार्य छ।',
    'required_without_all' => ':values मध्ये कुनै नहुँदा :attribute अनिवार्य छ।',
    'same' => ':attribute :other सँग मेल खानुपर्छ।',
    'size' => [
        'array' => ':attribute मा :size वस्तु हुनुपर्छ।',
        'file' => ':attribute :size किलोबाइट हुनुपर्छ।',
        'numeric' => ':attribute :size हुनुपर्छ।',
        'string' => ':attribute :size अक्षरको हुनुपर्छ।',
    ],
    'starts_with' => ':attribute यीमध्ये कुनै एकबाट सुरु हुनुपर्छ: :values।',
    'string' => ':attribute स्ट्रिङ हुनुपर्छ।',
    'timezone' => ':attribute मान्य समय क्षेत्र हुनुपर्छ।',
    'unique' => ':attribute पहिले नै प्रयोग भइसकेको छ।',
    'uploaded' => ':attribute अपलोड हुन सकेन।',
    'uppercase' => ':attribute ठूला अक्षरमा हुनुपर्छ।',
    'url' => ':attribute मान्य URL हुनुपर्छ।',
    'ulid' => ':attribute मान्य ULID हुनुपर्छ।',
    'uuid' => ':attribute मान्य UUID हुनुपर्छ।',

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
