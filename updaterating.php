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

rating_update($_GET);

// go back to where we came from
redirectref();
?>
