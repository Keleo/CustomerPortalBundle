<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\tests\Entity;

use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet
 */
class SharedProjectTimesheetTest extends TestCase
{
    public function testBillableDefaults(): void
    {
        $portal = new SharedProjectTimesheet();

        self::assertTrue($portal->isNonBillableVisible());
        self::assertFalse($portal->isEntryBillableVisible());
        self::assertFalse($portal->hasBillableColumn());
    }

    public function testBillableColumnRequiresNonBillableVisible(): void
    {
        $portal = new SharedProjectTimesheet();
        $portal->setEntryBillableVisible(true);
        self::assertTrue($portal->hasBillableColumn());

        $portal->setNonBillableVisible(false);
        self::assertTrue($portal->isEntryBillableVisible());
        self::assertFalse($portal->hasBillableColumn());
    }
}
