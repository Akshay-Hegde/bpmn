<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

interface BusinessProcessSignalableActivityInterface extends BusinessProcessActivityInterface
{
	public function signal(BusinessProcessExecution $execution, $signal, array $variables = []);
}
