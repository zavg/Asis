<?php

// filtering a lot of mess from PEAR XML_Serializer
ini_set('error_reporting', ~E_STRICT & ~E_DEPRECATED);

require_once "Asis/Logger.php";
require_once "Asis/Tester.php";

// Declaring simple class to test it
class Foo
{
    public function bar($x)
    {
        return $x * $x;
    }
}

// Creating a logger for our simple test project
// with project directory root linked to current directory
$logger = new Asis_Logger(
    array("applicationPath" => dirname(__FILE__))
);

// Logging the sample input data to tests/inputs/sample.xml
$logger->log('Foo', 'bar', 2);

// Creating a tester classes
// Note: all classes which are tested should be available
// either with require_once-s or with autoloading
$tester = new Asis_Tester();

// Running tests, serialized output (value 4 for our case) is now in
// tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.received.txt
$tester->run();

// Approving output
// tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.received.txt is renamed in
// tests/outputs/Foo/bar-a5f5d7a5fc80600513c623db108873af.approved.txt
$tester->approve();

// Running tests again
$tester->run();
// 1 tests executed
// 0 assertions





