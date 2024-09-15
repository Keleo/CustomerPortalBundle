<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use KimaiPlugin\SharedProjectTimesheetsBundle\Model\RecordMergeMode;

final class Version2020120920000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add record merge mode to shared project timesheets';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
        $table->addColumn(
            'record_merge_mode',
            Types::STRING,
            ['length' => 50, 'notnull' => true, 'default' => RecordMergeMode::MODE_NONE]
        );
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
        $table->dropColumn('record_merge_mode');
    }
}
