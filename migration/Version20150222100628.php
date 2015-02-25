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
 * Adds history / audit storage.
 * 
 * @generated 2015-02-22 10:06:28 UTC
 */
class Version20150222100628 extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
    	$exec = $this->table('#__bpmn_history_process');
    	$exec->addColumn('id', 'uuid', ['primary_key' => true]);
    	$exec->addColumn('definition_id', 'uuid');
    	$exec->addColumn('business_key', 'varchar', ['default' => NULL]);
    	$exec->addColumn('start_activity', 'varchar');
    	$exec->addColumn('end_activity', 'varchar', ['default' => NULL]);
    	$exec->addColumn('started_at', 'bigint');
    	$exec->addColumn('ended_at', 'bigint', ['default' => NULL]);
    	$exec->addColumn('duration', 'bigint', ['default' => NULL, 'unsigned' => true]);
    	$exec->addIndex(['definition_id', 'business_key']);
    	$exec->addIndex(['start_activity']);
    	$exec->addIndex(['end_activity']);
    	$exec->addIndex(['started_at']);
    	$exec->addIndex(['ended_at']);
    	$exec->addIndex(['duration', 'definition_id']);
    	$exec->addForeignKey(['definition_id'], '#__bpmn_process_definition', ['id'], ['delete' => 'RESTRICT']);
    	$exec->create();
    	
    	$vars = $this->table('#__bpmn_history_variables');
    	$vars->addColumn('process_id', 'uuid', ['primary_key' => true]);
    	$vars->addColumn('data', 'blob');
    	$vars->addForeignKey(['process_id'], '#__bpmn_history_process', ['id']);
    	$vars->create();
    	
    	$task = $this->table('#__bpmn_history_task');
    	$task->addColumn('id', 'uuid', ['primary_key' => true]);
    	$task->addColumn('process_id', 'uuid', ['default' => NULL]);
    	$task->addColumn('definition_key', 'varchar', ['default' => NULL]);
    	$task->addColumn('started_at', 'bigint');
    	$task->addColumn('ended_at', 'bigint', ['default' => NULL]);
    	$task->addColumn('duration', 'bigint', ['default' => NULL, 'unsigned' => true]);
    	$task->addColumn('completed', 'int', ['default' => 0, 'unsigned' => 1]);
    	$task->addColumn('description', 'text', ['default' => NULL]);
    	$task->addColumn('assignee', 'varchar', ['default' => NULL]);
    	$task->addColumn('priority', 'int', ['unsigned' => true]);
    	$task->addIndex(['process_id']);
    	$task->addIndex(['definition_key']);
    	$task->addIndex(['started_at']);
    	$task->addIndex(['ended_at']);
    	$task->addIndex(['assignee']);
    	$task->addForeignKey(['process_id'], '#__bpmn_history_process', ['id'], ['delete' => 'RESTRICT']);
    	$task->create();
    	
    	$activity = $this->table('#__bpmn_history_activity');
    	$activity->addColumn('id', 'uuid', ['primary_key' => true]);
    	$activity->addColumn('process_id', 'uuid');
    	$activity->addColumn('task_id', 'uuid', ['default' => NULL]);
    	$activity->addColumn('activity', 'varchar');
    	$activity->addColumn('started_at', 'bigint');
    	$activity->addColumn('ended_at', 'bigint', ['default' => NULL]);
    	$activity->addColumn('duration', 'bigint', ['default' => NULL, 'unsigned' => true]);
    	$activity->addColumn('completed', 'int', ['default' => 0, 'unsigned' => 1]);
    	$activity->addIndex(['process_id']);
    	$activity->addIndex(['task_id']);
    	$activity->addIndex(['activity']);
    	$activity->addIndex(['started_at']);
    	$activity->addIndex(['ended_at']);
    	$activity->addIndex(['duration', 'process_id', 'activity']);
    	$activity->addForeignKey(['process_id'], '#__bpmn_history_process', ['id'], ['delete' => 'RESTRICT']);
    	$activity->addForeignKey(['task_id'], '#__bpmn_history_task', ['id'], ['delete' => 'RESTRICT']);
    	$activity->create();
    }
    
    /**
     * Migrate down.
     */
    public function down()
    {
    	$this->dropTable('#__bpmn_history_activity');
    	$this->dropTable('#__bpmn_history_task');
    	$this->dropTable('#__bpmn_history_variables');
    	$this->dropTable('#__bpmn_history_process');
    }
}
