<?php
/*************************/
/* code to modify rating */
/*************************/

/*
 * application environment
 */ 
include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."rating.php");

// go back to where we came from
redirectref();

apidb_header("Update Rating");

apidb_session_start();

rating_update($_GET);

apidb_footer();

?>
