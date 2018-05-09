<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate;

class ComputePriceTask implements DelegateTaskInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(DelegateExecutionInterface $execution)
    {
        $amount = (int) $execution->getVariable('amount');
        $discount = (int) $execution->getVariable('discount', 0);
        
        $execution->setVariable('result', $amount - $discount);
    }
}
