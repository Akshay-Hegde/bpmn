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
    	$deployment->addColumn('id', 'uuid', ['primary_key' => true]);
    	$deployment->addColumn('name', 'varchar');
    	$deployment->addColumn('deployed_at', 'int', ['unsigned' => true]);
    	$deployment->addIndex(['name', 'deployed_at']);
    	$deployment->save();
    	
    	$resource = $this->table('#__resource');
    	$resource->addColumn('id', 'uuid', ['primary_key' => true]);
    	$resource->addColumn('deployment_id', 'uuid');
    	$resource->addColumn('name', 'varchar');
    	$resource->addColumn('data', 'blob');
    	$resource->addUniqueIndex(['name', 'deployment_id']);
    	$resource->addForeignKey(['deployment_id'], '#__deployment', ['id']);
    	$resource->save();
    	
    	$def = $this->table('#__process_definition');
    	$def->addColumn('id', 'uuid', ['primary_key' => true]);
    	$def->addColumn('deployment_id', 'uuid');
    	$def->addColumn('process_key', 'varchar');
    	$def->addColumn('revision', 'int', ['unsigned' => true]);
    	$def->addColumn('definition', 'blob');
    	$def->addColumn('name', 'varchar');
    	$def->addColumn('deployed_at', 'int', ['unsigned' => true]);
    	$def->addUniqueIndex(['process_key', 'revision']);
    	$def->addIndex(['deployment_id', 'process_key']);
    	$def->addForeignKey(['deployment_id'], '#__deployment', ['id']);
    	$def->save();
    	
    	$psub = $this->table('#__process_subscription');
    	$psub->addColumn('id', 'uuid', ['primary_key' => true]);
    	$psub->addColumn('definition_id', 'uuid');
    	$psub->addColumn('flags', 'int', ['unsigned' => true]);
    	$psub->addColumn('name', 'varchar');
    	$psub->addUniqueIndex(['definition_id', 'name']);
    	$psub->addIndex(['name', 'flags']);
    	$psub->addForeignKey(['definition_id'], '#__process_definition', ['id']);
    	$psub->save();
    	
    	$exec = $this->table('#__execution');
    	$exec->addColumn('id', 'uuid', ['primary_key' => true]);
    	$exec->addColumn('pid', 'uuid', ['null' => true]);
    	$exec->addColumn('process_id', 'uuid');
    	$exec->addColumn('definition_id', 'uuid');
    	$exec->addColumn('state', 'int', ['unsigned' => true]);
    	$exec->addColumn('active', 'double');
    	$exec->addColumn('node', 'varchar', ['null' => true]);
    	$exec->addColumn('transition', 'varchar', ['null' => true]);
    	$exec->addColumn('depth', 'int', ['unsigned' => true]);
    	$exec->addColumn('business_key', 'varchar', ['null' => true]);
    	$exec->addIndex(['pid']);
    	$exec->addIndex(['definition_id']);
    	$exec->addIndex(['process_id']);
    	$exec->addIndex(['active']);
    	$exec->addIndex(['business_key']);
    	$exec->addIndex(['node']);
    	$exec->addForeignKey(['definition_id'], '#__process_definition', ['id'], ['delete' => 'RESTRICT']);
    	$exec->addForeignKey(['pid'], '#__execution', ['id']);
    	$exec->addForeignKey(['process_id'], '#__execution', ['id']);
    	$exec->save();
    	
    	$vars = $this->table('#__execution_variables');
    	$vars->addColumn('execution_id', 'uuid', ['primary_key' => true]);
    	$vars->addColumn('name', 'varchar', ['primary_key' => true]);
    	$vars->addColumn('value', 'varchar', ['null' => true]);
    	$vars->addColumn('value_blob', 'blob');
    	$vars->addIndex(['name', 'value']);
    	$vars->addForeignKey(['execution_id'], '#__execution', ['id']);
    	$vars->save();
    	
    	$events = $this->table('#__event_subscription');
    	$events->addColumn('id', 'uuid', ['primary_key' => true]);
    	$events->addColumn('execution_id', 'uuid');
    	$events->addColumn('activity_id', 'varchar');
    	$events->addColumn('node', 'varchar', ['null' => true]);
    	$events->addColumn('process_instance_id', 'uuid');
    	$events->addColumn('flags', 'int', ['unsigned' => true]);
    	$events->addColumn('name', 'varchar');
    	$events->addColumn('created_at', 'int', ['unsigned' => true]);
    	$events->addIndex(['execution_id', 'activity_id']);
    	$events->addIndex(['process_instance_id']);
    	$events->addIndex(['name', 'flags']);
    	$events->addForeignKey(['execution_id'], '#__execution', ['id']);
    	$events->addForeignKey(['process_instance_id'], '#__execution', ['id']);
    	$events->save();
    	
    	$tasks = $this->table('#__user_task');
    	$tasks->addColumn('id', 'uuid', ['primary_key' => true]);
    	$tasks->addColumn('execution_id', 'uuid');
    	$tasks->addColumn('name', 'varchar');
    	$tasks->addColumn('documentation', 'text', ['null' => true]);
    	$tasks->addColumn('activity', 'varchar');
    	$tasks->addColumn('created_at', 'int', ['unsigned' => true]);
    	$tasks->addColumn('claimed_at', 'int', ['unsigned' => true, 'null' => true]);
    	$tasks->addColumn('claimed_by', 'varchar', ['null' => true]);
    	$tasks->addColumn('priority', 'int', ['unsigned' => true]);
    	$tasks->addColumn('due_at', 'int', ['unsigned' => true, 'null' => true]);
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
