<?php
/************************************************************/
/* view comments                                            */
/*                                                          */
/* script expects appId, versionId and threadId as argument */
/************************************************************/

/*
 * application environment
 */
include("path.php");
include(BASE."include/incl.php");
include(BASE."include/filter.php");
require_once(BASE."include/comment.php");

apidb_header("Comments");

Comment::view_app_comments($aClean['iVersionId'], $aClean['iThreadId']);

apidb_footer();
?>
