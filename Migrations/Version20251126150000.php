<?php

namespace KimaiPlugin\CustomerPortalBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 4.6.0
 */
final class Version20251126150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project_sub_totals_visible column to customer portals';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if (!$table->hasColumn('project_sub_totals_visible')) {
            $table->addColumn('project_sub_totals_visible', 'boolean', [
                'default' => true,
                'notnull' => true,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        if ($table->hasColumn('project_sub_totals_visible')) {
            $table->dropColumn('project_sub_totals_visible');
        }
    }
}
