<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\tests\Model;

use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;
use PHPUnit\Framework\TestCase;

class RecordMergeModeTest extends TestCase
{
    public function testSize(): void
    {
        self::assertCount(4, RecordMergeMode::getModes());
    }
}
