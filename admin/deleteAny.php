<?


include("path.php");
include(BASE."include/"."incl.php");


if(!loggedin() || !havepriv("admin"))
{
    errorpage();
    exit;
}

if($confirmed != "yes")
{
    // ask for confirmation
    // could do some Real Damage if someone accidently hits the delete button on the main category :)
    //
    // perhaps we can do this with some javascript, popup
    
    errorpage("Not confirmed");
}


function deleteCategory($catId)
{
    $r = mysql_query("SELECT appId FROM appFamily WHERE catId = $catId");
    if($r)
	{
	    while($ob = mysql_fetch_object($r))
		deleteAppFamily($ob->appId);
	    $r = mysql_query("DELETE FROM appCategory WHERE catId = $catId");
       
	    if($r)
		addmsg("Category $catId deleted", "green");
	    else
		addmsg("Failed to delete category $catId:".mysql_error(), "red");
	}
    else
	{
	    addmsg("Failed to delete category $catId: ".mysql_error(), "red");
	}
}

function deleteAppFamily($appId)
{
    $r = mysql_query("DELETE FROM appFamily WHERE appId = $appId");
    if($r)
	{
	    $r = mysql_query("DELETE FROM appVersion WHERE appId = $appId");
	    if($r)
		addmsg("Application and versions deleted", "green");
	    else
		addmsg("Failed to delete appVersions: " . mysql_error(), "red");
	}
    else
	addmsg("Failed to delete appFamily $appId: " . mysql_error(), "red");
    
}

function deleteAppVersion($versionId)
{
    $r = mysql_query("DELETE FROM appVersion WHERE versionId = $versionId");
    if($r)
	addmsg("Application Version $versionId deleted", "green");
    else
	addmsg("Failed to delete appVersion $versionId: " . mysql_error(), "red");
}



if($what)
{
    switch($what)
	{
	case "comment":
	    // delete a comment
	    //TODO
	    break;
	case "category":
	    // delete category and the apps in it
	    deleteCategory($catId);
	    break;
	case "appFamily":
	    // delete app family & all its versions
	    deleteAppFamily($appId);
	    break;
	case "appVersion":
	    // delete a version
	    deleteAppVersion($versionId);
	    break;
	}

    //FIXME need to redirect to the page before the confirmation page
    redirect($apidb_root."appbrowse.php");
}


?>
