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

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage();
    exit;
}

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
	case "comment":
            $oComment = new Comment($_REQUEST['commentId']);
            $oComment->delete();
            redirect(BASE."appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']);
	    break;
	case "category":
	    // delete category and the apps in it
            $oCategory = new Category($_REQUEST['catId']);
            $oCategory->delete();
            redirect(BASE."appbrowse.php");
	    break;
	case "appFamily":
	    // delete app family & all its versions
            $oApp = new Application($_REQUEST['appId']);
            $oApp->delete();
            redirect(BASE."appbrowse.php");
	    break;
	case "appVersion":
	    // delete a version
            $oVersion = new Application($_REQUEST['versionId']);
            $oVersion->delete();
            redirect(BASE."appview.php?appId=".$_REQUEST['appId']);
	    break;
	}
}
?>
