<?php

$amount = $execution->getVariable('amount');
$discount = $execution->getVariable('discount', 0);

return $amount - $discount;
