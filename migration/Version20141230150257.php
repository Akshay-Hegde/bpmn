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
use KoolKode\Database\Schema\Column;

/**
 * Initial DB schema of the process engine.
 * 
 * @generated 2014-12-30 15:02:57 UTC
 */
class Version20141230150257 extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
    	$deployment = $this->table('#__deployment');
    	$deployment->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$deployment->addColumn('name', Column::TYPE_VARCHAR);
    	$deployment->addColumn('deployed_at', Column::TYPE_INT, ['unsigned' => true]);
    	$deployment->addIndex(['name', 'deployed_at']);
    	$deployment->save();
    	
    	$resource = $this->table('#__resource');
    	$resource->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$resource->addColumn('deployment_id', Column::TYPE_UUID);
    	$resource->addColumn('name', Column::TYPE_VARCHAR);
    	$resource->addColumn('data', Column::TYPE_BLOB);
    	$resource->addUniqueIndex(['name', 'deployment_id']);
    	$resource->addForeignKey(['deployment_id'], '#__deployment', ['id']);
    	$resource->save();
    	
    	$def = $this->table('#__process_definition');
    	$def->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$def->addColumn('deployment_id', Column::TYPE_UUID);
    	$def->addColumn('process_key', Column::TYPE_VARCHAR);
    	$def->addColumn('revision', Column::TYPE_INT, ['unsigned' => true]);
    	$def->addColumn('definition', Column::TYPE_BLOB);
    	$def->addColumn('name', Column::TYPE_VARCHAR);
    	$def->addColumn('deployed_at', Column::TYPE_INT, ['unsigned' => true]);
    	$def->addUniqueIndex(['process_key', 'revision']);
    	$def->addIndex(['deployment_id', 'process_key']);
    	$def->addForeignKey(['deployment_id'], '#__deployment', ['id']);
    	$def->save();
    	
    	$psub = $this->table('#__process_subscription');
    	$psub->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$psub->addColumn('definition_id', Column::TYPE_UUID);
    	$psub->addColumn('flags', Column::TYPE_INT, ['unsigned' => true]);
    	$psub->addColumn('name', Column::TYPE_VARCHAR);
    	$psub->addUniqueIndex(['definition_id', 'name']);
    	$psub->addIndex(['name', 'flags']);
    	$psub->addForeignKey(['definition_id'], '#__process_definition', ['id']);
    	$psub->save();
    	
    	$exec = $this->table('#__execution');
    	$exec->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$exec->addColumn('pid', Column::TYPE_UUID, ['null' => true]);
    	$exec->addColumn('process_id', Column::TYPE_UUID);
    	$exec->addColumn('definition_id', Column::TYPE_UUID);
    	$exec->addColumn('state', Column::TYPE_INT, ['unsigned' => true]);
    	$exec->addColumn('active', Column::TYPE_DOUBLE);
    	$exec->addColumn('node', Column::TYPE_VARCHAR, ['null' => true]);
    	$exec->addColumn('transition', Column::TYPE_VARCHAR, ['null' => true]);
    	$exec->addColumn('depth', Column::TYPE_INT, ['unsigned' => true]);
    	$exec->addColumn('business_key', Column::TYPE_VARCHAR, ['null' => true]);
    	$exec->addIndex(['pid']);
    	$exec->addIndex(['definition_id']);
    	$exec->addIndex(['process_id']);
    	$exec->addIndex(['active']);
    	$exec->addIndex(['business_key']);
    	$exec->addIndex(['node']);
    	$exec->addForeignKey(['definition_id'], '#__process_definition', ['id']);
    	$exec->addForeignKey(['pid'], '#__execution', ['id']);
    	$exec->addForeignKey(['process_id'], '#__execution', ['id']);
    	$exec->save();
    	
    	$vars = $this->table('#__execution_variables');
    	$vars->addColumn('execution_id', Column::TYPE_UUID, ['primary_key' => true]);
    	$vars->addColumn('name', Column::TYPE_VARCHAR, ['primary_key' => true]);
    	$vars->addColumn('value', Column::TYPE_VARCHAR, ['null' => true]);
    	$vars->addColumn('value_blob', Column::TYPE_BLOB);
    	$vars->addIndex(['name', 'value']);
    	$vars->addForeignKey(['execution_id'], '#__execution', ['id']);
    	$vars->save();
    	
    	$events = $this->table('#__event_subscription');
    	$events->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$events->addColumn('execution_id', Column::TYPE_UUID);
    	$events->addColumn('activity_id', Column::TYPE_VARCHAR);
    	$events->addColumn('node', Column::TYPE_VARCHAR, ['null' => true]);
    	$events->addColumn('process_instance_id', Column::TYPE_UUID);
    	$events->addColumn('flags', Column::TYPE_INT, ['unsigned' => true]);
    	$events->addColumn('name', Column::TYPE_VARCHAR);
    	$events->addColumn('created_at', Column::TYPE_INT, ['unsigned' => true]);
    	$events->addIndex(['execution_id', 'activity_id']);
    	$events->addIndex(['process_instance_id']);
    	$events->addIndex(['name', 'flags']);
    	$events->addForeignKey(['execution_id'], '#__execution', ['id']);
    	$events->addForeignKey(['process_instance_id'], '#__execution', ['id']);
    	$events->save();
    	
    	$tasks = $this->table('#__user_task');
    	$tasks->addColumn('id', Column::TYPE_UUID, ['primary_key' => true]);
    	$tasks->addColumn('execution_id', Column::TYPE_UUID);
    	$tasks->addColumn('name', Column::TYPE_VARCHAR);
    	$tasks->addColumn('documentation', Column::TYPE_TEXT, ['null' => true]);
    	$tasks->addColumn('activity', Column::TYPE_VARCHAR);
    	$tasks->addColumn('created_at', Column::TYPE_INT, ['unsigned' => true]);
    	$tasks->addColumn('claimed_at', Column::TYPE_INT, ['unsigned' => true, 'null' => true]);
    	$tasks->addColumn('claimed_by', Column::TYPE_VARCHAR, ['null' => true]);
    	$tasks->addColumn('priority', Column::TYPE_INT, ['unsigned' => true]);
    	$tasks->addColumn('due_at', Column::TYPE_INT, ['unsigned' => true, 'null' => true]);
    	$tasks->addUniqueIndex(['execution_id']);
    	$tasks->addIndex(['created_at']);
    	$tasks->addIndex(['activity']);
    	$tasks->addIndex(['claimed_by']);
    	$tasks->addIndex(['priority']);
    	$tasks->addIndex(['due_at']);
    	$tasks->addForeignKey(['execution_id'], '#__execution', ['id']);
    	$tasks->save();
    }
    
    /**
     * Migrate down.
     */
    public function down()
    {
    	$this->dropTable('#__user_task');
    	$this->dropTable('#__event_subscription');
    	$this->dropTable('#__execution_variables');
    	$this->dropTable('#__execution');
    	$this->dropTable('#__process_subscription');
    	$this->dropTable('#__process_definition');
    	$this->dropTable('#__resource');
        $this->dropTable('#__deployment');
    }
}
