<?php
/************************************************************/
/* view comments                                            */
/*                                                          */
/* script expects appId, versionId and threadId as argument */
/************************************************************/

/*
 * application environment
 */
require("path.php");
require(BASE."include/incl.php");
require(BASE."include/filter.php");
require_once(BASE."include/comment.php");

apidb_header("Comments");

Comment::view_app_comments($aClean['iVersionId'], $aClean['iThreadId']);

apidb_footer();
?>
