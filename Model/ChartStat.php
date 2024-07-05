<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;

class ChartStat
{
    private int $duration;
    private float $rate;

    /**
     * @param array{'duration': int, 'rate': float}|null $resultRow
     */
    public function __construct(?array $resultRow = null)
    {
        $this->duration = (int) ($resultRow !== null && isset($resultRow['duration']) ? $resultRow['duration'] : 0);
        $this->rate = (float) ($resultRow !== null && isset($resultRow['rate']) ? $resultRow['rate'] : 0.0);
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
