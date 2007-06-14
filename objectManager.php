<?php
/**
 * Code for displaying and processing objects that have object management
 * methods.
 *
 * Required parameters
 * sClass: The class that is to be handled
 *
 * Optional parameters
 * sTitle: Set the title of the page
 * iId: The object id when handling a specific entry
 * bIsQueue: Whether we are handling a queue, set automatically if bIsRejected is true
 * bIsRejected: Whether we are handling rejected entries, sets bIsQueue to true
 *              if enabled
 * sAction: What to do, defaults to viewing the item if iId is given and to
 *          displaying a table of objects with the specified queue status otherwise
 */

require_once('path.php');
require_once(BASE.'include/incl.php');
require_once(BASE.'include/objectManager.php');
require_once(BASE.'include/application_queue.php');
require_once(BASE.'include/version_queue.php');
require_once(BASE.'include/testData_queue.php');

/* if we have no valid class name we should abort */
if(!$aClean['sClass'])
{
    echo "No class defined.\n";
    exit;
}

/* make sure the class is defined */
if(!class_exists($aClean['sClass']))
{
    echo "Class ".$aClean['sClass']." doesn't exist";
    exit;
}

$oObject = new objectManager($aClean['sClass'], $aClean['sTitle'], $aClean['iId']);

if($aClean['bIsQueue'] == 'true')
    $oObject->bIsQueue = true;
else
    $oObject->bIsQueue = false;

/* If it is rejected it is defined as queued */
if($aClean['bIsRejected'] == 'true')
{
    $oObject->bIsRejected = true;
    $oObject->bIsQueue = true;
} else
    $oObject->bIsRejected = false;

$oOtherObject = new $oObject->sClass($oObject->iId);

/* Certain actions must be performed before the header is set. */
/* processForm returns TRUE on success, or a user-readable list of errors
   on failure */
$sErrors = $oObject->processForm($aClean);

if($oObject->iId && $aClean['sAction'] == "delete")
    $oObject->delete_entry();

if($aClean['sAction'] == "add")
    $oObject->handle_anonymous_submission();

/* Provided the necessary values are present, an object's children may be moved
   without any confirmation */
if($oObject->iId && $aClean['sAction'] == "moveChildren" && $aClean['iNewId'])
    $oObject->move_children($aClean['iNewId']);

apidb_header($oObject->sTitle);

/* display a particular element */
if($oObject->iId)
{
    switch($aClean['sAction'])
    {
        case "cancel":
        $oObject->display_table($aClean); /* go back to the queue */
        break;

        case "edit":
        $oObject->display_entry_for_editing($REQUEST_URI, $sErrors);
        break;

        case "showMoveChildren":
        $oObject->display_move_children();
        break;

        default:
        $oObject->view($REQUEST_URI);
        break;
    }
} else if ($aClean['sAction'] == "add")
{
    $oObject->add_entry($REQUEST_URI, $sErrors);
} else
{
    // if displaying a queue display the help for the given queue
    if($oObject->bIsQueue)
        $oObject->display_queue_processing_help();

    $oObject->display_table($aClean);
}

apidb_footer();

?>
