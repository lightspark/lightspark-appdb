<?


include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin() || !havepriv("admin"))
{
    errorpage("Insufficient Privileges","You do not have access to this section of the website");
    exit;
}

function build_user_list()
{
    $result = mysql_query("SELECT username,email FROM user_list ORDER BY username");
    
    echo "<select name=username size=15 onChange='this.form.ownerName.value = this.value; this.form.submit()'>\n";
    while($ob = mysql_fetch_object($result))
	{
	    echo "<option value='$ob->username'>$ob->username - $ob->email</option>\n";
	}
    echo "</select>\n";
}


if($cmd)
{
    if($cmd == "delete")
	{
	    $result = mysql_query("DELETE FROM appOwners WHERE appId = $appId AND ownerId = $ownerId");
	    if($result)
		{
		    addmsg("Owner deleted", "green");
		    redirectref();
		}
	    else
		echo "Failed: " . mysql_error();
	}
    if($cmd == "add")
	{
	    $result = mysql_query("SELECT userid FROM user_list WHERE username = '$ownerName'");
	    if($result)
		{
		    $ob = mysql_fetch_object($result);
		    if(!$ob || !$ob->userid)
			{
			    errorpage("Not Found!","User $ownerName was not found in the database");
			    exit;
			}
		    $result = mysql_query("INSERT INTO appOwners VALUES ($appId, $ob->userid)");
		    if(!$result)
			{
			    errorpage("Failed!",mysql_error());
			    exit;
			}
		    addmsg("Owner $ownerName added", "green");
		    redirectref();
		}
	    else
                echo "Failed: " . mysql_error();
	}
}
else
{
    apidb_header("Edit Application Owners");

    $result = mysql_query("SELECT ownerId,username FROM appOwners, user_list WHERE appId = $appId AND userid = ownerId");

    if($result && mysql_num_rows($result))
	{
	    echo html_frame_start("Current Owners","300",'',0);
	    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
	    
	    echo "<tr class=color4>\n";
	    echo "    <td><font color=white> User Name </font></td>\n";
	    echo "    <td><font color=white> Delete </font></td>\n";
	    echo "</tr>\n\n";	    
	    
	    $c = 1;
	    while($ob = mysql_fetch_object($result))
		{
		    //set row color
		    if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
		    
		    $delete_link = "[<a href='editAppOwners.php?cmd=delete&appId=$appId&ownerId=$ob->ownerId'>delete</a>]";

	            echo "<tr class=$bgcolor>\n";
	            echo "    <td>$ob->username &nbsp;</td>\n";
	            echo "    <td>$delete_link &nbsp;</td>\n";
	            echo "</tr>\n\n";
		    
		    $c++;
		}
		
	    echo "</table>\n\n";
	    echo html_frame_end();		
	    
	}

    echo "<form method=post action=editAppOwners.php>\n";

    echo html_frame_start("Manually Add User","300",'',5);
    echo "<input type=text name=ownerName size=15>\n";
    echo "<input type=submit value=' Add User ' class=button>\n";
    echo html_frame_end();


    echo html_frame_start("User List","",'',2);
    build_user_list();
    echo html_frame_end();
    
    echo "<input type=hidden name=appId  value=$appId>\n";
    echo "<input type=hidden name=cmd value=add>\n";
    echo "</form>\n";
    
    apidb_footer();
}


?>
