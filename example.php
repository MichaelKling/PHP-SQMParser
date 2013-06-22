<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:54
 */

include "SQMParser.php";

echo xdebug_get_profiler_filename()."<br>";
$now = microtime(true);
printf("MEM:  %d<br/>\nPEAK: %d<br/>\n", memory_get_usage(), memory_get_peak_usage());

$sqmFile = SQMParser::parseFile("./test/mission1.sqm");
$sqmFile->parse();

$then = microtime(true);
$time = $then-$now;
printf("MEM:  %d<br/>\nPEAK: %d<br/>\n", memory_get_usage(), memory_get_peak_usage());
echo "Total Elapsed: ".$time." seconds<br/>\n";

echo "<pre>";
print_r($sqmFile->searchPlayableSlots(true));
echo "</pre>";
