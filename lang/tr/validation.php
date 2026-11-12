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

    'accepted' => ':Attribute kabul edilmelidir.',
    'accepted_if' => ':Other :value olduğunda :attribute kabul edilmelidir.',
    'active_url' => ':Attribute geçerli bir URL olmalıdır.',
    'after' => ':Attribute :date tarihinden sonra bir tarih olmalıdır.',
    'after_or_equal' => ':Attribute :date tarihi veya sonrası bir tarih olmalıdır.',
    'alpha' => ':Attribute yalnızca harf içermelidir.',
    'alpha_dash' => ':Attribute yalnızca harf, rakam, tire ve alt çizgi içermelidir.',
    'alpha_num' => ':Attribute yalnızca harf ve rakam içermelidir.',
    'any_of' => ':Attribute geçersiz.',
    'array' => ':Attribute bir dizi olmalıdır.',
    'array_keys' => ':Attribute yalnızca şu anahtarları içermelidir: :values.',
    'ascii' => ':Attribute yalnızca tek baytlık alfasayısal karakterler ve semboller içermelidir.',
    'base64' => ':Attribute geçerli bir Base64 dizesi olmalıdır.',
    'before' => ':Attribute :date tarihinden önce bir tarih olmalıdır.',
    'before_or_equal' => ':Attribute :date tarihi veya öncesi bir tarih olmalıdır.',
    'between' => [
        'array' => ':Attribute :min ile :max arasında öğe içermelidir.',
        'file' => ':Attribute :min ile :max kilobayt arasında olmalıdır.',
        'numeric' => ':Attribute :min ile :max arasında olmalıdır.',
        'string' => ':Attribute :min ile :max karakter arasında olmalıdır.',
    ],
    'boolean' => ':Attribute doğru veya yanlış olmalıdır.',
    'can' => ':Attribute yetkisiz bir değer içeriyor.',
    'confirmed' => ':Attribute onayı eşleşmiyor.',
    'contains' => ':Attribute gerekli bir değeri içermiyor.',
    'current_password' => 'Parola yanlış.',
    'date' => ':Attribute geçerli bir tarih olmalıdır.',
    'date_equals' => ':Attribute :date ile aynı tarih olmalıdır.',
    'date_format' => ':Attribute :format biçimiyle eşleşmelidir.',
    'decimal' => ':Attribute :decimal ondalık basamaklı olmalıdır.',
    'declined' => ':Attribute reddedilmelidir.',
    'declined_if' => ':Other :value olduğunda :attribute reddedilmelidir.',
    'different' => ':Attribute ile :other farklı olmalıdır.',
    'digits' => ':Attribute :digits haneli olmalıdır.',
    'digits_between' => ':Attribute :min ile :max hane arasında olmalıdır.',
    'dimensions' => ':Attribute geçersiz görsel boyutlarına sahip.',
    'distinct' => ':Attribute yinelenen bir değere sahip.',
    'doesnt_contain' => ':Attribute şunlardan hiçbirini içermemelidir: :values.',
    'doesnt_end_with' => ':Attribute şunlardan biriyle bitmemelidir: :values.',
    'doesnt_start_with' => ':Attribute şunlardan biriyle başlamamalıdır: :values.',
    'email' => ':Attribute geçerli bir e-posta adresi olmalıdır.',
    'encoding' => ':Attribute :encoding ile kodlanmış olmalıdır.',
    'ends_with' => ':Attribute şunlardan biriyle bitmelidir: :values.',
    'enum' => 'Seçilen :attribute geçersiz.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'extensions' => ':Attribute şu uzantılardan birine sahip olmalıdır: :values.',
    'file' => ':Attribute bir dosya olmalıdır.',
    'filled' => ':Attribute bir değere sahip olmalıdır.',
    'gt' => [
        'array' => ':Attribute :value öğeden fazla içermelidir.',
        'file' => ':Attribute :value kilobayttan büyük olmalıdır.',
        'numeric' => ':Attribute :value değerinden büyük olmalıdır.',
        'string' => ':Attribute :value karakterden uzun olmalıdır.',
    ],
    'gte' => [
        'array' => ':Attribute :value veya daha fazla öğe içermelidir.',
        'file' => ':Attribute :value kilobayt veya daha büyük olmalıdır.',
        'numeric' => ':Attribute :value veya daha büyük olmalıdır.',
        'string' => ':Attribute :value karakter veya daha uzun olmalıdır.',
    ],
    'hex_color' => ':Attribute geçerli bir onaltılık renk olmalıdır.',
    'image' => ':Attribute bir görsel olmalıdır.',
    'in' => 'Seçilen :attribute geçersiz.',
    'in_array' => ':Attribute :other içinde bulunmalıdır.',
    'in_array_keys' => ':Attribute şu anahtarlardan en az birini içermelidir: :values.',
    'integer' => ':Attribute bir tam sayı olmalıdır.',
    'ip' => ':Attribute geçerli bir IP adresi olmalıdır.',
    'ipv4' => ':Attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6' => ':Attribute geçerli bir IPv6 adresi olmalıdır.',
    'json' => ':Attribute geçerli bir JSON dizesi olmalıdır.',
    'list' => ':Attribute bir liste olmalıdır.',
    'lowercase' => ':Attribute küçük harf olmalıdır.',
    'lt' => [
        'array' => ':Attribute :value öğeden az içermelidir.',
        'file' => ':Attribute :value kilobayttan küçük olmalıdır.',
        'numeric' => ':Attribute :value değerinden küçük olmalıdır.',
        'string' => ':Attribute :value karakterden kısa olmalıdır.',
    ],
    'lte' => [
        'array' => ':Attribute :value öğeden fazla içermemelidir.',
        'file' => ':Attribute :value kilobayt veya daha küçük olmalıdır.',
        'numeric' => ':Attribute :value veya daha küçük olmalıdır.',
        'string' => ':Attribute :value karakter veya daha kısa olmalıdır.',
    ],
    'mac_address' => ':Attribute geçerli bir MAC adresi olmalıdır.',
    'max' => [
        'array' => ':Attribute en fazla :max öğe içerebilir.',
        'file' => ':Attribute :max kilobayttan büyük olmamalıdır.',
        'numeric' => ':Attribute :max değerinden büyük olmamalıdır.',
        'string' => ':Attribute :max karakterden uzun olmamalıdır.',
    ],
    'max_digits' => ':Attribute en fazla :max haneli olmalıdır.',
    'mimes' => ':Attribute şu türde bir dosya olmalıdır: :values.',
    'mimetypes' => ':Attribute şu türde bir dosya olmalıdır: :values.',
    'min' => [
        'array' => ':Attribute en az :min öğe içermelidir.',
        'file' => ':Attribute en az :min kilobayt olmalıdır.',
        'numeric' => ':Attribute en az :min olmalıdır.',
        'string' => ':Attribute en az :min karakter olmalıdır.',
    ],
    'min_digits' => ':Attribute en az :min haneli olmalıdır.',
    'missing' => ':Attribute bulunmamalıdır.',
    'missing_if' => ':Other :value olduğunda :attribute bulunmamalıdır.',
    'missing_unless' => ':Other :value olmadıkça :attribute bulunmamalıdır.',
    'missing_with' => ':Values mevcut olduğunda :attribute bulunmamalıdır.',
    'missing_with_all' => ':Values mevcut olduğunda :attribute bulunmamalıdır.',
    'multiple_of' => ':Attribute :value değerinin katı olmalıdır.',
    'not_in' => 'Seçilen :attribute geçersiz.',
    'not_regex' => ':Attribute biçimi geçersiz.',
    'numeric' => ':Attribute bir sayı olmalıdır.',
    'password' => [
        'letters' => ':Attribute en az bir harf içermelidir.',
        'mixed' => ':Attribute en az bir büyük harf ve bir küçük harf içermelidir.',
        'numbers' => ':Attribute en az bir rakam içermelidir.',
        'symbols' => ':Attribute en az bir sembol içermelidir.',
        'uncompromised' => 'Girilen :attribute bir veri sızıntısında yer almış. Lütfen farklı bir :attribute seçin.',
    ],
    'present' => ':Attribute mevcut olmalıdır.',
    'present_if' => ':Other :value olduğunda :attribute mevcut olmalıdır.',
    'present_unless' => ':Other :value olmadıkça :attribute mevcut olmalıdır.',
    'present_with' => ':Values mevcut olduğunda :attribute mevcut olmalıdır.',
    'present_with_all' => ':Values mevcut olduğunda :attribute mevcut olmalıdır.',
    'prohibited' => ':Attribute alanı yasaktır.',
    'prohibited_if' => ':Other :value olduğunda :attribute alanı yasaktır.',
    'prohibited_if_accepted' => ':Other kabul edildiğinde :attribute alanı yasaktır.',
    'prohibited_if_declined' => ':Other reddedildiğinde :attribute alanı yasaktır.',
    'prohibited_unless' => ':Other :values içinde olmadıkça :attribute alanı yasaktır.',
    'prohibits' => ':Attribute alanı :other alanının bulunmasını yasaklar.',
    'regex' => ':Attribute biçimi geçersiz.',
    'required' => ':Attribute alanı zorunludur.',
    'required_array_keys' => ':Attribute şunlar için girdi içermelidir: :values.',
    'required_if' => ':Other :value olduğunda :attribute alanı zorunludur.',
    'required_if_accepted' => ':Other kabul edildiğinde :attribute alanı zorunludur.',
    'required_if_declined' => ':Other reddedildiğinde :attribute alanı zorunludur.',
    'required_unless' => ':Other :values içinde olmadıkça :attribute alanı zorunludur.',
    'required_with' => ':Values mevcut olduğunda :attribute alanı zorunludur.',
    'required_with_all' => ':Values mevcut olduğunda :attribute alanı zorunludur.',
    'required_without' => ':Values mevcut olmadığında :attribute alanı zorunludur.',
    'required_without_all' => ':Values değerlerinin hiçbiri mevcut olmadığında :attribute alanı zorunludur.',
    'same' => ':Attribute ile :other eşleşmelidir.',
    'size' => [
        'array' => ':Attribute :size öğe içermelidir.',
        'file' => ':Attribute :size kilobayt olmalıdır.',
        'numeric' => ':Attribute :size olmalıdır.',
        'string' => ':Attribute :size karakter olmalıdır.',
    ],
    'starts_with' => ':Attribute şunlardan biriyle başlamalıdır: :values.',
    'string' => ':Attribute bir metin olmalıdır.',
    'timezone' => ':Attribute geçerli bir saat dilimi olmalıdır.',
    'unique' => ':Attribute zaten kullanılıyor.',
    'uploaded' => ':Attribute yüklenemedi.',
    'uppercase' => ':Attribute büyük harf olmalıdır.',
    'url' => ':Attribute geçerli bir URL olmalıdır.',
    'ulid' => ':Attribute geçerli bir ULID olmalıdır.',
    'uuid' => ':Attribute geçerli bir UUID olmalıdır.',

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
