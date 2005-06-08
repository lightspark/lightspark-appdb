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
perform_search_and_output_results($_REQUEST['q']);
apidb_footer();
?>
