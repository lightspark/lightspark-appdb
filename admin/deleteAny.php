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

if(!havepriv("admin"))
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
	    // delete a comment
	    //TODO
	    break;
	case "category":
	    // delete category and the apps in it
	    deleteCategory($_REQUEST['catId']);
	    break;
	case "appFamily":
	    // delete app family & all its versions
	    deleteAppFamily($_REQUEST['appId']);
	    break;
	case "appVersion":
	    // delete a version
	    deleteAppVersion($_REQUEST['versionId']);
	    break;
	}

    //FIXME need to redirect to the page before the confirmation page
    redirect(BASE."appbrowse.php");
}
?>
