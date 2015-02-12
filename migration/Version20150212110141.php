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
 * Adds support for scheduled jobs.
 * 
 * @generated 2015-02-12 11:01:41 UTC
 */
class Version20150212110141 extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $job = $this->table('#__bpmn_job');
        $job->addColumn('id', 'uuid', ['primary_key' => true]);
        $job->addColumn('lock_owner', 'varchar', ['default' => NULL]);
        $job->addColumn('execution_id', 'uuid');
        $job->addColumn('retries', 'int', ['unsigned' => true, 'default' => 0]);
        $job->addColumn('handler_type', 'varchar');
        $job->addColumn('handler_data', 'blob', ['default' => NULL]);
        
        // TODO: This should really be a datetime / timestamp type!
        $job->addColumn('run_at', 'bigint', ['default' => NULL]);
        
        $job->addIndex(['execution_id']);
        $job->addIndex(['run_at']);
        $job->addForeignKey(['execution_id'], '#__bpmn_execution', ['id']);
        $job->create();
    }
    
    /**
     * Migrate down.
     */
    public function down()
    {
    	$this->dropTable('#__bpmn_job');
    }
}
