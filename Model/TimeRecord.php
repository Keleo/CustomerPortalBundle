<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Model;

use App\Entity\Timesheet;
use App\Entity\User;

/**
 * Class to represent the view time records.
 */
class TimeRecord
{
    /** @var array<string> */
    public const VALID_MERGE_MODES = [
        RecordMergeMode::MODE_MERGE,
        RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY,
        RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY,
    ];

    public static function fromTimesheet(Timesheet $timesheet, string $mergeMode = RecordMergeMode::MODE_MERGE): TimeRecord
    {
        if (!\in_array($mergeMode, self::VALID_MERGE_MODES)) {
            throw new \InvalidArgumentException("Invalid merge mode given: $mergeMode");
        }

        $record = new TimeRecord($timesheet->getBegin(), $timesheet->getUser(), $mergeMode);
        $record->addTimesheet($timesheet);

        return $record;
    }

    private ?\DateTimeInterface $date = null;
    private ?string $description = null;
    /**
     * @var array<array{'hourlyRate': float, 'duration': int}>>
     */
    private array $hourlyRates = [];
    private float $rate = 0.0;
    private int $duration = 0;
    private ?User $user = null;
    private ?string $mergeMode = null;

    private function __construct(\DateTimeInterface $date, User $user, string $mergeMode)
    {
        $this->date = $date;
        $this->user = $user;
        $this->mergeMode = $mergeMode;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<array{'hourlyRate': float, 'duration': int}>>
     */
    public function getHourlyRates(): array
    {
        return $this->hourlyRates;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    // Helper methods

    public function hasDifferentHourlyRates(): bool
    {
        return \count($this->hourlyRates) > 1;
    }

    public function addTimesheet(Timesheet $timesheet): void
    {
        $this->addHourlyRate($timesheet->getHourlyRate(), $timesheet->getDuration());
        $this->addRate($timesheet->getRate());
        $this->addDuration($timesheet->getDuration());
        $this->setDescription($timesheet);
    }

    protected function addHourlyRate(?float $hourlyRate, ?int $duration): void
    {
        if ($hourlyRate > 0 && $duration > 0) {
            $entryIndex = null;
            foreach ($this->hourlyRates as $index => $info) {
                if ($info['hourlyRate'] === $hourlyRate) {
                    $entryIndex = $index;
                    break;
                }
            }

            if ($entryIndex === null) {
                $this->hourlyRates[] = [
                    'hourlyRate' => $hourlyRate,
                    'duration' => $duration,
                ];
            } else {
                $this->hourlyRates[$entryIndex]['duration'] += $duration;
            }
        }
    }

    private function addRate(?float $rate): void
    {
        if ($rate !== null) {
            $this->rate += $rate;
        }
    }

    private function addDuration(?int $duration): void
    {
        if ($duration !== null) {
            $this->duration += $duration;
        }
    }

    protected function setDescription(Timesheet $timesheet): void
    {
        $description = $timesheet->getDescription();

        // Merge description dependent on record merge mode
        if ($this->description === null) {
            $this->description = $description;
        } elseif ($this->mergeMode === RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY && $this->getDate() < $timesheet->getBegin()) {
            // Override description on last
            $this->description = $timesheet->getDescription();
        } elseif ($this->mergeMode === RecordMergeMode::MODE_MERGE) {
            // MODE_MERGE
            if ($description !== null && \strlen($description) > 0) {
                $this->description = (
                    implode(PHP_EOL, [
                        $this->getDescription(),
                        $description
                    ])
                );
            }
        }
    }
}
