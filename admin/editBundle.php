<?php

include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin() || !havepriv("admin"))
{
    errorpage();
    exit;
}

function build_app_list()
{
    $result = mysql_query("SELECT appId, appName FROM appFamily ORDER BY appName");
    
    echo "<select name=appId size=5 onChange='this.form.submit()'>\n";
    while($ob = mysql_fetch_object($result))
	{
	    echo "<option value=$ob->appId>$ob->appName</option>\n";
	}
    echo "</select>\n";
}


if($cmd)
{
    if($cmd == "delete")
	{
	    $result = mysql_query("DELETE FROM appBundle WHERE appId = $appId AND bundleId = $bundleId");
	    if($result)
		addmsg("App deleted from bundle", "green");
	    else
		addmsg("Failed: " . mysql_error(), "red");
	}
    if($cmd == "add")
	{
	    $result = mysql_query("INSERT INTO appBundle VALUES ($bundleId, $appId)");
	    if($result)
		addmsg("App $appId added to Bundle $bundleId", "green");
	    else
                addmsg("Failed: " . mysql_error(), "red");
	}
    redirectref();
    exit;
}
else
{
    apidb_header("Edit Application Bundle");

    $result = mysql_query("SELECT bundleId, appBundle.appId, appName FROM appBundle, appFamily ".
			  "WHERE bundleId = $bundleId AND appFamily.appId = appBundle.appId");

    if($result && mysql_num_rows($result))
	{
	    echo html_frame_start("Apps in this Bundle","300",'',0);
	    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
	    
	    echo "<tr class=color4>\n";
	    echo "    <td><font color=white> Application Name </font></td>\n";
	    echo "    <td><font color=white> Delete </font></td>\n";
	    echo "</tr>\n\n";	    
	    
	    $c = 1;
	    while($ob = mysql_fetch_object($result))
		{
		    //set row color
		    if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
		    
		    $delete_link = "[<a href='editBundle.php?cmd=delete&bundleId=$bundleId&appId=$ob->appId'>delete</a>]";

	            echo "<tr class=$bgcolor>\n";
	            echo "    <td>$ob->appName &nbsp;</td>\n";
	            echo "    <td>$delete_link &nbsp;</td>\n";
	            echo "</tr>\n\n";
		    
		    $c++;
		}
		
	    echo "</table>\n\n";
	    echo html_frame_end();
	    
	}

    echo "<form method=post action=editBundle.php>\n";

    echo html_frame_start("Application List (double click to add)","",'',2);
    build_app_list();
    echo html_frame_end();
    
    echo "<input type=hidden name=bundleId  value=$bundleId>\n";
    echo "<input type=hidden name=cmd value=add>\n";
    echo "</form>\n";
    
    apidb_footer();
}


?>
