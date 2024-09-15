<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * @version 3.2.0
 */
final class Version20240726082447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable customer_id column and make project_id nullable';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
        $table->addColumn('customer_id', Types::INTEGER, [
            'notnull' => false,
        ]);
        $table->modifyColumn('project_id', [
            'notnull' => false,
        ]);
        $table->dropIndex('UNIQ_BE51C9A166D1F9CF06F2E59');
        $table->addUniqueIndex(['customer_id', 'project_id', 'share_key']);
        $table->addForeignKeyConstraint(
            'kimai2_customers',
            ['customer_id'],
            ['id'],
            [
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(Version2020120600000::SHARED_PROJECT_TIMESHEETS_TABLE_NAME);
        $table->dropIndex('UNIQ_BE51C9A9395C3F3166D1F9CF06F2E59');
        $table->addUniqueIndex(['project_id', 'share_key']);
        $table->removeForeignKey('FK_BE51C9A9395C3F3');
        $table->dropColumn('customer_id');
    }
}
