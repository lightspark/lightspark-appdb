<?


include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

global $apidb_root;

//FIXME: need to check for admin privs
if(!loggedin() || (!havepriv("admin") && !$current->ownsApp($appId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

apidb_header("Edit Application Family");

$t = new TableVE("edit");

if($cmd)
{
    $statusMessage = '';
    
    //process add URL
    if($cmd == "add_url")
	{
	    $query = "INSERT INTO appData VALUES (null, $appId, 0, 'url', ".
		"'$url_desc', '$url')";
	    
	    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }
	    
	    if (mysql_query($query))
	    {
	        //success
	        $statusMessage = "<p>The URL was successfully added into the database</p>\n";
	    }
	    else
	    {
	       //error
	       $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
	    }
	}
	
    // display status message
    if ($statusMessage)
    {
	echo html_frame_start("Edit Application","300");
	echo "<p><b>$statusMessage</b></p>\n";
	echo html_frame_end();
	echo html_back_link(1,"editAppFamily.php?appId=$appId");
    }
	
}
else if($HTTP_POST_VARS)
{
    // commit changes of form to database
    $t->update($HTTP_POST_VARS);
}
else
{

    // show form
    $table = "appFamily";
    $query = "SELECT * FROM $table WHERE appId = $appId";

    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $t->edit($query);

      //url entry box
      echo '<form enctype="multipart/form-data" action="editAppFamily.php" method="post">',"\n";
      echo html_frame_start("Add URL","400","",0);
      echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
      echo '<tr><td class=color1>URL</td><td class=color0><input name="url" type="text"></td></tr>',"\n";
      echo '<tr><td class=color1>Description</td><td class=color0><input type="text" name="url_desc"></td></tr>',"\n";
      
      echo '<tr><td colspan=2 align=center class=color3><input type="submit" value="Add URL"></td></tr>',"\n";
           
      echo '</table>',"\n";
      echo html_frame_end();
      echo '<input type="hidden" name="cmd" value="add_url">',"\n";
      echo '<input type="hidden" name="appId" value="'.$appId.'"></form>',"\n";

      echo html_back_link(1,$apidb_root."appview.php?appId=$appId");

}

apidb_footer();

?>
