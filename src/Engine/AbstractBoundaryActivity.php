<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

/**
 * Base class for BPMN boundary events.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBoundaryActivity extends AbstractActivity
{
	protected $attachedTo;
	
	protected $interrupting = true;
	
	public function __construct($attachedTo)
	{
		$this->attachedTo = (string)$attachedTo;
	}
	
	public function getAttachedTo()
	{
		return $this->attachedTo;
	}
	
	public function isInterrupting()
	{
		return $this->interrupting;
	}
	
	public function setInterrupting($interrupting)
	{
		$this->interrupting = $interrupting ? true : false;
	}
}
