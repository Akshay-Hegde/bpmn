<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\History;

/**
 * DB transformer that creates a date object from a millisecond-timestamp.
 * 
 * @author Martin Schröder
 */
class DateTimeMillisTransformer
{
    public function __invoke($value)
    {
        if ($value === null) {
            return null;
        }
        
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.03f', (float) $value / 1000));
    }

    public static function encode(\DateTimeInterface $date)
    {
        return $date->format('U') . sprintf('%03u', ceil((int) $date->format('u') / 1000));
    }
}
