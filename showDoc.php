<?php

require_once "CreateCalFile.php";

$project_id = $_GET["pid"];
$createCal = new CreateCalFile();
echo $createCal->generate($project_id);


?>