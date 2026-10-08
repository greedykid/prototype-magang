<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_initials_excludes_hyphens_and_symbols(): void
    {
        $user = new User(['name' => 'Rizki - PIC Lab Utama']);
        $this->assertSame('RP', $user->initials);

        $user2 = new User(['name' => 'Rizki -']);
        $this->assertSame('RI', $user2->initials);

        $user3 = new User(['name' => 'Jean-Luc Picard']);
        $this->assertSame('JL', $user3->initials);

        $user4 = new User(['name' => 'Dr. Sarah Connor, M.Kom']);
        $this->assertSame('DS', $user4->initials);

        $user5 = new User(['name' => 'Rizki']);
        $this->assertSame('RI', $user5->initials);

        $user6 = new User(['name' => 'R']);
        $this->assertSame('R', $user6->initials);

        $user7 = new User(['name' => '---']);
        $this->assertSame('U', $user7->initials);

        $user8 = new User(['name' => '']);
        $this->assertSame('U', $user8->initials);

        $user9 = new User(['name' => 'Élise Dupont']);
        $this->assertSame('ÉD', $user9->initials);
    }
}
