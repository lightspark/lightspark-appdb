<?php
/****************************************************/
/* code to delete versions, families and categories */
/****************************************************/

/*
 * application environment
 */
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/category.php");
require_once(BASE."include/application.php");
require_once(BASE."include/monitor.php");
require_once(BASE."include/testData.php");

$aClean = array(); //filtered user input

$aClean['sConfirmed'] = makeSafe($_REQUEST['sConfirmed']);
$aClean['sWhat'] = makeSafe($_REQUEST['sWhat']);
$aClean['iCatId'] = makeSafe($_REQUEST['iCatId']);
$aClean['iAppId'] = makeSafe($_REQUEST['iAppId']);
$aClean['iVersionId'] = makeSafe($_REQUEST['iVersionId']);

if($aClean['sConfirmed'] != "yes")
{
    // ask for confirmation
    // could do some Real Damage if someone accidently hits the delete button on the main category :)
    //
    // perhaps we can do this with some javascript, popup
    
    util_show_error_page_and_exit("Not confirmed");
}

if($aClean['sWhat'])
{
    switch($aClean['sWhat'])
    {
    case "category":
        // delete category and the apps in it
        $oCategory = new Category($aClean['iCatId']);
        if(!$oCategory->delete())
            util_show_error_page_and_exit();
        else
            util_redirect_and_exit(BASE."appbrowse.php");
        break;
    case "appFamily":
        // delete app family & all its versions
        $oApp = new Application($aClean['iAppId']);
        if(!$oApp->delete())
            util_show_error_page_and_exit();
        else
            util_redirect_and_exit(BASE."appbrowse.php");
        break;
    case "appVersion":
        $oVersion = new Version($aClean['iVersionId']);
        if(!$oVersion->delete())
            util_show_error_page_and_exit();
        else
            util_redirect_and_exit(BASE."appview.php?iAppId=".$aClean['iAppId']);
        break;
    }
}
?>
