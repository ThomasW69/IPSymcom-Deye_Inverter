<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class LibraryValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateDRS210C(): void
    {
        $this->validateModule(__DIR__ . '/../SUNxxKSG04LP3');
    }

}