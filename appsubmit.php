<?

/* code to Submit a new application */
/*   last modified 06-06-01 by Jeremy Newman */


include("path.php");
require(BASE."include/"."incl.php");
global $current;

// set email field if logged in
if ($current && loggedin())
{
    $email = $current->lookup_email($current->userid);
}

//header
apidb_header("Submit Application");


if ($queueName)
{
	// add to queue
	
	//FIXME: need to get image upload in
	
	$query = "INSERT INTO appQueue VALUES (null, '".
			addslashes($queueName)."', '".
			addslashes($queueVersion)."', '".
			addslashes($queueVendor)."', '".
            addslashes($queueDesc)."', '".
			addslashes($queueEmail)."', '".
			addslashes($queueURL)."', '".
			addslashes($queueImage)."');";
		 
	mysql_query($query);

	
	if ($error = mysql_error())
	{
		echo "<p><font color=red><b>Error:</b></font></p>\n";
		echo "<p>$error</p>\n";
	}
	else
	{
		echo "<p>Your application has been submitted for Review. You should hear back\n";
		echo "soon about the status of your submission</p>\n";
	}
	
}
else
{
	// show add to queue form
	
	echo '<form name="newApp" action="appsubmit.php" method="post" enctype="multipart/form-data">',"\n";

	echo "<p>This page is for submitting new applications to be added to this\n";
	echo "database. The application will be reviewed by the AppDB Administrator\n";
	echo "and you will be notified via email if this application will be added to\n";
	echo "the database.</p>\n";
	echo "<p>Please don't forget to mention whether you actually tested this\n";
	echo "application under Wine, which Wine version you used and how well it worked. Thank you !</p>\n";
	echo "<p>To submit screenshots, please email them to ";
	echo "<a href='mailto:appdb@winehq.com'>appdb@winehq.com</a></p>\n";

	    echo html_frame_start("New Application Form",400,"",0);

	    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
	    echo '<tr valign=top><td class=color0><b>App Name</b></td><td><input type=text name="queueName" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Version</b></td><td><input type=text name="queueVersion" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Vendor</b></td><td><input type=text name="queueVendor" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App URL</b></td><td><input type=text name="queueURL" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Desc</b></td><td><textarea name="queueDesc" rows=10 cols=35></textarea></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>Email</b></td><td><input type=text name="queueEmail" value="'.$email.'" size=20></td></tr>',"\n";
	    //echo '<tr valign=top><td class=color0><b>Image</b></td><td><input type=file name="queueImage" value="" size=15></td></tr>',"\n";
	    echo '<tr valign=top><td class=color3 align=center colspan=2> <input type=submit value=" Submit New Application " class=button> </td></tr>',"\n";
	    echo '</table>',"\n";    

	    echo html_frame_end();

	echo "</form>";
}
apidb_footer();

?>
