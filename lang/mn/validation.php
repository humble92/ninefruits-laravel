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

    'accepted' => ':attribute талбарыг зөвшөөрсөн байх ёстой.',
    'accepted_if' => ':other нь :value үед :attribute талбарыг зөвшөөрсөн байх ёстой.',
    'active_url' => ':attribute талбар хүчинтэй URL байх ёстой.',
    'after' => ':attribute талбар :date-ээс хойшхи огноо байх ёстой.',
    'after_or_equal' => ':attribute талбар :date буюу түүнээс хойшхи огноо байх ёстой.',
    'alpha' => ':attribute талбар зөвхөн үсэг агуулах ёстой.',
    'alpha_dash' => ':attribute талбар зөвхөн үсэг, тоо, зураас, доогуур зураас агуулах ёстой.',
    'alpha_num' => ':attribute талбар зөвхөн үсэг, тоо агуулах ёстой.',
    'any_of' => ':attribute талбар буруу байна.',
    'array' => ':attribute талбар массив байх ёстой.',
    'array_keys' => ':attribute талбар зөвхөн дараах түлхүүрүүдийг агуулах ёстой: :values.',
    'ascii' => ':attribute талбар зөвхөн нэг байтын үсэг, тоо, тэмдэгт агуулах ёстой.',
    'base64' => ':attribute талбар хүчинтэй Base64 мөр байх ёстой.',
    'before' => ':attribute талбар :date-ээс өмнөх огноо байх ёстой.',
    'before_or_equal' => ':attribute талбар :date буюу түүнээс өмнөх огноо байх ёстой.',
    'between' => [
        'array' => ':attribute талбар :min-ээс :max хүртэлх элементтэй байх ёстой.',
        'file' => ':attribute талбар :min-ээс :max килобайт байх ёстой.',
        'numeric' => ':attribute талбар :min-ээс :max хооронд байх ёстой.',
        'string' => ':attribute талбар :min-ээс :max тэмдэгттэй байх ёстой.',
    ],
    'boolean' => ':attribute талбар үнэн эсвэл худал байх ёстой.',
    'can' => ':attribute талбар зөвшөөрөгдөөгүй утга агуулж байна.',
    'confirmed' => ':attribute талбарын баталгаажуулалт таарахгүй байна.',
    'contains' => ':attribute талбарт шаардлагатай утга дутуу байна.',
    'current_password' => 'Нууц үг буруу байна.',
    'date' => ':attribute талбар хүчинтэй огноо байх ёстой.',
    'date_equals' => ':attribute талбар :date-тэй тэнцүү огноо байх ёстой.',
    'date_format' => ':attribute талбар :format форматтай таарах ёстой.',
    'decimal' => ':attribute талбар :decimal бутархай оронтой байх ёстой.',
    'declined' => ':attribute талбарыг татгалзсан байх ёстой.',
    'declined_if' => ':other нь :value үед :attribute талбарыг татгалзсан байх ёстой.',
    'different' => ':attribute талбар ба :other ялгаатай байх ёстой.',
    'digits' => ':attribute талбар :digits оронтой байх ёстой.',
    'digits_between' => ':attribute талбар :min-ээс :max оронтой байх ёстой.',
    'dimensions' => ':attribute талбарын зургийн хэмжээ буруу байна.',
    'distinct' => ':attribute талбарт давхардсан утга байна.',
    'doesnt_contain' => ':attribute талбар дараахын алийг нь ч агуулах ёсгүй: :values.',
    'doesnt_end_with' => ':attribute талбар дараахын аль нэгээр төгсөх ёсгүй: :values.',
    'doesnt_start_with' => ':attribute талбар дараахын аль нэгээр эхлэх ёсгүй: :values.',
    'email' => ':attribute талбар хүчинтэй и-мэйл хаяг байх ёстой.',
    'encoding' => ':attribute талбар :encoding кодчилолтой байх ёстой.',
    'ends_with' => ':attribute талбар дараахын аль нэгээр төгсөх ёстой: :values.',
    'enum' => 'Сонгосон :attribute буруу байна.',
    'exists' => 'Сонгосон :attribute буруу байна.',
    'extensions' => ':attribute талбар дараах өргөтгөлийн аль нэгтэй байх ёстой: :values.',
    'file' => ':attribute талбар файл байх ёстой.',
    'filled' => ':attribute талбар утгатай байх ёстой.',
    'gt' => [
        'array' => ':attribute талбар :value-ээс олон элементтэй байх ёстой.',
        'file' => ':attribute талбар :value килобайтаас их байх ёстой.',
        'numeric' => ':attribute талбар :value-ээс их байх ёстой.',
        'string' => ':attribute талбар :value тэмдэгтээс урт байх ёстой.',
    ],
    'gte' => [
        'array' => ':attribute талбар :value буюу түүнээс олон элементтэй байх ёстой.',
        'file' => ':attribute талбар :value килобайт буюу түүнээс их байх ёстой.',
        'numeric' => ':attribute талбар :value буюу түүнээс их байх ёстой.',
        'string' => ':attribute талбар :value тэмдэгт буюу түүнээс урт байх ёстой.',
    ],
    'hex_color' => ':attribute талбар хүчинтэй арван зургаатын өнгө байх ёстой.',
    'image' => ':attribute талбар зураг байх ёстой.',
    'in' => 'Сонгосон :attribute буруу байна.',
    'in_array' => ':attribute талбар :other-д байх ёстой.',
    'in_array_keys' => ':attribute талбар дараах түлхүүрүүдийн дор хаяж нэгийг агуулах ёстой: :values.',
    'integer' => ':attribute талбар бүхэл тоо байх ёстой.',
    'ip' => ':attribute талбар хүчинтэй IP хаяг байх ёстой.',
    'ipv4' => ':attribute талбар хүчинтэй IPv4 хаяг байх ёстой.',
    'ipv6' => ':attribute талбар хүчинтэй IPv6 хаяг байх ёстой.',
    'json' => ':attribute талбар хүчинтэй JSON мөр байх ёстой.',
    'list' => ':attribute талбар жагсаалт байх ёстой.',
    'lowercase' => ':attribute талбар жижиг үсгээр байх ёстой.',
    'lt' => [
        'array' => ':attribute талбар :value-ээс цөөн элементтэй байх ёстой.',
        'file' => ':attribute талбар :value килобайтаас бага байх ёстой.',
        'numeric' => ':attribute талбар :value-ээс бага байх ёстой.',
        'string' => ':attribute талбар :value тэмдэгтээс богино байх ёстой.',
    ],
    'lte' => [
        'array' => ':attribute талбар :value-ээс олон элементгүй байх ёстой.',
        'file' => ':attribute талбар :value килобайт буюу түүнээс бага байх ёстой.',
        'numeric' => ':attribute талбар :value буюу түүнээс бага байх ёстой.',
        'string' => ':attribute талбар :value тэмдэгт буюу түүнээс богино байх ёстой.',
    ],
    'mac_address' => ':attribute талбар хүчинтэй MAC хаяг байх ёстой.',
    'max' => [
        'array' => ':attribute талбар :max-аас олон элементгүй байх ёстой.',
        'file' => ':attribute талбар :max килобайтаас их байж болохгүй.',
        'numeric' => ':attribute талбар :max-аас их байж болохгүй.',
        'string' => ':attribute талбар :max тэмдэгтээс урт байж болохгүй.',
    ],
    'max_digits' => ':attribute талбар :max-аас олон оронтой байж болохгүй.',
    'mimes' => ':attribute талбар дараах төрлийн файл байх ёстой: :values.',
    'mimetypes' => ':attribute талбар дараах төрлийн файл байх ёстой: :values.',
    'min' => [
        'array' => ':attribute талбар дор хаяж :min элементтэй байх ёстой.',
        'file' => ':attribute талбар дор хаяж :min килобайт байх ёстой.',
        'numeric' => ':attribute талбар дор хаяж :min байх ёстой.',
        'string' => ':attribute талбар дор хаяж :min тэмдэгттэй байх ёстой.',
    ],
    'min_digits' => ':attribute талбар дор хаяж :min оронтой байх ёстой.',
    'missing' => ':attribute талбар байх ёсгүй.',
    'missing_if' => ':other нь :value үед :attribute талбар байх ёсгүй.',
    'missing_unless' => ':other нь :value биш бол :attribute талбар байх ёсгүй.',
    'missing_with' => ':values байгаа үед :attribute талбар байх ёсгүй.',
    'missing_with_all' => ':values байгаа үед :attribute талбар байх ёсгүй.',
    'multiple_of' => ':attribute талбар :value-ийн үржвэр байх ёстой.',
    'not_in' => 'Сонгосон :attribute буруу байна.',
    'not_regex' => ':attribute талбарын формат буруу байна.',
    'numeric' => ':attribute талбар тоо байх ёстой.',
    'password' => [
        'letters' => ':attribute талбар дор хаяж нэг үсэг агуулах ёстой.',
        'mixed' => ':attribute талбар дор хаяж нэг том, нэг жижиг үсэг агуулах ёстой.',
        'numbers' => ':attribute талбар дор хаяж нэг тоо агуулах ёстой.',
        'symbols' => ':attribute талбар дор хаяж нэг тэмдэгт агуулах ёстой.',
        'uncompromised' => 'Оруулсан :attribute мэдээлэл алдагдсан жагсаалтад байна. Өөр :attribute сонгоно уу.',
    ],
    'present' => ':attribute талбар байх ёстой.',
    'present_if' => ':other нь :value үед :attribute талбар байх ёстой.',
    'present_unless' => ':other нь :value биш бол :attribute талбар байх ёстой.',
    'present_with' => ':values байгаа үед :attribute талбар байх ёстой.',
    'present_with_all' => ':values байгаа үед :attribute талбар байх ёстой.',
    'prohibited' => ':attribute талбарыг хориглоно.',
    'prohibited_if' => ':other нь :value үед :attribute талбарыг хориглоно.',
    'prohibited_if_accepted' => ':other зөвшөөрөгдсөн үед :attribute талбарыг хориглоно.',
    'prohibited_if_declined' => ':other татгалзагдсан үед :attribute талбарыг хориглоно.',
    'prohibited_unless' => ':other нь :values-д байхгүй бол :attribute талбарыг хориглоно.',
    'prohibits' => ':attribute талбар байгаа үед :other байж болохгүй.',
    'regex' => ':attribute талбарын формат буруу байна.',
    'required' => ':attribute талбарыг заавал бөглөнө.',
    'required_array_keys' => ':attribute талбар дараах утгуудыг агуулах ёстой: :values.',
    'required_if' => ':other нь :value үед :attribute талбарыг заавал бөглөнө.',
    'required_if_accepted' => ':other зөвшөөрөгдсөн үед :attribute талбарыг заавал бөглөнө.',
    'required_if_declined' => ':other татгалзагдсан үед :attribute талбарыг заавал бөглөнө.',
    'required_unless' => ':other нь :values-д байхгүй бол :attribute талбарыг заавал бөглөнө.',
    'required_with' => ':values байгаа үед :attribute талбарыг заавал бөглөнө.',
    'required_with_all' => ':values байгаа үед :attribute талбарыг заавал бөглөнө.',
    'required_without' => ':values байхгүй үед :attribute талбарыг заавал бөглөнө.',
    'required_without_all' => ':values-ийн аль нь ч байхгүй үед :attribute талбарыг заавал бөглөнө.',
    'same' => ':attribute талбар :other-тэй таарах ёстой.',
    'size' => [
        'array' => ':attribute талбар :size элемент агуулах ёстой.',
        'file' => ':attribute талбар :size килобайт байх ёстой.',
        'numeric' => ':attribute талбар :size байх ёстой.',
        'string' => ':attribute талбар :size тэмдэгттэй байх ёстой.',
    ],
    'starts_with' => ':attribute талбар дараахын аль нэгээр эхлэх ёстой: :values.',
    'string' => ':attribute талбар тэмдэгт мөр байх ёстой.',
    'timezone' => ':attribute талбар хүчинтэй цагийн бүс байх ёстой.',
    'unique' => ':attribute аль хэдийн бүртгэгдсэн байна.',
    'uploaded' => ':attribute-ийг хуулж чадсангүй.',
    'uppercase' => ':attribute талбар том үсгээр байх ёстой.',
    'url' => ':attribute талбар хүчинтэй URL байх ёстой.',
    'ulid' => ':attribute талбар хүчинтэй ULID байх ёстой.',
    'uuid' => ':attribute талбар хүчинтэй UUID байх ёстой.',

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
