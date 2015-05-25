<?php

$execution = get_defined_vars()['execution'];

$amount = $execution->getVariable('amount');
$discount = $execution->getVariable('discount', 0);

return $amount - $discount;
