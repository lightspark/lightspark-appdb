<?php

/* code for displaying and processing objects that have object management
   methods */

require('path.php');
require(BASE.'include/incl.php');
require_once(BASE.'include/objectManager.php');
/* require_once(BASE.'include/application_queue.php');
require_once(BASE.'include/version_queue.php'); */

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

/* Certain actions must be performed before the header is set */
$oObject->processForm($aClean);

if($oObject->iId && $aClean['sAction'] == "delete")
    $oObject->delete_entry();

apidb_header($oObject->sTitle);

/* display a particular element */
if($oObject->iId)
{
    switch($aClean['sAction'])
    {
        case "cancel":
        $oObject->display_table(); /* go back to the queue */
        break;

        case "edit":
        $oObject->display_entry_for_editing($REQUEST_URI);
        break;

        default:
        $oObject->view($REQUEST_URI);
        break;
    }
} else if ($aClean['sAction'] == "add")
    $oObject->add_entry($REQUEST_URI);
else
{
    // if displaying a queue display the help for the given queue
    if($oObject->bIsQueue)
        $oObject->display_queue_processing_help();

    $oObject->display_table();
}

?>
