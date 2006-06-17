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
require_once(BASE."include/comment.php");

$aClean = array(); //array of filtered user input

$aClean['appId'] = makeSafe($_REQUEST['appId']);
$aClean['versionId'] = makeSafe($_REQUEST['versionId']);
$aClean['threadId'] = makeSafe($_REQUEST['threadId']);

apidb_header("Comments");


if(!is_numeric($aClean['appId']) OR !is_numeric($aClean['versionId']) OR (!empty($aClean['threadId']) AND !is_numeric($aClean['threadId'])))
{
    errorpage("Wrong IDs");
    exit;
}

view_app_comments($aClean['versionId'], $aClean['threadId']);

apidb_footer();
?>
