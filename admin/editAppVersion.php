<?


include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

//check for admin privs
if(!loggedin() || (!havepriv("admin") && !$current->ownsApp($appId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

apidb_header("Edit Application Version");

$t = new TableVE("edit");


if($cmd)
{
    $statusMessage = '';
    
    //process screenshot upload
    if($cmd == "screenshot_upload")
        {    
	    if(debugging())
	    {
	        echo "<p align=center>Screenshot: ($appId) file=$imagefile size=$imagefile_size\n";
		echo " name=$imagefile_name type=$imagefile_type<br>";
	    }
	    
	    if(!copy($imagefile, "../data/screenshots/".basename($imagefile_name)))
                {
		    // whoops, copy failed. do something
	            echo html_frame_start("Edit Application","300");
	            echo "<p><b>debug: copy failed; $imagefile; $imagefile_name</b></p>\n";
	            echo html_frame_end();
	            echo html_back_link(1,"editAppVersion.php?appId=$appId&versionID=$versionId");		    
		    apidb_footer();
		    exit;
                }
		
            $query = "INSERT INTO appData VALUES (null, $appId, $versionId, 'image', ".
                "'".addslashes($screenshot_desc)."', '".basename($imagefile_name)."')";
		
            if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }
	    
	    if (mysql_query($query))
	    {
	        //success
	        $statusMessage = "<p>The image was successfully added into the database</p>\n";
	    }
	    else
	    {
	       //error
	       $statusMessage = "<p><b>Database Error!<br>".mysql_error()."<br></b></p>\n";
	       if(debugging()) { $statusMessage .= "<p>$query</p>"; }
	    }
	    
        }
		
    // display status message
    if ($statusMessage)
    {
	echo html_frame_start("Edit Application","300");
	echo "<p><b>$statusMessage</b></p>\n";
	echo html_frame_end();
	echo html_back_link(1,"editAppVersion.php?appId=$appId&versionId=$versionId");
    }
    	
}
else if($HTTP_POST_VARS)
{
    $t->update($HTTP_POST_VARS);
}
else
{
    $table = "appVersion";
    $query = "SELECT * FROM $table WHERE appId = $appId AND versionId = $versionId";

    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $t->edit($query);


      //image upload box
      echo '<form enctype="multipart/form-data" action="editAppVersion.php" name=imageForm method="post">',"\n";
      echo html_frame_start("Upload Screenshot","400","",0);
      echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
      
      echo '<tr><td class=color1>Image</td><td class=color0><input name="imagefile" type="file"></td></tr>',"\n";
      echo '<tr><td class=color1>Description</td><td class=color0><input type="text" name="screenshot_desc"></td></tr>',"\n";
      
      echo '<tr><td colspan=2 align=center class=color3><input type="submit" value="Send File"></td></tr>',"\n";
       
      echo '</table>',"\n";
      echo html_frame_end();
      echo '<input type="hidden" name="MAX_FILE_SIZE" value="10000000">',"\n";
      echo '<input type="hidden" name="cmd" value="screenshot_upload">',"\n";
      echo '<input type="hidden" name="appId" value="'.$appId.'">',"\n";
      echo '<input type="hidden" name="versionId" value="'.$versionId.'"></form>',"\n";

      echo html_back_link(1,$apidb_root."appview.php?appId=$appId&versionId=$versionId");

}

apidb_footer();

?>
