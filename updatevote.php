<?php
/*************************/
/* code to modify voting */
/*************************/

/*
 * application environment
 */ 
require("path.php");
require(BASE."include/incl.php");
require(BASE."include/vote.php");

vote_update($aClean);

apidb_footer();
?>
