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

$aClean['iAppId'] = makeSafe($_REQUEST['iAppId']);
$aClean['iVersionId'] = makeSafe($_REQUEST['iVersionId']);
$aClean['iThreadId'] = makeSafe($_REQUEST['iThreadId']);

apidb_header("Comments");


if(!is_numeric($aClean['iAppId']) OR !is_numeric($aClean['iVersionId']) OR (!empty($aClean['iThreadId']) AND !is_numeric($aClean['iThreadId'])))
    util_show_error_page_and_exit("Wrong IDs");

view_app_comments($aClean['iVersionId'], $aClean['iThreadId']);

apidb_footer();
?>
