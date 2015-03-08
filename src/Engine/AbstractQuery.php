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

abstract class AbstractQuery
{
	protected $orderings = [];
	
	protected $limit = 0;
	
	protected $offset = 0;
	
	public function limit($limit)
	{
		$limit = (int)$limit;
		
		if($limit < 1)
		{
			throw new \InvalidArgumentException(sprintf('Limit must be greater than 0, given %s', $limit));
		}
		
		$this->limit = $limit;
		
		return $this;
	}
	
	public function offset($offset)
	{
		$offset = (int)$offset;
		
		if($offset < 0)
		{
			throw new \InvalidArgumentException(sprintf('Offset must not be nagtive, given %s', $offset));
		}
		
		$this->offset = $offset;
		
		return $this;
	}
	
	protected function populateMultiProperty(& $prop, $value, callable $converter = NULL)
	{
		if(is_array($value) || $value instanceof \Traversable)
		{
			$prop = [];
				
			foreach($value as $tmp)
			{
				$prop[] = ($converter === NULL) ? (string)$tmp : $converter($tmp);
			}
		}
		else
		{
			$prop = [($converter === NULL) ? (string)$value : $converter($value)];
		}
	
		return $this;
	}
	
	protected function buildPredicate($name, $values, array & $where, array & $params)
	{
		if($values === NULL || (is_array($values) && empty($values)))
		{
			return;
		}
		
		if(count($values) == 1)
		{
			$p1 = 'p' . count($params);
		
			$where[] = sprintf('%s = :%s', $name, $p1);
			$params[$p1] = $values[0];
			
			return;
		}
		
		$ph = [];
	
		foreach($values as $value)
		{
			$p1 = 'p' . count($params);
			
			$ph[] = ":$p1";
			$params[$p1] = $value;
		}
	
		$where[] = sprintf('%s IN (%s)', $name, implode(', ', $ph));
	}
	
	protected abstract function getDefaultOrderBy();
	
	protected function buildOrderings()
	{
		$sql = '';
		
		$orderings = empty($this->orderings) ? [$this->getDefaultOrderBy()] : $this->orderings;
		$sql .= ' ORDER BY ';
		
		foreach($orderings as $i => $order)
		{
			if($i != 0)
			{
				$sql .= ', ';
			}
			
			$sql .= vsprintf('%s %s', $order);
		}
		
		return $sql;
	}
}
