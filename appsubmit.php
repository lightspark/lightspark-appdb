<?

/* code to Submit a new application */

// Check the input of a submitted form. And output with a list
// of errors. (<ul></ul>)
function checkInput( $fields )
{
    $errors = "";

    if ( strlen($fields['queueName']) > 200 )
    {
        $errors .= "<li>Your application name is too long.</li>\n";
    }

    if ( empty( $fields['queueName']) )
    {
        $errors .= "<li>Please enter an application name.</li>\n";
    }

    if ( empty( $fields['queueVersion']) )
    {
        $errors .= "<li>Please enter an application version.</li>\n";
    }

    // No vendor entered, and nothing in the list is selected
    if ( empty( $fields['queueVendor']) and $fields['altvendor'] == '0' )
    {
        $errors .= "<li>Please enter a vendor.</li>\n";
    }

    if ( empty( $fields['queueDesc']) )
    {
        $errors .= "<li>Please enter a description of your application.</li>\n";
    }

    // Not empty and an invalid e-mail address
    if ( !empty( $fields['queueEmail']) AND !preg_match('/^[A-Za-z0-9\._-]+[@][A-Za-z0-9_-]+([.][A-Za-z0-9_-]+)+[A-Za-z]$/',$fields['queueEmail']) )
    {
        $errors .= "<li>Please enter a valid e-mail address.</li>\n";
    }

    if ( empty($errors) )
    {
        return "";
    }
    else
    {
        return $errors;
    }
}

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
global $current;

if ($_REQUEST['queueName'])
{
    // Check input and exit if we found errors
    $errors = checkInput($_REQUEST);
    if( !empty($errors) )
    {
        errorpage("We found the following errors:","<ul>$errors</ul><br>Please go back and correct them.");
        exit;
    }

    /* if the user picked the vendor we need to retrieve the vendor name */
    /* and store it into the $queueVendor */
    if($_REQUEST['altvendor'])
    {
        /* retrieve the actual name here */
        $query = "select * from vendor where vendorId = '$altvendor';";
        $result = mysql_query($query);
        if($result)
        {
            $ob = mysql_fetch_object($result);
            $_REQUEST['queueVendor'] = $ob->vendorName;
        }
    }
    
    // header
    apidb_header("Submit Application");    

    // add to queue
    $query = "INSERT INTO appQueue VALUES (null, '".
            addslashes($_REQUEST['queueName'])."', '".
            addslashes($_REQUEST['queueVersion'])."', '".
            addslashes($_REQUEST['queueVendor'])."', '".
            addslashes($_REQUEST['queueDesc'])."', '".
            addslashes($_REQUEST['queueEmail'])."', '".
            addslashes($_REQUEST['queueURL'])."', '".
            addslashes($_REQUEST['queueImage'])."',".
            "NOW());";
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
    // set email field if logged in
    if ($current && loggedin())
	{
        $email = $current->lookup_email($current->userid);
    }

	// header
    apidb_header("Submit Application");

    // show add to queue form
	
    echo '<form name="newApp" action="appsubmit.php" method="post" enctype="multipart/form-data">',"\n";

	echo "<p>This page is for submitting new applications to be added to this\n";
	echo "database. The application will be reviewed by the AppDB Administrator\n";
	echo "and you will be notified via email if this application will be added to\n";
	echo "the database.</p>\n";
	echo "<p>Please don't forget to mention which Wine version you used, how well it worked\n";
	echo "and if any workaround were needed. Haveing app descriptions just sponsoring the app\n";
	echo "(Yes, some vendor want to use the appdb for this) or saying \"I haven't tried this app with wine\" ";
	echo "won't help wine development or wine users.</p>\n";
	echo "<p>To submit screenshots, please email them to ";
	echo "<a href='mailto:appdb@winehq.org'>appdb@winehq.org</a></p>\n";

	    echo html_frame_start("New Application Form",400,"",0);

	    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
	    echo '<tr valign=top><td class=color0><b>App Name</b></td><td><input type=text name="queueName" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Version</b></td><td><input type=text name="queueVersion" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Vendor</b></td><td><input type=text name="queueVendor" value="" size=20></td></tr>',"\n";

        //alt vendor
        $x = new TableVE("view");
        echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
        $x->make_option_list("altvendor","","vendor","vendorId","vendorName");
        echo '</td></tr>',"\n";

	    echo '<tr valign=top><td class=color0><b>App URL</b></td><td><input type=text name="queueURL" value="" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>App Desc</b></td><td><textarea name="queueDesc" rows=10 cols=35></textarea></td></tr>',"\n";
	    echo '<tr valign=top><td class=color0><b>Email</b></td><td><input type=text name="queueEmail" value="'.$email.'" size=20></td></tr>',"\n";
	    echo '<tr valign=top><td class=color3 align=center colspan=2> <input type=submit value=" Submit New Application " class=button> </td></tr>',"\n";
	    echo '</table>',"\n";    

	    echo html_frame_end();

	echo "</form>";
}

apidb_footer();

?>
