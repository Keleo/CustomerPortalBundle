<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\tests\Service;

use App\Entity\Project;
use App\Repository\TimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ViewService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class ViewServiceTest extends TestCase
{
    /**
     * @var ViewService
     */
    private $service;

    /**
     * @var SessionInterface|MockObject
     */
    private $session;

    /**
     * @var PasswordHasherInterface|MockObject
     */
    private $encoder;

    /**
     * @var string
     */
    private $sessionKey;

    protected function setUp(): void
    {
        $timesheetRepository = $this->createMock(TimesheetRepository::class);
        $sharedProjectTimesheetRepository = $this->createMock(SharedProjectTimesheetRepository::class);
        $request = new RequestStack();
        $this->session = $this->createPartialMock(SessionInterface::class, []);

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->encoder = $this->createMock(PasswordHasherInterface::class);
        $factory->method('getPasswordHasher')->willReturn($this->encoder);

        $this->service = new ViewService($timesheetRepository, $request, $factory, $sharedProjectTimesheetRepository);
    }

    private function createSharedProjectTimesheet(): SharedProjectTimesheet
    {
        $project = $this->createMock(Project::class);
        $project->method('getId')
            ->willReturn(1);

        $tmp = new SharedProjectTimesheet();
        $tmp->setProject($project);
        $tmp->setShareKey('sharekey');

        return $tmp;
    }

    public function testNoPassword(): void
    {
        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, '');
        self::assertTrue($hasAccess);
    }

    public function testValidPassword(): void
    {
        $this->encoder->method('isPasswordValid')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('password');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'password');
        self::assertTrue($hasAccess);
    }

    public function testInvalidPassword(): void
    {
        $this->encoder->method('isPasswordValid')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('password');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'wrong');
        self::assertFalse($hasAccess);
    }

    public function testPasswordRemember(): void
    {
        // Mock session behaviour
        $this->session->expects($this->exactly(1))
            ->method('set')
            ->willReturnCallback(function ($key) {
                $this->sessionKey = $key;
            });

        $this->session->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(function ($key) {
                return $key === $this->sessionKey;
            });

        // Expect the encoder->isPasswordValid method is called only once
        $this->encoder->expects($this->exactly(1))
            ->method('isPasswordValid')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('test');

        $this->service->hasAccess($sharedProjectTimesheet, 'test');
        $this->service->hasAccess($sharedProjectTimesheet, 'test');
    }

    public function testPasswordChange(): void
    {
        // Mock session behaviour
        $this->session->expects($this->exactly(1))
            ->method('set')
            ->willReturnCallback(function ($key) {
                $this->sessionKey = $key;
            });

        $this->session->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(function ($key) {
                return $key === $this->sessionKey;
            });

        // Expect the encoder->isPasswordValid method is called only once
        $this->encoder->expects($this->exactly(2))
            ->method('isPasswordValid')
            ->willReturnCallback(function ($hashedPassword, $givenPassword) {
                return $hashedPassword === $givenPassword;
            });

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('test');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'test');
        self::assertTrue($hasAccess);

        $sharedProjectTimesheet = $this->createSharedProjectTimesheet();
        $sharedProjectTimesheet->setPassword('changed');

        $hasAccess = $this->service->hasAccess($sharedProjectTimesheet, 'test');
        self::assertFalse($hasAccess);
    }
}
