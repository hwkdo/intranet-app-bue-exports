<?php

declare(strict_types=1);

use Hwkdo\IntranetAppBueExports\Support\ExportTypeValidator;
use Illuminate\Validation\ValidationException;

test('validate excluded columns parses comma separated input', function () {
    expect(ExportTypeValidator::validateExcludedColumns('BETRAG, intern_id'))
        ->toBe(['BETRAG', 'INTERN_ID']);
});

test('validate excluded columns rejects invalid column names', function () {
    expect(fn () => ExportTypeValidator::validateExcludedColumns('BETRAG, invalid-name'))
        ->toThrow(ValidationException::class);
});
