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

    'accepted' => ':attribute maydoni qabul qilinishi kerak.',
    'accepted_if' => ':other :value boʻlganda :attribute maydoni qabul qilinishi kerak.',
    'active_url' => ':attribute maydoni toʻgʻri URL boʻlishi kerak.',
    'after' => ':attribute maydoni :date dan keyingi sana boʻlishi kerak.',
    'after_or_equal' => ':attribute maydoni :date yoki undan keyingi sana boʻlishi kerak.',
    'alpha' => ':attribute maydoni faqat harflardan iborat boʻlishi kerak.',
    'alpha_dash' => ':attribute maydoni faqat harflar, raqamlar, chiziqcha va pastki chiziqdan iborat boʻlishi kerak.',
    'alpha_num' => ':attribute maydoni faqat harflar va raqamlardan iborat boʻlishi kerak.',
    'any_of' => ':attribute maydoni yaroqsiz.',
    'array' => ':attribute maydoni massiv boʻlishi kerak.',
    'array_keys' => ':attribute maydoni faqat quyidagi kalitlarni oʻz ichiga olishi kerak: :values.',
    'ascii' => ':attribute maydoni faqat bir baytli harf-raqam belgilari va simvollardan iborat boʻlishi kerak.',
    'base64' => ':attribute maydoni toʻgʻri Base64 satri boʻlishi kerak.',
    'before' => ':attribute maydoni :date dan oldingi sana boʻlishi kerak.',
    'before_or_equal' => ':attribute maydoni :date yoki undan oldingi sana boʻlishi kerak.',
    'between' => [
        'array' => ':attribute maydonida :min dan :max tagacha element boʻlishi kerak.',
        'file' => ':attribute maydoni :min dan :max kilobaytgacha boʻlishi kerak.',
        'numeric' => ':attribute maydoni :min va :max oraligʻida boʻlishi kerak.',
        'string' => ':attribute maydoni :min dan :max tagacha belgidan iborat boʻlishi kerak.',
    ],
    'boolean' => ':attribute maydoni rost yoki yolgʻon boʻlishi kerak.',
    'can' => ':attribute maydonida ruxsat etilmagan qiymat bor.',
    'confirmed' => ':attribute maydoni tasdigʻi mos kelmaydi.',
    'contains' => ':attribute maydonida majburiy qiymat yetishmaydi.',
    'current_password' => 'Parol notoʻgʻri.',
    'date' => ':attribute maydoni toʻgʻri sana boʻlishi kerak.',
    'date_equals' => ':attribute maydoni :date ga teng sana boʻlishi kerak.',
    'date_format' => ':attribute maydoni :format formatiga mos boʻlishi kerak.',
    'decimal' => ':attribute maydonida :decimal ta kasr xonasi boʻlishi kerak.',
    'declined' => ':attribute maydoni rad etilishi kerak.',
    'declined_if' => ':other :value boʻlganda :attribute maydoni rad etilishi kerak.',
    'different' => ':attribute maydoni va :other har xil boʻlishi kerak.',
    'digits' => ':attribute maydoni :digits xonali boʻlishi kerak.',
    'digits_between' => ':attribute maydoni :min dan :max xonagacha boʻlishi kerak.',
    'dimensions' => ':attribute maydonidagi rasm oʻlchamlari yaroqsiz.',
    'distinct' => ':attribute maydonida takroriy qiymat bor.',
    'doesnt_contain' => ':attribute maydonida quyidagilarning hech biri boʻlmasligi kerak: :values.',
    'doesnt_end_with' => ':attribute maydoni quyidagilardan biri bilan tugamasligi kerak: :values.',
    'doesnt_start_with' => ':attribute maydoni quyidagilardan biri bilan boshlanmasligi kerak: :values.',
    'email' => ':attribute maydoni toʻgʻri e-pochta manzili boʻlishi kerak.',
    'encoding' => ':attribute maydoni :encoding kodlashida boʻlishi kerak.',
    'ends_with' => ':attribute maydoni quyidagilardan biri bilan tugashi kerak: :values.',
    'enum' => 'Tanlangan :attribute yaroqsiz.',
    'exists' => 'Tanlangan :attribute yaroqsiz.',
    'extensions' => ':attribute maydoni quyidagi kengaytmalardan biriga ega boʻlishi kerak: :values.',
    'file' => ':attribute maydoni fayl boʻlishi kerak.',
    'filled' => ':attribute maydoni qiymatga ega boʻlishi kerak.',
    'gt' => [
        'array' => ':attribute maydonida :value tadan koʻp element boʻlishi kerak.',
        'file' => ':attribute maydoni :value kilobaytdan katta boʻlishi kerak.',
        'numeric' => ':attribute maydoni :value dan katta boʻlishi kerak.',
        'string' => ':attribute maydoni :value belgidan koʻp boʻlishi kerak.',
    ],
    'gte' => [
        'array' => ':attribute maydonida :value ta yoki undan koʻp element boʻlishi kerak.',
        'file' => ':attribute maydoni :value kilobayt yoki undan katta boʻlishi kerak.',
        'numeric' => ':attribute maydoni :value yoki undan katta boʻlishi kerak.',
        'string' => ':attribute maydoni :value belgi yoki undan koʻp boʻlishi kerak.',
    ],
    'hex_color' => ':attribute maydoni toʻgʻri oʻn oltilik rang boʻlishi kerak.',
    'image' => ':attribute maydoni rasm boʻlishi kerak.',
    'in' => 'Tanlangan :attribute yaroqsiz.',
    'in_array' => ':attribute maydoni :other ichida mavjud boʻlishi kerak.',
    'in_array_keys' => ':attribute maydoni quyidagi kalitlardan kamida bittasini oʻz ichiga olishi kerak: :values.',
    'integer' => ':attribute maydoni butun son boʻlishi kerak.',
    'ip' => ':attribute maydoni toʻgʻri IP manzil boʻlishi kerak.',
    'ipv4' => ':attribute maydoni toʻgʻri IPv4 manzil boʻlishi kerak.',
    'ipv6' => ':attribute maydoni toʻgʻri IPv6 manzil boʻlishi kerak.',
    'json' => ':attribute maydoni toʻgʻri JSON satri boʻlishi kerak.',
    'list' => ':attribute maydoni roʻyxat boʻlishi kerak.',
    'lowercase' => ':attribute maydoni kichik harflarda boʻlishi kerak.',
    'lt' => [
        'array' => ':attribute maydonida :value tadan kam element boʻlishi kerak.',
        'file' => ':attribute maydoni :value kilobaytdan kichik boʻlishi kerak.',
        'numeric' => ':attribute maydoni :value dan kichik boʻlishi kerak.',
        'string' => ':attribute maydoni :value belgidan kam boʻlishi kerak.',
    ],
    'lte' => [
        'array' => ':attribute maydonida :value tadan koʻp element boʻlmasligi kerak.',
        'file' => ':attribute maydoni :value kilobayt yoki undan kichik boʻlishi kerak.',
        'numeric' => ':attribute maydoni :value yoki undan kichik boʻlishi kerak.',
        'string' => ':attribute maydoni :value belgi yoki undan kam boʻlishi kerak.',
    ],
    'mac_address' => ':attribute maydoni toʻgʻri MAC manzil boʻlishi kerak.',
    'max' => [
        'array' => ':attribute maydonida :max tadan koʻp element boʻlmasligi kerak.',
        'file' => ':attribute maydoni :max kilobaytdan katta boʻlmasligi kerak.',
        'numeric' => ':attribute maydoni :max dan katta boʻlmasligi kerak.',
        'string' => ':attribute maydoni :max belgidan koʻp boʻlmasligi kerak.',
    ],
    'max_digits' => ':attribute maydonida :max xonadan koʻp boʻlmasligi kerak.',
    'mimes' => ':attribute maydoni quyidagi turdagi fayl boʻlishi kerak: :values.',
    'mimetypes' => ':attribute maydoni quyidagi turdagi fayl boʻlishi kerak: :values.',
    'min' => [
        'array' => ':attribute maydonida kamida :min ta element boʻlishi kerak.',
        'file' => ':attribute maydoni kamida :min kilobayt boʻlishi kerak.',
        'numeric' => ':attribute maydoni kamida :min boʻlishi kerak.',
        'string' => ':attribute maydoni kamida :min belgidan iborat boʻlishi kerak.',
    ],
    'min_digits' => ':attribute maydonida kamida :min xona boʻlishi kerak.',
    'missing' => ':attribute maydoni boʻlmasligi kerak.',
    'missing_if' => ':other :value boʻlganda :attribute maydoni boʻlmasligi kerak.',
    'missing_unless' => ':other :value boʻlmasa, :attribute maydoni boʻlmasligi kerak.',
    'missing_with' => ':values mavjud boʻlganda :attribute maydoni boʻlmasligi kerak.',
    'missing_with_all' => ':values mavjud boʻlganda :attribute maydoni boʻlmasligi kerak.',
    'multiple_of' => ':attribute maydoni :value ga karrali boʻlishi kerak.',
    'not_in' => 'Tanlangan :attribute yaroqsiz.',
    'not_regex' => ':attribute maydoni formati yaroqsiz.',
    'numeric' => ':attribute maydoni son boʻlishi kerak.',
    'password' => [
        'letters' => ':attribute maydonida kamida bitta harf boʻlishi kerak.',
        'mixed' => ':attribute maydonida kamida bitta katta va bitta kichik harf boʻlishi kerak.',
        'numbers' => ':attribute maydonida kamida bitta raqam boʻlishi kerak.',
        'symbols' => ':attribute maydonida kamida bitta maxsus belgi boʻlishi kerak.',
        'uncompromised' => 'Kiritilgan :attribute maʼlumotlar sizib chiqishida uchragan. Iltimos, boshqa :attribute tanlang.',
    ],
    'present' => ':attribute maydoni mavjud boʻlishi kerak.',
    'present_if' => ':other :value boʻlganda :attribute maydoni mavjud boʻlishi kerak.',
    'present_unless' => ':other :value boʻlmasa, :attribute maydoni mavjud boʻlishi kerak.',
    'present_with' => ':values mavjud boʻlganda :attribute maydoni mavjud boʻlishi kerak.',
    'present_with_all' => ':values mavjud boʻlganda :attribute maydoni mavjud boʻlishi kerak.',
    'prohibited' => ':attribute maydoni taqiqlangan.',
    'prohibited_if' => ':other :value boʻlganda :attribute maydoni taqiqlangan.',
    'prohibited_if_accepted' => ':other qabul qilinganda :attribute maydoni taqiqlangan.',
    'prohibited_if_declined' => ':other rad etilganda :attribute maydoni taqiqlangan.',
    'prohibited_unless' => ':other :values ichida boʻlmasa, :attribute maydoni taqiqlangan.',
    'prohibits' => ':attribute maydoni :other mavjud boʻlishini taqiqlaydi.',
    'regex' => ':attribute maydoni formati yaroqsiz.',
    'required' => ':attribute maydoni toʻldirilishi shart.',
    'required_array_keys' => ':attribute maydonida quyidagilar uchun yozuvlar boʻlishi kerak: :values.',
    'required_if' => ':other :value boʻlganda :attribute maydoni toʻldirilishi shart.',
    'required_if_accepted' => ':other qabul qilinganda :attribute maydoni toʻldirilishi shart.',
    'required_if_declined' => ':other rad etilganda :attribute maydoni toʻldirilishi shart.',
    'required_unless' => ':other :values ichida boʻlmasa, :attribute maydoni toʻldirilishi shart.',
    'required_with' => ':values mavjud boʻlganda :attribute maydoni toʻldirilishi shart.',
    'required_with_all' => ':values mavjud boʻlganda :attribute maydoni toʻldirilishi shart.',
    'required_without' => ':values mavjud boʻlmaganda :attribute maydoni toʻldirilishi shart.',
    'required_without_all' => ':values ning hech biri mavjud boʻlmaganda :attribute maydoni toʻldirilishi shart.',
    'same' => ':attribute maydoni :other bilan mos kelishi kerak.',
    'size' => [
        'array' => ':attribute maydonida :size ta element boʻlishi kerak.',
        'file' => ':attribute maydoni :size kilobayt boʻlishi kerak.',
        'numeric' => ':attribute maydoni :size boʻlishi kerak.',
        'string' => ':attribute maydoni :size belgidan iborat boʻlishi kerak.',
    ],
    'starts_with' => ':attribute maydoni quyidagilardan biri bilan boshlanishi kerak: :values.',
    'string' => ':attribute maydoni satr boʻlishi kerak.',
    'timezone' => ':attribute maydoni toʻgʻri vaqt mintaqasi boʻlishi kerak.',
    'unique' => ':attribute allaqachon band.',
    'uploaded' => ':attribute yuklanmadi.',
    'uppercase' => ':attribute maydoni katta harflarda boʻlishi kerak.',
    'url' => ':attribute maydoni toʻgʻri URL boʻlishi kerak.',
    'ulid' => ':attribute maydoni toʻgʻri ULID boʻlishi kerak.',
    'uuid' => ':attribute maydoni toʻgʻri UUID boʻlishi kerak.',

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
