<?php
/*************************/
/* code to modify voting */
/*************************/

/*
 * application environment
 */ 
include("path.php");
include(BASE."include/incl.php");
include(BASE."include/vote.php");

vote_update($_POST);

apidb_footer();
?>
