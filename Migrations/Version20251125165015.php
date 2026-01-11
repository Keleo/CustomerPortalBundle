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
final class Version20251125165015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entry_activity_visible and entry_tags_visible columns to customer portals';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if (!$table->hasColumn('entry_activity_visible')) {
            $table->addColumn('entry_activity_visible', 'boolean', [
                'default' => false,
                'notnull' => true,
            ]);
        }

        if (!$table->hasColumn('entry_tags_visible')) {
            $table->addColumn('entry_tags_visible', 'boolean', [
                'default' => false,
                'notnull' => true,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if ($table->hasColumn('entry_activity_visible')) {
            $table->dropColumn('entry_activity_visible');
        }

        if ($table->hasColumn('entry_tags_visible')) {
            $table->dropColumn('entry_tags_visible');
        }
    }
}
