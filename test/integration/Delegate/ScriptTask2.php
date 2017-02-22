<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$execution = get_defined_vars()['execution'];

$amount = $execution->getVariable('amount');
$discount = $execution->getVariable('discount', 0);

return $amount - $discount;
