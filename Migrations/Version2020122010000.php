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

final class Version2020122010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add flags to enable charts';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        $table->addColumn('annual_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
        $table->addColumn('monthly_chart_visible', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');

        $table->dropColumn('annual_chart_visible');
        $table->dropColumn('monthly_chart_visible');
    }
}
