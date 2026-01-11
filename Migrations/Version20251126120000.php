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

/**
 * @version 4.6.0
 */
final class Version20251126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add show_total_amount_when_entry_rate_hidden column to customer portals';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if (!$table->hasColumn('show_total_amount_when_entry_rate_hidden')) {
            $table->addColumn('show_total_amount_when_entry_rate_hidden', 'boolean', [
                'default' => true,
                'notnull' => true,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if ($table->hasColumn('show_total_amount_when_entry_rate_hidden')) {
            $table->dropColumn('show_total_amount_when_entry_rate_hidden');
        }
    }
}
