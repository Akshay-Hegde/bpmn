<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use KoolKode\Database\Migration\AbstractMigration;

/**
 * Add recorded activity name to activity history table.
 * 
 * @generated 2018-06-06 08:46:53 UTC
 */
class Version20180606084653 extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $activity = $this->table('#__bpmn_history_activity');
        $activity->addColumn('name', 'varchar', ['default' => '']);
        $activity->update();
    }
    
    /**
     * Migrate down.
     */
    public function down()
    {
        $activity = $this->table('#__bpmn_history_activity');
        $activity->removeColumn('name');
        $activity->update();
    }
}
