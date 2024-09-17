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
 * @version 3.2.0
 */
final class Version20240726082447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow to share complete customers';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_customer_portals');
        $table->addColumn('customer_id', Types::INTEGER, [
            'notnull' => false,
        ]);
        $table->modifyColumn('project_id', [
            'notnull' => false,
        ]);
        if ($table->hasIndex('UNIQ_BE51C9A166D1F9CF06F2E59')) {
            $table->dropIndex('UNIQ_BE51C9A166D1F9CF06F2E59');
        }
        $table->addUniqueIndex(['customer_id', 'project_id', 'share_key'], 'UNIQ_BE51C9A9395C3F3166D1F9CF06F2E59');
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
        $table = $schema->getTable('kimai2_customer_portals');
        $table->dropIndex('UNIQ_BE51C9A9395C3F3166D1F9CF06F2E59');
        $table->addUniqueIndex(['project_id', 'share_key']);
        $table->removeForeignKey('FK_BE51C9A9395C3F3');
        $table->dropColumn('customer_id');
    }
}
