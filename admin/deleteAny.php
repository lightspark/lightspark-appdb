<?php
/****************************************************/
/* code to delete versions, families and categories */
/****************************************************/

/*
 * application environment
 */
include("path.php");
include(BASE."include/incl.php");
include(BASE."include/category.php");
include(BASE."include/application.php");
include(BASE."include/mail.php");

if($_REQUEST['confirmed'] != "yes")
{
    // ask for confirmation
    // could do some Real Damage if someone accidently hits the delete button on the main category :)
    //
    // perhaps we can do this with some javascript, popup
    
    errorpage("Not confirmed");
}

if($_REQUEST['what'])
{
    switch($_REQUEST['what'])
    {
    case "category":
        // delete category and the apps in it
        $oCategory = new Category($_REQUEST['catId']);
        if(!$oCategory->delete())
            errorpage();
        else
            redirect(BASE."appbrowse.php");
        break;
    case "appFamily":
        // delete app family & all its versions
        $oApp = new Application($_REQUEST['appId']);
        if(!$oApp->delete())
            errorpage();
        else
            redirect(BASE."appbrowse.php");
        break;
    case "appVersion":
        $oVersion = new Version($_REQUEST['versionId']);
        if(!$oVersion->delete())
            errorpage();
        else
            redirect(BASE."appview.php?appId=".$_REQUEST['appId']);
        break;
    }
}
?>
