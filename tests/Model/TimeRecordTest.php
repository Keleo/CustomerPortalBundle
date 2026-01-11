<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\tests\Model;

use App\Entity\Timesheet;
use App\Entity\User;
use DateTime;
use KimaiPlugin\CustomerPortalBundle\Model\RecordMergeMode;
use KimaiPlugin\CustomerPortalBundle\Model\TimeRecord;
use PHPUnit\Framework\TestCase;

/**
 * @covers \KimaiPlugin\CustomerPortalBundle\Model\TimeRecord
 */
class TimeRecordTest extends TestCase
{
    /**
     * Creates a valid timesheet record from the given parameters.
     * @param DateTime $date date of the timesheet
     * @param User $user user of the timesheet
     * @param float $hourlyRate hour rate
     * @param int $duration duration in seconds
     * @param string $description record description
     * @return Timesheet
     */
    private static function createTimesheet(DateTime $date, User $user, float $hourlyRate, int $duration, ?string $description): Timesheet
    {
        $t = new Timesheet();
        $t->setBegin($date);
        $t->setUser($user);
        $t->setHourlyRate($hourlyRate);
        $t->setRate($hourlyRate * $duration / 60 / 60);
        $t->setDuration($duration);
        $t->setDescription($description);

        return $t;
    }

    public function testInvalidTimesheet(): void
    {
        $this->expectErrorMessage('null given');
        TimeRecord::fromTimesheet(new Timesheet());
    }

    public function testValidEmptyTimesheet(): void
    {
        $begin = new DateTime();
        $user = new User();

        $timeRecord = TimeRecord::fromTimesheet(
            self::createTimesheet($begin, $user, 0, 0, null)
        );

        self::assertNotNull($timeRecord);
        self::assertEquals($begin, $timeRecord->getDate());
        self::assertEquals($user, $timeRecord->getUser());
        self::assertNull($timeRecord->getDescription());
        self::assertEquals(0.0, $timeRecord->getRate());
        self::assertEquals(0, $timeRecord->getDuration());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals([], $timeRecord->getHourlyRates());
    }

    public function testValidFilledTimesheet(): void
    {
        $hours = 2.1;
        $hourlyRate = 123.456;
        $rate = $hours * $hourlyRate;
        $duration = $hours * 60 * 60;
        $description = 'description';

        $timeRecord = TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), $hourlyRate, $duration, $description)
        );

        self::assertEquals($duration, $timeRecord->getDuration());
        self::assertEquals($rate, $timeRecord->getRate());
        self::assertEquals($description, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($duration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeNull(): void
    {
        $this->expectException(\TypeError::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            null
        );
    }

    public function testMergeModeNone(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            RecordMergeMode::MODE_NONE
        );
    }

    public function testMergeModeRandom(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TimeRecord::fromTimesheet(
            self::createTimesheet(new DateTime(), new User(), 0, 0, null),
            uniqid()
        );
    }

    public function testMergeModeDefaultSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription . PHP_EOL . $secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeUseFirstSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeUseLastSameRate(): void
    {
        $hourlyRate = 123.456;

        $firstRecordHours = 2.1;
        $firstRecordRate = $firstRecordHours * $hourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordRate = $secondRecordHours * $hourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $hourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(false, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($hourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
    }

    public function testMergeModeDefaultDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription . PHP_EOL . $secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }

    public function testMergeModeUseFirstDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($firstRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }

    public function testMergeModeUseLastDifferentRate(): void
    {
        $firstRecordHours = 2.1;
        $firstRecordHourlyRate = 123.456;
        $firstRecordRate = $firstRecordHours * $firstRecordHourlyRate;
        $firstRecordDuration = $firstRecordHours * 60 * 60;
        $firstRecordDescription = 'description-first';

        $secondRecordHours = 3.8;
        $secondRecordHourlyRate = 234.567;
        $secondRecordRate = $secondRecordHours * $secondRecordHourlyRate;
        $secondRecordDuration = $secondRecordHours * 60 * 60;
        $secondRecordDescription = 'description-second';

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), $firstRecordHourlyRate, $firstRecordDuration, $firstRecordDescription);
        $timesheet2 = self::createTimesheet(new DateTime(), new User(), $secondRecordHourlyRate, $secondRecordDuration, $secondRecordDescription);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals($firstRecordDuration + $secondRecordDuration, $timeRecord->getDuration());
        self::assertEquals($firstRecordRate + $secondRecordRate, $timeRecord->getRate());
        self::assertEquals($secondRecordDescription, $timeRecord->getDescription());

        self::assertNotEmpty($timeRecord->getHourlyRates());
        self::assertEquals(true, $timeRecord->hasDifferentHourlyRates());
        self::assertEquals($firstRecordHourlyRate, $timeRecord->getHourlyRates()[0]['hourlyRate']);
        self::assertEquals($firstRecordDuration, $timeRecord->getHourlyRates()[0]['duration']);
        self::assertEquals($secondRecordHourlyRate, $timeRecord->getHourlyRates()[1]['hourlyRate']);
        self::assertEquals($secondRecordDuration, $timeRecord->getHourlyRates()[1]['duration']);
    }

    public function testActivityNameExtraction(): void
    {
        $activity = $this->createMock(\App\Entity\Activity::class);
        $activity->method('getName')->willReturn('Development');

        $timesheet = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc');
        $timesheet->setActivity($activity);

        $timeRecord = TimeRecord::fromTimesheet($timesheet);

        self::assertEquals('Development', $timeRecord->getActivityName());
    }

    public function testActivityMergeModeDefault(): void
    {
        $activity1 = $this->createMock(\App\Entity\Activity::class);
        $activity1->method('getName')->willReturn('Development');

        $activity2 = $this->createMock(\App\Entity\Activity::class);
        $activity2->method('getName')->willReturn('Testing');

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc1');
        $timesheet1->setActivity($activity1);

        $timesheet2 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc2');
        $timesheet2->setActivity($activity2);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals('Development, Testing', $timeRecord->getActivityName());
    }

    public function testActivityMergeModeUseFirst(): void
    {
        $activity1 = $this->createMock(\App\Entity\Activity::class);
        $activity1->method('getName')->willReturn('Development');

        $activity2 = $this->createMock(\App\Entity\Activity::class);
        $activity2->method('getName')->willReturn('Testing');

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc1');
        $timesheet1->setActivity($activity1);

        $timesheet2 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc2');
        $timesheet2->setActivity($activity2);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_FIRST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals('Development', $timeRecord->getActivityName());
    }

    public function testActivityMergeModeUseLast(): void
    {
        $activity1 = $this->createMock(\App\Entity\Activity::class);
        $activity1->method('getName')->willReturn('Development');

        $activity2 = $this->createMock(\App\Entity\Activity::class);
        $activity2->method('getName')->willReturn('Testing');

        $date1 = new DateTime('2025-01-01 09:00:00');
        $date2 = new DateTime('2025-01-01 14:00:00');

        $timesheet1 = self::createTimesheet($date1, new User(), 100, 3600, 'desc1');
        $timesheet1->setActivity($activity1);

        $timesheet2 = self::createTimesheet($date2, new User(), 100, 3600, 'desc2');
        $timesheet2->setActivity($activity2);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1, RecordMergeMode::MODE_MERGE_USE_LAST_OF_DAY);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals('Testing', $timeRecord->getActivityName());
    }

    public function testActivitySameNotDuplicated(): void
    {
        $activity1 = $this->createMock(\App\Entity\Activity::class);
        $activity1->method('getName')->willReturn('Development');

        $activity2 = $this->createMock(\App\Entity\Activity::class);
        $activity2->method('getName')->willReturn('Development');

        $timesheet1 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc1');
        $timesheet1->setActivity($activity1);

        $timesheet2 = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc2');
        $timesheet2->setActivity($activity2);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1);
        $timeRecord->addTimesheet($timesheet2);

        self::assertEquals('Development', $timeRecord->getActivityName());
    }

    public function testTagsExtraction(): void
    {
        $tag1 = $this->createMock(\App\Entity\Tag::class);
        $tag1->method('getName')->willReturn('urgent');
        $tag1->method('getColor')->willReturn('#ff0000');

        $tag2 = $this->createMock(\App\Entity\Tag::class);
        $tag2->method('getName')->willReturn('billable');
        $tag2->method('getColor')->willReturn('#00ff00');

        $tags = new \Doctrine\Common\Collections\ArrayCollection([$tag1, $tag2]);

        $timesheetMock = $this->getMockBuilder(\App\Entity\Timesheet::class)
            ->onlyMethods(['getTags'])
            ->getMock();
        $timesheetMock->method('getTags')->willReturn($tags);
        $timesheetMock->setBegin(new DateTime());
        $timesheetMock->setUser(new User());
        $timesheetMock->setHourlyRate(100);
        $timesheetMock->setRate(100);
        $timesheetMock->setDuration(3600);
        $timesheetMock->setDescription('desc');

        $activity = $this->createMock(\App\Entity\Activity::class);
        $activity->method('getName')->willReturn('Development');
        $timesheetMock->setActivity($activity);

        $timeRecord = TimeRecord::fromTimesheet($timesheetMock);

        $tags = $timeRecord->getTags();
        self::assertCount(2, $tags);
        self::assertEquals('billable', $tags[0]->getName());
        self::assertEquals('urgent', $tags[1]->getName());
    }

    public function testTagsMergeAccumulatesUniqueTags(): void
    {
        // Create tag mocks
        $tag1 = $this->createMock(\App\Entity\Tag::class);
        $tag1->method('getName')->willReturn('urgent');

        $tag2 = $this->createMock(\App\Entity\Tag::class);
        $tag2->method('getName')->willReturn('billable');

        $tag3 = $this->createMock(\App\Entity\Tag::class);
        $tag3->method('getName')->willReturn('client-x');

        $tags1 = new \Doctrine\Common\Collections\ArrayCollection([$tag1, $tag2]);
        $tags2 = new \Doctrine\Common\Collections\ArrayCollection([$tag2, $tag3]); // tag2 is duplicate

        $timesheet1Mock = $this->getMockBuilder(\App\Entity\Timesheet::class)
            ->onlyMethods(['getTags'])
            ->getMock();
        $timesheet1Mock->method('getTags')->willReturn($tags1);
        $timesheet1Mock->setBegin(new DateTime());
        $timesheet1Mock->setUser(new User());
        $timesheet1Mock->setHourlyRate(100);
        $timesheet1Mock->setRate(100);
        $timesheet1Mock->setDuration(3600);
        $timesheet1Mock->setDescription('desc1');

        $activity = $this->createMock(\App\Entity\Activity::class);
        $activity->method('getName')->willReturn('Development');
        $timesheet1Mock->setActivity($activity);

        $timesheet2Mock = $this->getMockBuilder(\App\Entity\Timesheet::class)
            ->onlyMethods(['getTags'])
            ->getMock();
        $timesheet2Mock->method('getTags')->willReturn($tags2);
        $timesheet2Mock->setBegin(new DateTime());
        $timesheet2Mock->setUser(new User());
        $timesheet2Mock->setHourlyRate(100);
        $timesheet2Mock->setRate(100);
        $timesheet2Mock->setDuration(3600);
        $timesheet2Mock->setDescription('desc2');
        $timesheet2Mock->setActivity($activity);

        $timeRecord = TimeRecord::fromTimesheet($timesheet1Mock);
        $timeRecord->addTimesheet($timesheet2Mock);

        $tags = $timeRecord->getTags();
        self::assertCount(3, $tags); // unique tags only

        // Tags should be sorted by name
        self::assertEquals('billable', $tags[0]->getName());
        self::assertEquals('client-x', $tags[1]->getName());
        self::assertEquals('urgent', $tags[2]->getName());
    }

    public function testEmptyTags(): void
    {
        $timesheet = self::createTimesheet(new DateTime(), new User(), 100, 3600, 'desc');

        $activity = $this->createMock(\App\Entity\Activity::class);
        $activity->method('getName')->willReturn('Development');
        $timesheet->setActivity($activity);

        $timeRecord = TimeRecord::fromTimesheet($timesheet);

        self::assertEmpty($timeRecord->getTags());
    }
}
