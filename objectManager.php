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
require_once(BASE.'include/browse_newest_apps.php');
require_once(BASE.'include/bugs.php');

/* if we have no valid class name we should abort */
if(!isset($aClean['sClass']))
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

$aClean['iId'] = isset($aClean['iId']) ? $aClean['iId'] : 0;

$oObject = new objectManager($aClean['sClass'], $aClean['sTitle'], $aClean['iId']);

if(isset($aClean['bIsQueue']) && $aClean['bIsQueue'] == 'true')
  $oObject->setIsQueue(true);
else
  $oObject->setIsQueue(false);

if(isset($aClean['sReturnToTitle']))
  $oObject->setReturnToTitle($aClean['sReturnToTitle']);

if(isset($aClean['sReturnTo']))
  $oObject->setReturnTo($aClean['sReturnTo']);

$aClean['bIsRejected'] = isset($aClean['bIsRejected']) ? $aClean['bIsRejected'] : false;
/* If it is rejected it is defined as queued */
if($aClean['bIsRejected'] == 'true')
{
    $oObject->setIsRejected(true);
    $oObject->setIsQueue(true);
} else
{
    $oObject->setIsRejected(false);
}

$oObject->getMultiPageDataFromInput($aClean);

$sClass = $oObject->getClass();
$oOtherObject = new $sClass($oObject->getId());

/* Certain actions must be performed before the header is set. */
/* processForm returns TRUE on success, or a user-readable list of errors
   on failure */
$sErrors = $oObject->processForm($aClean);

if(array_key_exists("sAction", $aClean))
    $sAction = $aClean['sAction'];
else
    $sAction = "";

/* Handle things that need to be done before showing any output */
if($sAction)
{
    switch($aClean['sAction'])
    {
        case 'add':
            $oObject->handle_anonymous_submission();
            break;

        case 'moveChildren':
            /* Provided the necessary values are present, an object's children may be moved
            without any confirmation */
            if($oObject->getId() && $aClean['iNewId'])
                $oObject->move_children($aClean['iNewId']);
            break;

        case 'doPurgeRejected':
            /* Purge some or all rejected entries */
            $oObject->purgeRejected($aClean);
            break;
    }
}

/* If no action is specified, use a default depending on other parameters */
if(!$sAction)
{
    if($oObject->getId())
        $sAction = "view";
}

apidb_header($oObject->get_title($sAction));

/* display a particular element */
if($oObject->getId() && $sAction != "add")
{
    switch($sAction)
    {
        case "cancel":
        $oObject->display_table($aClean); /* go back to the queue */
        break;

        case "edit":
        $oObject->display_entry_for_editing($aClean, $sErrors);
        break;

        case "showMoveChildren":
        $oObject->display_move_children();
        break;

        case "delete":
        $oObject->delete_prompt();
        break;

        case "view":
        $oObject->view($_SERVER['REQUEST_URI'], $aClean);
        break;
    }
} else
{
    switch($sAction)
    {
        case 'add':
            $oObject->add_entry($aClean, $sErrors);
            break;

        case 'purgeRejected':
            $oObject->displayPurgeRejected();
            break;

        default:
            $oObject->display_table($aClean);
    }
}

apidb_footer();

?>
