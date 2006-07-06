<?php

include("path.php");
include(BASE."include/"."incl.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    util_show_error_page();
    exit;
}

function build_app_list()
{
    $hResult = query_parameters("SELECT appId, appName FROM appFamily ORDER BY appName");
    
    echo "<select name=iAppId size=5 onChange='this.form.submit()'>\n";
    while($oRow = mysql_fetch_object($hResult))
    {
        echo "<option value=$oRow->appId>$oRow->appName</option>\n";
    }
    echo "</select>\n";
}

if($_REQUEST['sCmd'])
{
    if($_REQUEST['sCmd'] == "delete")
    {
        $hResult = query_parameters("DELETE FROM appBundle WHERE appId ='?' AND bundleId = '?'",
                                    $_REQUEST['iAppId'], $_REQUEST['iBundleId']);
        if($hResult)
            addmsg("App deleted from bundle", "green");
        else
            addmsg("Failed to delete app from bundle!", "red");
    }
    if($_REQUEST['sCmd'] == "add")
    {
        $hResult = query_parameters("INSERT INTO appBundle (bundleId, appId) VALUES".
                                    "('?', '?')",
                                    $_REQUEST['iBundleId'],
                                    $_REQUEST['iAppId']);
        if($hResult)
            addmsg("App $appId added to Bundle".$_REQUEST['iBundleId'], "green");
    }
}


apidb_header("Edit Application Bundle");

$hResult = query_parameters("SELECT bundleId, appBundle.appId, appName FROM appBundle, appFamily ".
                            "WHERE bundleId = '?' AND appFamily.appId = appBundle.appId",
                            $_REQUEST['iBundleId']);

echo html_frame_start("Apps in this Bundle","300",'',0);
echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
	    
echo "<tr class=color4>\n";
echo "    <td><font color=white> Application Name </font></td>\n";
echo "    <td><font color=white> Delete </font></td>\n";
echo "</tr>\n\n";	    

if($hResult && mysql_num_rows($hResult))
{
    $c = 1;
    while($oRow = mysql_fetch_object($hResult))
    {
        //set row color
        if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
		    
        $delete_link = "[<a href='editBundle.php?sCmd=delete&iBundleId=".$_REQUEST['iBundleId']."&iAppId=$oRow->appId'>delete</a>]";

        echo "<tr class=$bgcolor>\n";
        echo "    <td>$oRow->appName &nbsp;</td>\n";
        echo "    <td>$delete_link &nbsp;</td>\n";
        echo "</tr>\n\n";
		    
        $c++;
    }
} else if($hResult && !mysql_num_rows($hResult))
{
    /* indicate to the user that there are no apps in this bundle at the moment */
    echo "<tr>\n";
    echo " <td colspan=2 align=center><b>No applications in this bundle</b></td>\n";
    echo "</tr>\n";
}

echo "</table>\n\n";
echo html_frame_end();

echo "<form method=post action=editBundle.php>\n";

echo html_frame_start("Application List (double click to add)","",'',2);
build_app_list();
echo html_frame_end();
    
echo "<input type=\"hidden\" name=\"iBundleId\"  value=\"".$_REQUEST['iBundleId']."\">\n";
echo "<input type=\"hidden\" name=\"sCmd\" value=\"add\">\n";
echo "</form>\n";
    
apidb_footer();

?>
