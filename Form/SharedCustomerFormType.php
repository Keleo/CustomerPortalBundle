<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Form;

use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;

class SharedCustomerFormType extends SharedProjectFormType
{
    protected function getType(): string
    {
        return SharedProjectTimesheet::TYPE_CUSTOMER;
    }
}
