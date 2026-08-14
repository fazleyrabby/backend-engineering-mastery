<?php

// Test script to inspect PHP variable types and memory addresses
$a = 5;
$b = 10;
$c = $a + $b;

echo "Result: $c\n";
echo "Memory Used: " . memory_get_usage() . " bytes\n";
