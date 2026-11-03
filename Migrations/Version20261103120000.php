<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * @version 4.7.0
 */
final class Version20261103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add billable options to the Customer Portal';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        // existing portals keep showing non-billable timesheets
        $table->addColumn('non_billable_visible', Types::BOOLEAN, ['default' => true, 'notnull' => true]);
        $table->addColumn('entry_billable_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        $table->dropColumn('non_billable_visible');
        $table->dropColumn('entry_billable_visible');
    }
}
