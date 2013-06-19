<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 08:54
 */

include "SQMParser.php";


$sqmFile = SQMParser::parseFile("./test/mission.sqm");
$sqmFile->parse();
echo "<pre>";
print_r($sqmFile->searchPlayableSlots(true));
echo "</pre>";

