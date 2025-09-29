<?php

require __DIR__.'/../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__.'../')
    ->withDatabaseUri('https://sreemeditec-final-default-rtdb.firebaseio.com');

$database = $factory->createDatabase();
$auth = $factory->createAuth();

?>