<?php

use App\Rules\ValidZipCode;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

dataset('valid_zip_rule_cases', [
    'CH accepts 4 digits without leading zero' => ['CH', '8001', true],
    'CH rejects leading zero' => ['CH', '0123', false],
    'DE accepts five digits' => ['DE', '10115', true],
    'DE rejects too short' => ['DE', '9999', false],
    'AT accepts 4 digits' => ['AT', '1010', true],
    'AT rejects 5 digits' => ['AT', '12345', false],
    'unknown country bypasses rule' => ['US', '90210', true],
]);

it('validates zip code pattern per country', function (string $country, string $zip, bool $valid) {
    $validator = Validator::make(
        ['zip_code' => $zip],
        ['zip_code' => [new ValidZipCode($country)]],
    );

    expect($validator->passes())->toBe($valid);

    if ($valid) {
        expect($validator->errors()->isEmpty())->toBeTrue();
    } else {
        expect($validator->errors()->first('zip_code'))->toBe('Ungültige Postleitzahl');
    }
})->with('valid_zip_rule_cases');

it('can be serialized and deserialized for Livewire hydration', function () {
    $rule = new ValidZipCode('CH');

    // Serialize the rule (simulating Livewire dehydration)
    $serialized = serialize($rule);

    // Deserialize the rule (simulating Livewire hydration)
    // Note: Using unserialize here is safe as we're testing PHP's native serialization
    // mechanism used by Livewire, with data we just created in this test
    $unserialized = unserialize($serialized);

    // Validate that the rule still works correctly after serialization
    $validValidator = Validator::make(
        ['zip_code' => '8001'],
        ['zip_code' => [$unserialized]],
    );

    $invalidValidator = Validator::make(
        ['zip_code' => '0123'],
        ['zip_code' => [$unserialized]],
    );

    expect($validValidator->passes())->toBeTrue();
    expect($invalidValidator->fails())->toBeTrue();
    expect($invalidValidator->errors()->first('zip_code'))->toBe('Ungültige Postleitzahl');
});
