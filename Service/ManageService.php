<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Service;

use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class ManageService
{
    public const PASSWORD_DO_NOT_CHANGE_VALUE = '__DO_NOT_CHANGE__';

    public function __construct(
        private readonly SharedProjectTimesheetRepository $repository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory
    )
    {
    }

    private function getPasswordHasher(): PasswordHasherInterface
    {
        return $this->passwordHasherFactory->getPasswordHasher('shared_projects');
    }

    public function create(SharedProjectTimesheet $sharedProjectTimesheet, ?string $password = null): SharedProjectTimesheet
    {
        // Set share key
        if ($sharedProjectTimesheet->getShareKey() === null) {
            do {
                $sharedProjectTimesheet->setShareKey(
                    substr(preg_replace('/[^A-Za-z0-9]+/', '', $this->getUuidV4()), 0, 12)
                );

                $existingEntry = $this->repository->findBySharedProjectTimesheet($sharedProjectTimesheet);
            } while ($existingEntry !== null);
        }

        return $this->update($sharedProjectTimesheet, $password);
    }

    public function update(SharedProjectTimesheet $sharedProjectTimesheet, ?string $newPassword = null): SharedProjectTimesheet
    {
        // Check if updatable
        if ($sharedProjectTimesheet->getShareKey() === null) {
            throw new \InvalidArgumentException('Cannot update shared project timesheet with share key equals null');
        }

        // Handle password
        $currentHashedPassword = !empty($sharedProjectTimesheet->getPassword()) ? $sharedProjectTimesheet->getPassword() : null;

        if ($newPassword !== self::PASSWORD_DO_NOT_CHANGE_VALUE) {
            if (!empty($newPassword)) {
                // Change password
                $encodedPassword = $this->getPasswordHasher()->hash($newPassword);
                $sharedProjectTimesheet->setPassword($encodedPassword);
            } else {
                // Reset password if exists
                $sharedProjectTimesheet->setPassword(null);
            }
        } else {
            $sharedProjectTimesheet->setPassword($currentHashedPassword);
        }

        $this->repository->save($sharedProjectTimesheet);

        return $sharedProjectTimesheet;
    }

    /**
     * @see https://www.php.net/manual/en/function.uniqid.php#94959
     */
    private function getUuidV4(): string
    {
        return \sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
