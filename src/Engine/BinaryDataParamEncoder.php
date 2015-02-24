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

use KoolKode\Database\ConnectionInterface;
use KoolKode\Database\ParamEncoderInterface;
use KoolKode\Stream\StringStream;
use KoolKode\Database\DB;

/**
 * Encodes binary values with optional comporession.
 * 
 * @author Martin Schröder
 */
class BinaryDataParamEncoder implements ParamEncoderInterface
{
	public function encodeParam(ConnectionInterface $conn, $param, & $isEncoded)
	{
		if($param instanceof BinaryData)
		{
			$isEncoded = true;
			
			if($conn->getDriverName() == DB::DRIVER_POSTGRESQL)
			{
				return new StringStream(str_replace('\\', '\\\\', $param->encode()));
			}
			
			return new StringStream($param->encode());
		}
	}
}
