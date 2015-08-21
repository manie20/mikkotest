#!/usr/bin/php
<?php

namespace ArmandKrijgsman;

require __DIR__ . '/vendor/autoload.php';

try {
    $application = new MikkoTest();
    $filename = $application->generate();
    \cli\line('%Y%4File is saved as: ' . $filename . '%n');
} catch (\Exception $e) {
    \cli\line('%Y%1' . $e->getMessage() . '%n');
}
