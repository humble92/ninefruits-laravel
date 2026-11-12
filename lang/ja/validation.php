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

    'accepted' => ':attribute を承認してください。',
    'accepted_if' => ':other が :value の場合、:attribute を承認してください。',
    'active_url' => ':attribute には有効な URL を指定してください。',
    'after' => ':attribute には :date より後の日付を指定してください。',
    'after_or_equal' => ':attribute には :date 以降の日付を指定してください。',
    'alpha' => ':attribute には英字のみ使用できます。',
    'alpha_dash' => ':attribute には英数字、ハイフン、アンダースコアのみ使用できます。',
    'alpha_num' => ':attribute には英数字のみ使用できます。',
    'any_of' => ':attribute が正しくありません。',
    'array' => ':attribute は配列にしてください。',
    'array_keys' => ':attribute には次のキーのみ含めることができます: :values',
    'ascii' => ':attribute には半角英数字と記号のみ使用できます。',
    'base64' => ':attribute には有効な Base64 文字列を指定してください。',
    'before' => ':attribute には :date より前の日付を指定してください。',
    'before_or_equal' => ':attribute には :date 以前の日付を指定してください。',
    'between' => [
        'array' => ':attribute の項目数は :min 個から :max 個にしてください。',
        'file' => ':attribute は :min KB から :max KB のファイルにしてください。',
        'numeric' => ':attribute は :min から :max の間で指定してください。',
        'string' => ':attribute は :min 文字から :max 文字で入力してください。',
    ],
    'boolean' => ':attribute には true か false を指定してください。',
    'can' => ':attribute に許可されていない値が含まれています。',
    'confirmed' => ':attribute と確認用の入力が一致しません。',
    'contains' => ':attribute に必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attribute には有効な日付を指定してください。',
    'date_equals' => ':attribute には :date と同じ日付を指定してください。',
    'date_format' => ':attribute は :format 形式で指定してください。',
    'decimal' => ':attribute は小数点以下 :decimal 桁で指定してください。',
    'declined' => ':attribute を拒否してください。',
    'declined_if' => ':other が :value の場合、:attribute を拒否してください。',
    'different' => ':attribute と :other には異なる値を指定してください。',
    'digits' => ':attribute は :digits 桁で指定してください。',
    'digits_between' => ':attribute は :min 桁から :max 桁で指定してください。',
    'dimensions' => ':attribute の画像サイズが正しくありません。',
    'distinct' => ':attribute に重複した値があります。',
    'doesnt_contain' => ':attribute には次のいずれも含めないでください: :values',
    'doesnt_end_with' => ':attribute の末尾には次のいずれも使用できません: :values',
    'doesnt_start_with' => ':attribute の先頭には次のいずれも使用できません: :values',
    'email' => ':attribute には有効なメールアドレスを指定してください。',
    'encoding' => ':attribute は :encoding でエンコードしてください。',
    'ends_with' => ':attribute の末尾は次のいずれかにしてください: :values',
    'enum' => '選択された :attribute は正しくありません。',
    'exists' => '選択された :attribute は正しくありません。',
    'extensions' => ':attribute の拡張子は次のいずれかにしてください: :values',
    'file' => ':attribute にはファイルを指定してください。',
    'filled' => ':attribute に値を入力してください。',
    'gt' => [
        'array' => ':attribute の項目数は :value 個より多くしてください。',
        'file' => ':attribute は :value KB より大きいファイルにしてください。',
        'numeric' => ':attribute には :value より大きい値を指定してください。',
        'string' => ':attribute は :value 文字より多く入力してください。',
    ],
    'gte' => [
        'array' => ':attribute の項目数は :value 個以上にしてください。',
        'file' => ':attribute は :value KB 以上のファイルにしてください。',
        'numeric' => ':attribute には :value 以上の値を指定してください。',
        'string' => ':attribute は :value 文字以上で入力してください。',
    ],
    'hex_color' => ':attribute には有効な 16 進数カラーコードを指定してください。',
    'image' => ':attribute には画像を指定してください。',
    'in' => '選択された :attribute は正しくありません。',
    'in_array' => ':attribute は :other に含まれている必要があります。',
    'in_array_keys' => ':attribute には次のキーのうち少なくとも 1 つを含めてください: :values',
    'integer' => ':attribute には整数を指定してください。',
    'ip' => ':attribute には有効な IP アドレスを指定してください。',
    'ipv4' => ':attribute には有効な IPv4 アドレスを指定してください。',
    'ipv6' => ':attribute には有効な IPv6 アドレスを指定してください。',
    'json' => ':attribute には有効な JSON 文字列を指定してください。',
    'list' => ':attribute はリストにしてください。',
    'lowercase' => ':attribute は小文字で入力してください。',
    'lt' => [
        'array' => ':attribute の項目数は :value 個より少なくしてください。',
        'file' => ':attribute は :value KB より小さいファイルにしてください。',
        'numeric' => ':attribute には :value より小さい値を指定してください。',
        'string' => ':attribute は :value 文字より少なく入力してください。',
    ],
    'lte' => [
        'array' => ':attribute の項目数は :value 個以下にしてください。',
        'file' => ':attribute は :value KB 以下のファイルにしてください。',
        'numeric' => ':attribute には :value 以下の値を指定してください。',
        'string' => ':attribute は :value 文字以下で入力してください。',
    ],
    'mac_address' => ':attribute には有効な MAC アドレスを指定してください。',
    'max' => [
        'array' => ':attribute の項目数は :max 個以下にしてください。',
        'file' => ':attribute は :max KB 以下のファイルにしてください。',
        'numeric' => ':attribute には :max 以下の値を指定してください。',
        'string' => ':attribute は :max 文字以下で入力してください。',
    ],
    'max_digits' => ':attribute は :max 桁以下で指定してください。',
    'mimes' => ':attribute には次の形式のファイルを指定してください: :values',
    'mimetypes' => ':attribute には次の形式のファイルを指定してください: :values',
    'min' => [
        'array' => ':attribute の項目数は :min 個以上にしてください。',
        'file' => ':attribute は :min KB 以上のファイルにしてください。',
        'numeric' => ':attribute には :min 以上の値を指定してください。',
        'string' => ':attribute は :min 文字以上で入力してください。',
    ],
    'min_digits' => ':attribute は :min 桁以上で指定してください。',
    'missing' => ':attribute は含めないでください。',
    'missing_if' => ':other が :value の場合、:attribute は含めないでください。',
    'missing_unless' => ':other が :value でない場合、:attribute は含めないでください。',
    'missing_with' => ':values がある場合、:attribute は含めないでください。',
    'missing_with_all' => ':values がすべてある場合、:attribute は含めないでください。',
    'multiple_of' => ':attribute には :value の倍数を指定してください。',
    'not_in' => '選択された :attribute は正しくありません。',
    'not_regex' => ':attribute の形式が正しくありません。',
    'numeric' => ':attribute には数値を指定してください。',
    'password' => [
        'letters' => ':attribute には英字を 1 文字以上含めてください。',
        'mixed' => ':attribute には英大文字と英小文字をそれぞれ 1 文字以上含めてください。',
        'numbers' => ':attribute には数字を 1 文字以上含めてください。',
        'symbols' => ':attribute には記号を 1 文字以上含めてください。',
        'uncompromised' => '入力された :attribute はデータ漏洩で確認されています。別の :attribute を指定してください。',
    ],
    'present' => ':attribute が存在している必要があります。',
    'present_if' => ':other が :value の場合、:attribute が存在している必要があります。',
    'present_unless' => ':other が :value でない場合、:attribute が存在している必要があります。',
    'present_with' => ':values がある場合、:attribute が存在している必要があります。',
    'present_with_all' => ':values がすべてある場合、:attribute が存在している必要があります。',
    'prohibited' => ':attribute は入力できません。',
    'prohibited_if' => ':other が :value の場合、:attribute は入力できません。',
    'prohibited_if_accepted' => ':other を承認した場合、:attribute は入力できません。',
    'prohibited_if_declined' => ':other を拒否した場合、:attribute は入力できません。',
    'prohibited_unless' => ':other が :values に含まれない場合、:attribute は入力できません。',
    'prohibits' => ':attribute がある場合、:other は入力できません。',
    'regex' => ':attribute の形式が正しくありません。',
    'required' => ':attribute は必須です。',
    'required_array_keys' => ':attribute には次の項目を含めてください: :values',
    'required_if' => ':other が :value の場合、:attribute は必須です。',
    'required_if_accepted' => ':other を承認した場合、:attribute は必須です。',
    'required_if_declined' => ':other を拒否した場合、:attribute は必須です。',
    'required_unless' => ':other が :values に含まれない場合、:attribute は必須です。',
    'required_with' => ':values がある場合、:attribute は必須です。',
    'required_with_all' => ':values がすべてある場合、:attribute は必須です。',
    'required_without' => ':values がない場合、:attribute は必須です。',
    'required_without_all' => ':values がいずれもない場合、:attribute は必須です。',
    'same' => ':attribute と :other が一致しません。',
    'size' => [
        'array' => ':attribute の項目数は :size 個にしてください。',
        'file' => ':attribute は :size KB のファイルにしてください。',
        'numeric' => ':attribute には :size を指定してください。',
        'string' => ':attribute は :size 文字で入力してください。',
    ],
    'starts_with' => ':attribute の先頭は次のいずれかにしてください: :values',
    'string' => ':attribute には文字列を指定してください。',
    'timezone' => ':attribute には有効なタイムゾーンを指定してください。',
    'unique' => ':attribute はすでに使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'uppercase' => ':attribute は大文字で入力してください。',
    'url' => ':attribute には有効な URL を指定してください。',
    'ulid' => ':attribute には有効な ULID を指定してください。',
    'uuid' => ':attribute には有効な UUID を指定してください。',

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
