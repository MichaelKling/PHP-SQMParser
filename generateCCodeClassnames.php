<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:54
 */

//include "SQMParser.php";
include "SQMFastParser.php";

$outputDir = "./";
if (count($argv) >= 3 && $argv[1] == "--output") {
    $outputDir = $argv[2];
} 
if (isset($_GET['output'])) {
    $outputDir = $_GET['output'];
}

SQMLibrary::generateCCode($outputDir);