<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserEmailNormalizationTest extends TestCase
{
    public function test_it_trims_and_lowercases_an_email(): void
    {
        $this->assertSame(
            'persona@empresa.com',
            User::normalizeEmail('  Persona@Empresa.COM  ')
        );
    }
}
