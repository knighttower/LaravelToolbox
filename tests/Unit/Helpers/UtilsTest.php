<?php

use Knighttower\Toolbox\Helpers\Utils;
use Illuminate\Support\Collection;

test('parseCsvFromString parses simple CSV data correctly', function () {
    $csvString = "name,email,age\nJohn Doe,john@example.com,30\nJane Smith,jane@example.com,25";
    
    $result = Utils::parseCsvFromString($csvString);
    
    $expected = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => '30'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => '25']
    ];
    
    expect($result)->toBe($expected);
});

test('parseCsvFromString handles CSV with quoted fields', function () {
    $csvString = "name,description,price\n\"Product A\",\"A great product\",\"$19.99\"\n\"Product B\",\"Another product, with comma\",\"$29.99\"";
    
    $result = Utils::parseCsvFromString($csvString);
    
    $expected = [
        ['name' => 'Product A', 'description' => 'A great product', 'price' => '$19.99'],
        ['name' => 'Product B', 'description' => 'Another product, with comma', 'price' => '$29.99']
    ];
    
    expect($result)->toBe($expected);
});

test('parseCsvFromString handles empty CSV data', function () {
    $csvString = '';
    
    $result = Utils::parseCsvFromString($csvString);
    
    expect($result)->toBe([]);
});

test('parseCsvFromString handles CSV with only headers', function () {
    $csvString = 'name,email,age';
    
    $result = Utils::parseCsvFromString($csvString);
    
    expect($result)->toBe([]);
});

test('parseCsvFromString skips empty rows', function () {
    $csvString = "name,email,age\nJohn Doe,john@example.com,30\n,,\nJane Smith,jane@example.com,25\n,\"\",\n";
    
    $result = Utils::parseCsvFromString($csvString);
    
    $expected = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => '30'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => '25']
    ];
    
    expect($result)->toBe($expected);
});

test('parseCsvFromString handles missing values in rows', function () {
    $csvString = "name,email,age\nJohn Doe,john@example.com\nJane Smith,jane@example.com,25,extra";
    
    $result = Utils::parseCsvFromString($csvString);
    
    $expected = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => null],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => '25']
    ];
    
    expect($result)->toBe($expected);
});

test('parseCsvFromString handles CSV with special characters', function () {
    $csvString = "name,description\n\"John's Product\",\"A \"\"special\"\" item\"\n\"Émile's Store\",\"Café & Más\"";
    
    $result = Utils::parseCsvFromString($csvString);
    
    $expected = [
        ['name' => "John's Product", 'description' => 'A "special" item'],
        ['name' => "Émile's Store", 'description' => 'Café & Más']
    ];
    
    expect($result)->toBe($expected);
});

test('mapDataFromPointers maps simple pointer paths', function () {
    $mapping = [
        'userName' => 'user.name',
        'userEmail' => 'user.email',
        'userAge' => 'user.age'
    ];
    
    $sourceData = [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ]
    ];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    $expected = [
        'userName' => 'John Doe',
        'userEmail' => 'john@example.com',
        'userAge' => 30
    ];
    
    expect($result)->toBe($expected);
});

test('mapDataFromPointers handles deep nested paths', function () {
    $mapping = [
        'companyName' => 'user.profile.company.name',
        'phoneNumber' => 'user.contact.phone.primary',
        'street' => 'user.address.home.street'
    ];
    
    $sourceData = [
        'user' => [
            'profile' => [
                'company' => ['name' => 'Acme Corp']
            ],
            'contact' => [
                'phone' => ['primary' => '123-456-7890']
            ],
            'address' => [
                'home' => ['street' => '123 Main St']
            ]
        ]
    ];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    $expected = [
        'companyName' => 'Acme Corp',
        'phoneNumber' => '123-456-7890',
        'street' => '123 Main St'
    ];
    
    expect($result)->toBe($expected);
});

test('mapDataFromPointers handles missing paths gracefully', function () {
    $mapping = [
        'name' => 'user.name',
        'email' => 'user.email',
        'phone' => 'user.contact.phone', // This path doesn't exist
        'age' => 'user.profile.age'     // This path doesn't exist
    ];
    
    $sourceData = [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]
    ];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    $expected = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => null,
        'age' => null
    ];
    
    expect($result)->toBe($expected);
});

test('mapDataFromPointers handles empty mapping', function () {
    $mapping = [];
    $sourceData = ['user' => ['name' => 'John']];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    expect($result)->toBe([]);
});

test('mapDataFromPointers handles empty source data', function () {
    $mapping = ['name' => 'user.name'];
    $sourceData = [];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    expect($result)->toBe(['name' => null]);
});

test('mapDataFromPointers handles root level mappings', function () {
    $mapping = [
        'title' => 'title',
        'status' => 'status'
    ];
    
    $sourceData = [
        'title' => 'Test Title',
        'status' => 'active',
        'other' => 'ignored'
    ];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    $expected = [
        'title' => 'Test Title',
        'status' => 'active'
    ];
    
    expect($result)->toBe($expected);
});

test('toArray converts array input unchanged', function () {
    $data = ['name' => 'John', 'age' => 30];
    
    $result = Utils::toArray($data);
    
    expect($result)->toBe($data);
});

test('toArray converts Laravel Collection to array', function () {
    $collection = new Collection(['name' => 'John', 'age' => 30]);
    
    $result = Utils::toArray($collection);
    
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
});

test('toArray converts object to array', function () {
    $object = (object) ['name' => 'John', 'age' => 30];
    
    $result = Utils::toArray($object);
    
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
});

test('toArray converts JSON string to array', function () {
    $jsonString = '{"name":"John","age":30}';
    
    $result = Utils::toArray($jsonString);
    
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
});

test('toArray converts JSON array string to array', function () {
    $jsonString = '["apple","banana","orange"]';
    
    $result = Utils::toArray($jsonString);
    
    expect($result)->toBe(['apple', 'banana', 'orange']);
});

test('toArray converts non-JSON string to single-element array', function () {
    $string = 'Hello World';
    
    $result = Utils::toArray($string);
    
    expect($result)->toBe(['Hello World']);
});

test('toArray converts invalid JSON string to single-element array', function () {
    $invalidJson = '{"name":John,"age":}';
    
    $result = Utils::toArray($invalidJson);
    
    expect($result)->toBe(['{"name":John,"age":}']);
});

test('toArray handles null input', function () {
    $result = Utils::toArray(null);
    
    expect($result)->toBe([]);
});

test('toArray handles numeric input', function () {
    $result = Utils::toArray(123);
    
    expect($result)->toBe([]);
});

test('toArray handles boolean input', function () {
    expect(Utils::toArray(true))->toBe([]);
    expect(Utils::toArray(false))->toBe([]);
});

test('toArray converts complex nested object to array', function () {
    $object = (object) [
        'user' => (object) [
            'name' => 'John',
            'profile' => (object) ['age' => 30]
        ]
    ];
    
    $result = Utils::toArray($object);
    
    // Note: This will be a shallow conversion, nested objects remain as objects
    expect($result)->toHaveKey('user');
    expect($result['user'])->toBeObject();
    expect($result['user']->name)->toBe('John');
});

test('CSV parsing with different line endings', function () {
    // Test with Windows line endings (\r\n)
    $csvString = "name,age\r\nJohn,30\r\nJane,25";
    
    $result = Utils::parseCsvFromString($csvString);
    
    expect($result)->toBe([
        ['name' => 'John', 'age' => '30'],
        ['name' => 'Jane', 'age' => '25']
    ]);
});

test('mapDataFromPointers handles numeric keys in path', function () {
    $mapping = [
        'firstItem' => 'items.0.name',
        'secondPrice' => 'items.1.price'
    ];
    
    $sourceData = [
        'items' => [
            0 => ['name' => 'Item 1', 'price' => 10],
            1 => ['name' => 'Item 2', 'price' => 20]
        ]
    ];
    
    $result = Utils::mapDataFromPointers($mapping, $sourceData);
    
    $expected = [
        'firstItem' => 'Item 1',
        'secondPrice' => 20
    ];
    
    expect($result)->toBe($expected);
});

test('integration test - CSV parsing with data mapping', function () {
    // Simulate parsing CSV and then mapping the data
    $csvString = "full_name,email_address,birth_date\nJohn Doe,john@example.com,1990-01-01\nJane Smith,jane@example.com,1985-05-15";
    
    $parsedData = Utils::parseCsvFromString($csvString);
    
    // Map each row to a different structure
    $mappedData = [];
    foreach ($parsedData as $row) {
        $mapping = [
            'name' => 'full_name',
            'email' => 'email_address',
            'birthDate' => 'birth_date'
        ];
        $mappedData[] = Utils::mapDataFromPointers($mapping, $row);
    }
    
    $expected = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'birthDate' => '1990-01-01'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'birthDate' => '1985-05-15']
    ];
    
    expect($mappedData)->toBe($expected);
});

test('static methods work without instantiation', function () {
    // Verify all methods are static and callable
    expect(Utils::parseCsvFromString("name\nJohn"))->toBe([['name' => 'John']]);
    expect(Utils::mapDataFromPointers(['name' => 'name'], ['name' => 'John']))->toBe(['name' => 'John']);
    expect(Utils::toArray(['test']))->toBe(['test']);
});

test('class architecture verification', function () {
    $reflection = new ReflectionClass(Utils::class);
    
    // Verify all expected methods exist and are static
    $expectedMethods = ['parseCsvFromString', 'mapDataFromPointers', 'toArray'];
    
    foreach ($expectedMethods as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
        expect($reflection->getMethod($method)->isStatic())->toBeTrue();
        expect($reflection->getMethod($method)->isPublic())->toBeTrue();
    }
    
    // Verify class has no constructor (utility class)
    expect($reflection->getConstructor())->toBeNull();
});
