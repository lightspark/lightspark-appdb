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
require(BASE."include/comment.php");

apidb_header("Comments");

if(!is_numeric($_REQUEST['appId']) OR !is_numeric($_REQUEST['versionId']) OR (isset($_REQUEST['threadId']) AND !is_numeric($_REQUEST['threadId'])))
{
    errorpage("Wrong IDs");
    exit;
}

view_app_comments($_REQUEST['appId'], $_REQUEST['versionId'], $_REQUEST['threadId']);

apidb_footer();
?>
