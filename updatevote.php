<?php
/*************************/
/* code to modify voting */
/*************************/

/*
 * application evironment
 */ 
include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."vote.php");

redirectref();

apidb_header("Testing");

vote_update($_GET);

apidb_footer();

?>
