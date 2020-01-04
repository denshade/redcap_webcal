<?php

require_once __DIR__."/../../redcap_connect.php";
require_once "CreateCalFile.php";
require_once "vendor/autoload.php";
global $conn;
$c = new CreateCalFile();
try{

    echo $c->writeCalendar(15,".ics");

} catch(Exception $exception)
{
    var_dump($exception);
}

