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
        $job->addColumn('external_id', 'varchar', ['default' => null]);
        $job->addColumn('lock_owner', 'varchar', ['default' => null]);
        $job->addColumn('execution_id', 'uuid');
        $job->addColumn('retries', 'int', ['unsigned' => true, 'default' => 0]);
        $job->addColumn('handler_type', 'varchar');
        $job->addColumn('handler_data', 'blob', ['default' => null]);
        $job->addColumn('created_at', 'bigint');
        $job->addColumn('scheduled_at', 'bigint', ['default' => null]);
        $job->addColumn('run_at', 'bigint', ['default' => null]);
        $job->addColumn('locked_at', 'bigint', ['default' => null]);
        $job->addColumn('exception_type', 'varchar', ['default' => null]);
        $job->addColumn('exception_message', 'text', ['default' => null]);
        $job->addColumn('exception_data', 'blob', ['default' => null]);
        $job->addIndex(['execution_id']);
        $job->addIndex(['lock_owner']);
        $job->addIndex(['handler_type']);
        $job->addIndex(['created_at']);
        $job->addIndex(['scheduled_at']);
        $job->addIndex(['locked_at']);
        $job->addIndex(['run_at']);
        $job->addForeignKey(['execution_id'], '#__bpmn_execution', ['id']);
        $job->create();
        
        $def = $this->table('#__bpmn_process_definition');
        $def->addColumn('resource_id', 'uuid', ['default' => null]);
        $def->update();
        
        $subscription = $this->table('#__bpmn_event_subscription');
        $subscription->addColumn('boundary', 'int', ['unsigned' => true, 'default' => 0]);
        $subscription->addColumn('job_id', 'uuid', ['default' => null]);
        $subscription->update();
    }
    
    /**
     * Migrate down.
     */
    public function down()
    {
        $def = $this->table('#__bpmn_process_definition');
        $def->removeColumn('resource_id');
        $def->update();
        
        $subscription = $this->table('#__bpmn_event_subscription');
        $subscription->removeColumn('job_id');
        $subscription->removeColumn('boundary');
        $subscription->update();
        
        $this->dropTable('#__bpmn_job');
    }
}
