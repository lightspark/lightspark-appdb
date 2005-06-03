<?php
/*****************/
/* search engine */
/*****************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");


apidb_header("Search Results");

echo "<center><b>Like matches</b></center>";
$hResult = searchForApplication($_REQUEST['q']);
outputSearchTableForhResult($_REQUEST['q'], $hResult);

$minMatchingPercent = 60;
echo "<center><b>Fuzzy matches - minimum ".$minMatchingPercent."% match</b></center>";
$hResult = searchForApplicationFuzzy($_REQUEST['q'], $minMatchingPercent);
outputSearchTableForhResult($_REQUEST['q'], $hResult);

apidb_footer();
?>
