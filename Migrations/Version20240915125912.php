<?php

declare(strict_types=1);

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 3.2.0
 */
final class Version20240915125912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the share-key unique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_shared_project_timesheets DROP INDEX IDX_BE51C9AF06F2E59, ADD UNIQUE INDEX UNIQ_BE51C9AF06F2E59 (share_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_shared_project_timesheets DROP INDEX UNIQ_BE51C9AF06F2E59, ADD INDEX IDX_BE51C9AF06F2E59 (share_key)');
    }
}
