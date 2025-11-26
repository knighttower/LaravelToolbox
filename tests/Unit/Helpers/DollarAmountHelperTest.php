<?php

use Knighttower\Toolbox\Helpers\DollarAmountHelper;

test('isDollarAmount validates currency format correctly', function () {
    $helper = new DollarAmountHelper();

    // Valid currency formats
    expect($helper->isDollarAmount('$123.45'))->toBeTrue();
    expect($helper->isDollarAmount('$1,234.56'))->toBeTrue();
    expect($helper->isDollarAmount('$0.99'))->toBeTrue();
    expect($helper->isDollarAmount('$1,000,000.00'))->toBeTrue();

    // Invalid currency formats
    expect($helper->isDollarAmount('123.45'))->toBeFalse();
    expect($helper->isDollarAmount('abc'))->toBeFalse();
    expect($helper->isDollarAmount(''))->toBeFalse();
    // Don't test null as it causes deprecation warning
});

test('isDollarAmount with decimal mode validates numbers correctly', function () {
    $helper = new DollarAmountHelper();

    // Valid with decimal mode
    expect($helper->isDollarAmount('$123.45', true))->toBeTrue();
    expect($helper->isDollarAmount('123.45', true))->toBeTrue();
    expect($helper->isDollarAmount('1000', true))->toBeTrue();
    expect($helper->isDollarAmount('0', true))->toBeTrue();
    expect($helper->isDollarAmount('0.99', true))->toBeTrue();

    // Invalid with decimal mode
    expect($helper->isDollarAmount('-50', true))->toBeFalse();
    expect($helper->isDollarAmount('abc', true))->toBeFalse();
    expect($helper->isDollarAmount('', true))->toBeFalse();
});

test('removeSymbol extracts numeric values correctly', function () {
    $helper = new DollarAmountHelper();
    $removeSymbolMethod = new ReflectionMethod($helper, 'removeSymbol');
    $removeSymbolMethod->setAccessible(true);

    // Currency formats
    expect($removeSymbolMethod->invoke($helper, '$123.45'))->toBe('123.45');
    expect($removeSymbolMethod->invoke($helper, '$1,234.56'))->toBe('1234.56');
    expect($removeSymbolMethod->invoke($helper, '$ 1000'))->toBe('1000');

    // Already numeric
    expect($removeSymbolMethod->invoke($helper, '123.45'))->toBe('123.45');
    expect($removeSymbolMethod->invoke($helper, '1000'))->toBe('1000');
});

test('toCurrency formats amounts correctly with default USD', function () {
    $helper = new DollarAmountHelper();

    expect($helper->toCurrency('123.45'))->toBe('$123.45');
    expect($helper->toCurrency('1234.56'))->toBe('$1,234.56');
    expect($helper->toCurrency('1000000'))->toBe('$1,000,000.00');
    expect($helper->toCurrency('0.99'))->toBe('$0.99');

    // Already formatted input
    expect($helper->toCurrency('$1,234.56'))->toBe('$1,234.56');
});

test('toCurrency formats amounts correctly with different currencies', function () {
    $helper = new DollarAmountHelper();

    expect($helper->toCurrency('123.45', 'EUR'))->toContain('123.45');
    expect($helper->toCurrency('123.45', 'GBP'))->toContain('123.45');
    expect($helper->toCurrency('123.45', 'CAD'))->toContain('123.45');
});

test('toCurrency handles invalid input gracefully', function () {
    $helper = new DollarAmountHelper();

    expect($helper->toCurrency('invalid'))->toBe('invalid');
    expect($helper->toCurrency('abc123'))->toBe('abc123');
    expect($helper->toCurrency(''))->toBe('');
});

test('toDecimal converts currency to float correctly', function () {
    $helper = new DollarAmountHelper();
    $toDecimalMethod = new ReflectionMethod($helper, 'toDecimal');
    $toDecimalMethod->setAccessible(true);

    expect($toDecimalMethod->invoke($helper, '$123.45'))->toBe(123.45);
    expect($toDecimalMethod->invoke($helper, '$1,234.56'))->toBe(1234.56);
    expect($toDecimalMethod->invoke($helper, '1000'))->toBe(1000.0);
    expect($toDecimalMethod->invoke($helper, '0.99'))->toBe(0.99);
});

test('toDecimal throws exception for invalid input', function () {
    $helper = new DollarAmountHelper();
    $toDecimalMethod = new ReflectionMethod($helper, 'toDecimal');
    $toDecimalMethod->setAccessible(true);

    expect(fn() => $toDecimalMethod->invoke($helper, 'invalid'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn() => $toDecimalMethod->invoke($helper, 'abc123'))
        ->toThrow(InvalidArgumentException::class);
});

test('toString formats numbers into condensed format', function () {
    $helper = new DollarAmountHelper();

    // Small numbers (under 1000)
    expect($helper->toString('123.45'))->toBe('123.4');
    expect($helper->toString('$999.99'))->toBe('1,000.0'); // Laravel rounds to 1000

    // Thousands
    expect($helper->toString('1234'))->toBe('1.2K');
    expect($helper->toString('$1,500.00'))->toBe('1.5K');

    // Millions
    expect($helper->toString('1234567'))->toBe('1.2M');
    expect($helper->toString('$2,500,000'))->toBe('2.5M');

    // Billions
    expect($helper->toString('1234567890'))->toBe('1.2B');
});

test('toString handles invalid input gracefully', function () {
    $helper = new DollarAmountHelper();

    expect($helper->toString('invalid'))->toBe('invalid');
    expect($helper->toString('abc123'))->toBe('abc123');
    expect($helper->toString(''))->toBe('');
});

test('magic call method handles empty values correctly', function () {
    $helper = new DollarAmountHelper();

    // Empty values should return as-is
    expect($helper->toCurrency(''))->toBe('');
    expect($helper->toString(''))->toBe('');

    // Zero should be processed - Laravel Number::abbreviate returns '0.0' for zero
    expect($helper->toCurrency('0'))->toBe('$0.00');
    expect($helper->toString('0'))->toBe('0.0');
});

test('constructor sets locale correctly', function () {
    $helper = new DollarAmountHelper();
    
    // Test that locale-dependent functionality works
    expect($helper->toCurrency('1234.56'))->toBe('$1,234.56');
    expect($helper->isDollarAmount('$1,234.56'))->toBeTrue();
});

test('edge cases and boundary conditions', function () {
    $helper = new DollarAmountHelper();

    // Very small amounts
    expect($helper->toCurrency('0.01'))->toBe('$0.01');
    expect($helper->toString('0.01'))->toBe('0.0');

    // Large amounts - Laravel formats as '1,000.0B' not '1T'
    expect($helper->toString('999999999999'))->toBe('1,000.0B');
    
    // Zero handling
    expect($helper->isDollarAmount('$0.00'))->toBeTrue();
    expect($helper->isDollarAmount('0', true))->toBeTrue();
    expect($helper->toCurrency('0'))->toBe('$0.00');
});

test('method chaining and integration', function () {
    $helper = new DollarAmountHelper();

    // Test that methods work well together
    $original = '$1,234.56';
    $currency = $helper->toCurrency($original);
    $condensed = $helper->toString($original);

    expect($helper->isDollarAmount($original))->toBeTrue();
    expect($currency)->toBe('$1,234.56');
    expect($condensed)->toBe('1.2K');
});
