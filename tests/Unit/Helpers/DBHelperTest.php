<?php

use Knighttower\Toolbox\Helpers\DBHelper;

// Note: Database-dependent methods (getEnum, hasColumns, getTableColumns) are not tested here
// as they require complex database setup and mocking. They should be tested in integration tests
// with actual database connections.

test('keysToDbFormat converts camelCase to snake_case', function () {
    $input = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailAddress' => 'john@example.com',
        'phoneNumber' => '123-456-7890',
        'isActive' => true
    ];
    
    $result = DBHelper::keysToDbFormat($input);
    
    $expected = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john@example.com',
        'phone_number' => '123-456-7890',
        'is_active' => true
    ];
    
    expect($result)->toBe($expected);
});

test('keysToDbFormat handles empty array', function () {
    $result = DBHelper::keysToDbFormat([]);
    expect($result)->toBe([]);
});

test('keysToDbFormat handles collection input', function () {
    $input = collect([
        'firstName' => 'Jane',
        'lastName' => 'Smith'
    ]);
    
    $result = DBHelper::keysToDbFormat($input);
    
    expect($result)->toBe([
        'first_name' => 'Jane',
        'last_name' => 'Smith'
    ]);
});

test('keysToUserFormat converts snake_case to camelCase', function () {
    $input = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john@example.com',
        'phone_number' => '123-456-7890',
        'is_active' => true
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    $expected = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailAddress' => 'john@example.com',
        'phoneNumber' => '123-456-7890',
        'isActive' => true
    ];
    
    expect($result)->toBe($expected);
});

test('keysToUserFormat handles nested arrays recursively', function () {
    $input = [
        'user_data' => [
            'first_name' => 'John',
            'contact_info' => [
                'email_address' => 'john@example.com',
                'phone_number' => '123-456-7890'
            ]
        ]
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    $expected = [
        'userData' => [
            'firstName' => 'John',
            'contactInfo' => [
                'emailAddress' => 'john@example.com',
                'phoneNumber' => '123-456-7890'
            ]
        ]
    ];
    
    expect($result)->toBe($expected);
});

test('keysToUserFormat preserves object type when input is object', function () {
    $input = (object) [
        'first_name' => 'John',
        'last_name' => 'Doe'
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    expect($result)->toBeObject();
    expect($result->firstName)->toBe('John');
    expect($result->lastName)->toBe('Doe');
});

test('keysToUserFormat handles nested objects recursively', function () {
    $input = (object) [
        'user_data' => (object) [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    expect($result)->toBeObject();
    expect($result->userData)->toBeObject();
    expect($result->userData->firstName)->toBe('John');
    expect($result->userData->lastName)->toBe('Doe');
});

test('setFilters converts simple key-value pairs to Laravel filter format', function () {
    $input = [
        'firstName' => 'John',
        'isActive' => true,
        'age' => 25
    ];
    
    $result = DBHelper::setFilters($input);
    
    $expected = [
        ['first_name', 'John'],
        ['is_active', true],
        ['age', 25]
    ];
    
    expect($result)->toBe($expected);
});

test('setFilters converts operator-value arrays to Laravel filter format', function () {
    $input = [
        'age' => ['>', 18],
        'salary' => ['between', [30000, 80000]],
        'name' => ['like', 'John%']
    ];
    
    $result = DBHelper::setFilters($input);
    
    $expected = [
        ['age', '>', 18],
        ['salary', 'between', [30000, 80000]],
        ['name', 'like', 'John%']
    ];
    
    expect($result)->toBe($expected);
});

test('setFilters converts camelCase keys to snake_case', function () {
    $input = [
        'firstName' => 'John',
        'emailAddress' => ['like', '%@example.com'],
        'isActive' => ['=', true]
    ];
    
    $result = DBHelper::setFilters($input);
    
    $expected = [
        ['first_name', 'John'],
        ['email_address', 'like', '%@example.com'],
        ['is_active', '=', true]
    ];
    
    expect($result)->toBe($expected);
});

test('setFilters handles empty array', function () {
    expect(fn() => DBHelper::setFilters([]))
        ->toThrow(\Exception::class, 'Filters must be a Simple Associative Array Key:pair Values');
});

test('setFilters throws exception for non-associative array', function () {
    $input = ['value1', 'value2', 'value3'];
    
    expect(fn() => DBHelper::setFilters($input))
        ->toThrow(\Exception::class, 'Filters must be a Simple Associative Array Key:pair Values');
});

test('setFilters handles malformed operator-value arrays gracefully', function () {
    $input = [
        'name' => 'John',
        'age' => ['>', ''], // Empty value
        'status' => [''] // Missing operator and value
    ];
    
    $result = DBHelper::setFilters($input);
    
    // Should create simple filters for malformed arrays
    $expected = [
        ['name', 'John'],
        ['age', ['>', '']], // Treated as simple value since validation fails
        ['status', ['']]    // Treated as simple value since validation fails
    ];
    
    expect($result)->toBe($expected);
});

test('integration test - full workflow with mixed data formats', function () {
    // Simulate converting user input to DB format and creating filters
    $userInput = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailAddress' => ['like', '%@example.com'],
        'isActive' => true,
        'createdAt' => ['>=', '2023-01-01']
    ];
    
    // Convert to filters (which includes key conversion)
    $filters = DBHelper::setFilters($userInput);
    
    $expectedFilters = [
        ['first_name', 'John'],
        ['last_name', 'Doe'],
        ['email_address', 'like', '%@example.com'],
        ['is_active', true],
        ['created_at', '>=', '2023-01-01']
    ];
    
    expect($filters)->toBe($expectedFilters);
    
    // Test reverse conversion for response
    $dbResponse = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_address' => 'john@example.com',
        'is_active' => true
    ];
    
    $userResponse = DBHelper::keysToUserFormat($dbResponse);
    
    $expectedUserResponse = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailAddress' => 'john@example.com',
        'isActive' => true
    ];
    
    expect($userResponse)->toBe($expectedUserResponse);
});

test('static methods work without instantiation', function () {
    // Test the utility methods that don't require database connections
    expect(DBHelper::keysToDbFormat(['firstName' => 'test']))->toBe(['first_name' => 'test']);
    expect(DBHelper::keysToUserFormat(['first_name' => 'test']))->toBe(['firstName' => 'test']);
    expect(DBHelper::setFilters(['name' => 'test']))->toBe([['name', 'test']]);
});

// Database-dependent method tests would require integration testing
test('database methods exist and are callable', function () {
    // Just verify the methods exist and are callable
    expect(method_exists(DBHelper::class, 'getEnum'))->toBeTrue();
    expect(method_exists(DBHelper::class, 'hasColumns'))->toBeTrue();
    expect(method_exists(DBHelper::class, 'getTableColumns'))->toBeTrue();
    
    // Verify they are static methods
    $reflection = new ReflectionClass(DBHelper::class);
    expect($reflection->getMethod('getEnum')->isStatic())->toBeTrue();
    expect($reflection->getMethod('hasColumns')->isStatic())->toBeTrue();
    expect($reflection->getMethod('getTableColumns')->isStatic())->toBeTrue();
});

test('keysToDbFormat handles special characters and edge cases', function () {
    $input = [
        'firstName' => 'John',
        'HTML' => '<p>content</p>',
        'XMLHttpRequest' => 'ajax',
        'userId' => 123,
        'isActiveUser' => true,
        'createdAt' => '2023-01-01',
        'someVeryLongVariableNameThatShouldBeConverted' => 'value'
    ];
    
    $result = DBHelper::keysToDbFormat($input);
    
    $expected = [
        'first_name' => 'John',
        'h_t_m_l' => '<p>content</p>',
        'x_m_l_http_request' => 'ajax',
        'user_id' => 123,
        'is_active_user' => true,
        'created_at' => '2023-01-01',
        'some_very_long_variable_name_that_should_be_converted' => 'value'
    ];
    
    expect($result)->toBe($expected);
});

test('keysToUserFormat handles special database column patterns', function () {
    $input = [
        'user_id' => 123,
        'created_at' => '2023-01-01',
        'updated_at' => '2023-01-02',
        'is_active' => true,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email_verified_at' => '2023-01-01 12:00:00'
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    $expected = [
        'userId' => 123,
        'createdAt' => '2023-01-01',
        'updatedAt' => '2023-01-02',
        'isActive' => true,
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailVerifiedAt' => '2023-01-01 12:00:00'
    ];
    
    expect($result)->toBe($expected);
});

test('setFilters handles complex nested filter scenarios', function () {
    $input = [
        'userName' => 'john_doe',
        'createdAt' => ['>=', '2023-01-01'],
        'status' => ['in', ['active', 'pending']],
        'score' => ['between', [50, 100]],
        'emailAddress' => ['like', '%@example.com']
    ];
    
    $result = DBHelper::setFilters($input);
    
    $expected = [
        ['user_name', 'john_doe'],
        ['created_at', '>=', '2023-01-01'],
        ['status', 'in', ['active', 'pending']],
        ['score', 'between', [50, 100]],
        ['email_address', 'like', '%@example.com']
    ];
    
    expect($result)->toBe($expected);
});

test('setFilters handles edge cases with null and boolean values', function () {
    $input = [
        'isActive' => true,
        'isDeleted' => false,
        'description' => null,
        'count' => 0,
        'emptyString' => ''
    ];
    
    $result = DBHelper::setFilters($input);
    
    $expected = [
        ['is_active', true],
        ['is_deleted', false],
        ['description', null],
        ['count', 0],
        ['empty_string', '']
    ];
    
    expect($result)->toBe($expected);
});

test('keysToUserFormat with deeply nested structures', function () {
    $input = [
        'user_profile' => [
            'personal_info' => [
                'first_name' => 'John',
                'contact_details' => [
                    'email_address' => 'john@example.com',
                    'phone_number' => '123-456-7890'
                ]
            ],
            'preferences' => [
                'email_notifications' => true,
                'push_notifications' => false
            ]
        ]
    ];
    
    $result = DBHelper::keysToUserFormat($input);
    
    $expected = [
        'userProfile' => [
            'personalInfo' => [
                'firstName' => 'John',
                'contactDetails' => [
                    'emailAddress' => 'john@example.com',
                    'phoneNumber' => '123-456-7890'
                ]
            ],
            'preferences' => [
                'emailNotifications' => true,
                'pushNotifications' => false
            ]
        ]
    ];
    
    expect($result)->toBe($expected);
});

test('mixed type conversions maintain data integrity', function () {
    // Test that conversion doesn't alter data values, only keys (without objects for exact comparison)
    $originalData = [
        'user_id' => 12345,
        'amount' => 99.99,
        'is_active' => true,
        'tags' => ['php', 'laravel', 'testing'],
        'created_at' => '2023-01-01 12:00:00'
    ];
    
    // Convert to user format then back to DB format
    $userFormat = DBHelper::keysToUserFormat($originalData);
    $backToDbFormat = DBHelper::keysToDbFormat($userFormat);
    
    // Should get back to original structure (keys wise)
    expect($backToDbFormat)->toBe($originalData);
    
    // Values should remain unchanged
    expect($userFormat['userId'])->toBe(12345);
    expect($userFormat['amount'])->toBe(99.99);
    expect($userFormat['isActive'])->toBe(true);
    expect($userFormat['tags'])->toBe(['php', 'laravel', 'testing']);
    expect($userFormat['createdAt'])->toBe('2023-01-01 12:00:00');
    
    // Test object handling separately
    $dataWithObject = ['metadata' => (object) ['key' => 'value']];
    $convertedWithObject = DBHelper::keysToUserFormat($dataWithObject);
    expect($convertedWithObject['metadata'])->toBeObject();
    expect($convertedWithObject['metadata']->key)->toBe('value');
});

test('class provides proper public API', function () {
    $reflection = new ReflectionClass(DBHelper::class);
    
    // Check that all expected public methods exist
    $expectedMethods = [
        'getEnum',
        'hasColumns', 
        'getTableColumns',
        'keysToDbFormat',
        'keysToUserFormat',
        'setFilters'
    ];
    
    foreach ($expectedMethods as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
        expect($reflection->getMethod($method)->isPublic())->toBeTrue();
        expect($reflection->getMethod($method)->isStatic())->toBeTrue();
    }
    
    // Verify class is not instantiable (all static methods)
    expect($reflection->getConstructor())->toBeNull();
});
