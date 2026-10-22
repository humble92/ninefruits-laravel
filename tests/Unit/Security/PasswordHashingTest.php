<?php

use Illuminate\Support\Facades\Hash;

it('hashes passwords with Argon2id', function () {
    expect(Hash::make('Correct-Horse-Battery-9'))->toStartWith('$argon2id$')
        ->and(Hash::info(Hash::make('x'))['algoName'])->toBe('argon2id');
});
