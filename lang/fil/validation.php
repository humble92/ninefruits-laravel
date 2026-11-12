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

    'accepted' => 'Dapat tanggapin ang field na :attribute.',
    'accepted_if' => 'Dapat tanggapin ang field na :attribute kapag ang :other ay :value.',
    'active_url' => 'Dapat isang wastong URL ang field na :attribute.',
    'after' => 'Dapat isang petsang pagkatapos ng :date ang field na :attribute.',
    'after_or_equal' => 'Dapat isang petsang pagkatapos o katumbas ng :date ang field na :attribute.',
    'alpha' => 'Dapat mga titik lamang ang laman ng field na :attribute.',
    'alpha_dash' => 'Dapat mga titik, numero, gitling at underscore lamang ang laman ng field na :attribute.',
    'alpha_num' => 'Dapat mga titik at numero lamang ang laman ng field na :attribute.',
    'any_of' => 'Hindi wasto ang field na :attribute.',
    'array' => 'Dapat isang array ang field na :attribute.',
    'array_keys' => 'Dapat ang mga sumusunod na key lamang ang laman ng field na :attribute: :values.',
    'ascii' => 'Dapat mga single-byte na alphanumeric na character at simbolo lamang ang laman ng field na :attribute.',
    'base64' => 'Dapat isang wastong Base64 string ang field na :attribute.',
    'before' => 'Dapat isang petsang bago ang :date ang field na :attribute.',
    'before_or_equal' => 'Dapat isang petsang bago o katumbas ng :date ang field na :attribute.',
    'between' => [
        'array' => 'Dapat may :min hanggang :max item ang field na :attribute.',
        'file' => 'Dapat nasa pagitan ng :min at :max kilobyte ang field na :attribute.',
        'numeric' => 'Dapat nasa pagitan ng :min at :max ang field na :attribute.',
        'string' => 'Dapat may :min hanggang :max character ang field na :attribute.',
    ],
    'boolean' => 'Dapat true o false ang field na :attribute.',
    'can' => 'May hindi awtorisadong halaga ang field na :attribute.',
    'confirmed' => 'Hindi tugma ang kumpirmasyon ng field na :attribute.',
    'contains' => 'Kulang ng kinakailangang halaga ang field na :attribute.',
    'current_password' => 'Mali ang password.',
    'date' => 'Dapat isang wastong petsa ang field na :attribute.',
    'date_equals' => 'Dapat isang petsang katumbas ng :date ang field na :attribute.',
    'date_format' => 'Dapat tumugma sa format na :format ang field na :attribute.',
    'decimal' => 'Dapat may :decimal decimal place ang field na :attribute.',
    'declined' => 'Dapat tanggihan ang field na :attribute.',
    'declined_if' => 'Dapat tanggihan ang field na :attribute kapag ang :other ay :value.',
    'different' => 'Dapat magkaiba ang field na :attribute at ang :other.',
    'digits' => 'Dapat :digits digit ang field na :attribute.',
    'digits_between' => 'Dapat nasa pagitan ng :min at :max digit ang field na :attribute.',
    'dimensions' => 'Hindi wasto ang sukat ng larawan sa field na :attribute.',
    'distinct' => 'May duplicate na halaga ang field na :attribute.',
    'doesnt_contain' => 'Hindi dapat maglaman ang field na :attribute ng alinman sa mga sumusunod: :values.',
    'doesnt_end_with' => 'Hindi dapat magtapos ang field na :attribute sa alinman sa mga sumusunod: :values.',
    'doesnt_start_with' => 'Hindi dapat magsimula ang field na :attribute sa alinman sa mga sumusunod: :values.',
    'email' => 'Dapat isang wastong email address ang field na :attribute.',
    'encoding' => 'Dapat naka-encode sa :encoding ang field na :attribute.',
    'ends_with' => 'Dapat magtapos ang field na :attribute sa isa sa mga sumusunod: :values.',
    'enum' => 'Hindi wasto ang napiling :attribute.',
    'exists' => 'Hindi wasto ang napiling :attribute.',
    'extensions' => 'Dapat may isa sa mga sumusunod na extension ang field na :attribute: :values.',
    'file' => 'Dapat isang file ang field na :attribute.',
    'filled' => 'Dapat may halaga ang field na :attribute.',
    'gt' => [
        'array' => 'Dapat may higit sa :value item ang field na :attribute.',
        'file' => 'Dapat mas malaki sa :value kilobyte ang field na :attribute.',
        'numeric' => 'Dapat mas malaki sa :value ang field na :attribute.',
        'string' => 'Dapat mas mahaba sa :value character ang field na :attribute.',
    ],
    'gte' => [
        'array' => 'Dapat may :value item o higit pa ang field na :attribute.',
        'file' => 'Dapat mas malaki o katumbas ng :value kilobyte ang field na :attribute.',
        'numeric' => 'Dapat mas malaki o katumbas ng :value ang field na :attribute.',
        'string' => 'Dapat mas mahaba o katumbas ng :value character ang field na :attribute.',
    ],
    'hex_color' => 'Dapat isang wastong hexadecimal na kulay ang field na :attribute.',
    'image' => 'Dapat isang larawan ang field na :attribute.',
    'in' => 'Hindi wasto ang napiling :attribute.',
    'in_array' => 'Dapat umiiral sa :other ang field na :attribute.',
    'in_array_keys' => 'Dapat maglaman ang field na :attribute ng kahit isa sa mga sumusunod na key: :values.',
    'integer' => 'Dapat isang integer ang field na :attribute.',
    'ip' => 'Dapat isang wastong IP address ang field na :attribute.',
    'ipv4' => 'Dapat isang wastong IPv4 address ang field na :attribute.',
    'ipv6' => 'Dapat isang wastong IPv6 address ang field na :attribute.',
    'json' => 'Dapat isang wastong JSON string ang field na :attribute.',
    'list' => 'Dapat isang listahan ang field na :attribute.',
    'lowercase' => 'Dapat nasa maliliit na titik ang field na :attribute.',
    'lt' => [
        'array' => 'Dapat may mas kaunti sa :value item ang field na :attribute.',
        'file' => 'Dapat mas maliit sa :value kilobyte ang field na :attribute.',
        'numeric' => 'Dapat mas maliit sa :value ang field na :attribute.',
        'string' => 'Dapat mas maikli sa :value character ang field na :attribute.',
    ],
    'lte' => [
        'array' => 'Hindi dapat lumampas sa :value item ang field na :attribute.',
        'file' => 'Dapat mas maliit o katumbas ng :value kilobyte ang field na :attribute.',
        'numeric' => 'Dapat mas maliit o katumbas ng :value ang field na :attribute.',
        'string' => 'Dapat mas maikli o katumbas ng :value character ang field na :attribute.',
    ],
    'mac_address' => 'Dapat isang wastong MAC address ang field na :attribute.',
    'max' => [
        'array' => 'Hindi dapat lumampas sa :max item ang field na :attribute.',
        'file' => 'Hindi dapat lumampas sa :max kilobyte ang field na :attribute.',
        'numeric' => 'Hindi dapat lumampas sa :max ang field na :attribute.',
        'string' => 'Hindi dapat lumampas sa :max character ang field na :attribute.',
    ],
    'max_digits' => 'Hindi dapat lumampas sa :max digit ang field na :attribute.',
    'mimes' => 'Dapat isang file na may uring: :values ang field na :attribute.',
    'mimetypes' => 'Dapat isang file na may uring: :values ang field na :attribute.',
    'min' => [
        'array' => 'Dapat may hindi bababa sa :min item ang field na :attribute.',
        'file' => 'Dapat hindi bababa sa :min kilobyte ang field na :attribute.',
        'numeric' => 'Dapat hindi bababa sa :min ang field na :attribute.',
        'string' => 'Dapat hindi bababa sa :min character ang field na :attribute.',
    ],
    'min_digits' => 'Dapat may hindi bababa sa :min digit ang field na :attribute.',
    'missing' => 'Dapat wala ang field na :attribute.',
    'missing_if' => 'Dapat wala ang field na :attribute kapag ang :other ay :value.',
    'missing_unless' => 'Dapat wala ang field na :attribute maliban kung ang :other ay :value.',
    'missing_with' => 'Dapat wala ang field na :attribute kapag may :values.',
    'missing_with_all' => 'Dapat wala ang field na :attribute kapag may :values.',
    'multiple_of' => 'Dapat multiple ng :value ang field na :attribute.',
    'not_in' => 'Hindi wasto ang napiling :attribute.',
    'not_regex' => 'Hindi wasto ang format ng field na :attribute.',
    'numeric' => 'Dapat isang numero ang field na :attribute.',
    'password' => [
        'letters' => 'Dapat maglaman ang field na :attribute ng kahit isang titik.',
        'mixed' => 'Dapat maglaman ang field na :attribute ng kahit isang malaking titik at isang maliit na titik.',
        'numbers' => 'Dapat maglaman ang field na :attribute ng kahit isang numero.',
        'symbols' => 'Dapat maglaman ang field na :attribute ng kahit isang simbolo.',
        'uncompromised' => 'Lumabas na sa isang data leak ang ibinigay na :attribute. Pumili ng ibang :attribute.',
    ],
    'present' => 'Dapat naroroon ang field na :attribute.',
    'present_if' => 'Dapat naroroon ang field na :attribute kapag ang :other ay :value.',
    'present_unless' => 'Dapat naroroon ang field na :attribute maliban kung ang :other ay :value.',
    'present_with' => 'Dapat naroroon ang field na :attribute kapag may :values.',
    'present_with_all' => 'Dapat naroroon ang field na :attribute kapag may :values.',
    'prohibited' => 'Ipinagbabawal ang field na :attribute.',
    'prohibited_if' => 'Ipinagbabawal ang field na :attribute kapag ang :other ay :value.',
    'prohibited_if_accepted' => 'Ipinagbabawal ang field na :attribute kapag tinanggap ang :other.',
    'prohibited_if_declined' => 'Ipinagbabawal ang field na :attribute kapag tinanggihan ang :other.',
    'prohibited_unless' => 'Ipinagbabawal ang field na :attribute maliban kung ang :other ay nasa :values.',
    'prohibits' => 'Ipinagbabawal ng field na :attribute na naroroon ang :other.',
    'regex' => 'Hindi wasto ang format ng field na :attribute.',
    'required' => 'Kinakailangan ang field na :attribute.',
    'required_array_keys' => 'Dapat maglaman ang field na :attribute ng mga entry para sa: :values.',
    'required_if' => 'Kinakailangan ang field na :attribute kapag ang :other ay :value.',
    'required_if_accepted' => 'Kinakailangan ang field na :attribute kapag tinanggap ang :other.',
    'required_if_declined' => 'Kinakailangan ang field na :attribute kapag tinanggihan ang :other.',
    'required_unless' => 'Kinakailangan ang field na :attribute maliban kung ang :other ay nasa :values.',
    'required_with' => 'Kinakailangan ang field na :attribute kapag may :values.',
    'required_with_all' => 'Kinakailangan ang field na :attribute kapag may :values.',
    'required_without' => 'Kinakailangan ang field na :attribute kapag walang :values.',
    'required_without_all' => 'Kinakailangan ang field na :attribute kapag wala ang alinman sa :values.',
    'same' => 'Dapat tumugma ang field na :attribute sa :other.',
    'size' => [
        'array' => 'Dapat maglaman ang field na :attribute ng :size item.',
        'file' => 'Dapat :size kilobyte ang field na :attribute.',
        'numeric' => 'Dapat :size ang field na :attribute.',
        'string' => 'Dapat :size character ang field na :attribute.',
    ],
    'starts_with' => 'Dapat magsimula ang field na :attribute sa isa sa mga sumusunod: :values.',
    'string' => 'Dapat isang string ang field na :attribute.',
    'timezone' => 'Dapat isang wastong time zone ang field na :attribute.',
    'unique' => 'Nagamit na ang :attribute.',
    'uploaded' => 'Nabigong i-upload ang :attribute.',
    'uppercase' => 'Dapat nasa malalaking titik ang field na :attribute.',
    'url' => 'Dapat isang wastong URL ang field na :attribute.',
    'ulid' => 'Dapat isang wastong ULID ang field na :attribute.',
    'uuid' => 'Dapat isang wastong UUID ang field na :attribute.',

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
