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

$hResult = searchForApplication($_REQUEST['q']);
outputSearchTableForhResult($_REQUEST['q'], $hResult);
apidb_footer();
?>
