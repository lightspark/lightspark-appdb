<?php
/************************************/
/* code to Submit a new application */
/************************************/

# ENVIRONMENT AND HEADER
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");

// Send user to the correct branch of code even if they try to bypass
// the first page (appsubmit.php without parameters)
if(!loggedin())
{
  unset($_REQUEST['queueName']);
  unset($_REQUEST['apptype']);
}

// Check the input of a submitted form. And output with a list
// of errors. (<ul></ul>)
function checkInput($fields)
{
  $errors = "";

  if (strlen($fields['queueName']) > 200 )
    $errors .= "<li>Your application name is too long.</li>\n";

  if (empty( $fields['queueName']))
    $errors .= "<li>Please enter an application name.</li>\n";

  if (empty( $fields['queueVersion']))
    $errors .= "<li>Please enter an application version.</li>\n";

  // No vendor entered, and nothing in the list is selected
  if (empty( $fields['queueVendor']) and $fields['altvendor'] == '0')
    $errors .= "<li>Please enter a vendor.</li>\n";

  if (empty( $fields['queueDesc']))
    $errors .= "<li>Please enter a description of your application.</li>\n";

  // Not empty and an invalid e-mail address
  if (!empty( $fields['queueEmail'])
      AND !preg_match('/^[A-Za-z0-9\._-]+[@][A-Za-z0-9_-]+([.][A-Za-z0-9_-]+)+[A-Za-z]$/',
    $fields['queueEmail']))
  {
    $errors .= "<li>Please enter a valid e-mail address.</li>\n";
  }

  if (empty($errors))
    return "";
  else
    return $errors;
}

#################################
# USER SUBMITTED APP OR VERSION #
#################################
if (isset($_REQUEST['queueName']))
{
    // Check input and exit if we found errors
    $errors = checkInput($_REQUEST);
    if( !empty($errors) )
    {
        errorpage("We found the following errors:","<ul>$errors</ul><br>Please go back and correct them.");
        echo html_back_link(1);
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
            "NOW()".",".
            addslashes($_REQUEST['queueCatId']).");";

    mysql_query($query);

    if ($error = mysql_error())
    {
        echo "<p><font color=red><b>Error:</b></font></p>\n";
        echo "<p>$error</p>\n";
    } else {
        echo "<p>Your application has been submitted for Review. You should hear back\n";
        echo "soon about the status of your submission</p>\n";
    }
}

#######################################
# USER WANTS TO SUBMIT APP OR VERSION #
#######################################
else if (isset($_REQUEST['apptype']))
{
  // set email field if logged in
  if (loggedin())
    $email = $_SESSION['current']->lookup_email($_SESSION['current']->userid);

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

  # NEW APPLICATION
  if ($_REQUEST['apptype'] == 1)
  {
    echo html_frame_start("New Application Form",400,"",0);
    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
    echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
    echo '<td><input type=text name="queueName" value="" size=20></td></tr>',"\n";
    echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
    echo '<td><input type=text name="queueVersion" value="" size=20></td></tr>',"\n";

    // app Category
    $w = new TableVE("view");
    echo '<tr valign=top><td class=color0><b>Category</b></td><td>',"\n";
    $w->make_option_list("queueCatId","","appCategory","catId","catName");
    echo '</td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>App Vendor</b></td>',"\n";
    echo '<td><input type=text name="queueVendor" value="" size=20></td></tr>',"\n";

    // alt vendor
    $x = new TableVE("view");
    echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
    $x->make_option_list("altvendor","","vendor","vendorId","vendorName");
    echo '</td></tr>',"\n";
    
    echo '<tr valign=top><td class=color0><b>App URL</b></td>',"\n";
    echo '<td><input type=text name="queueURL" value="" size=20></td></tr>',"\n";
    
    echo '<tr valign=top><td class=color0><b>App Desc</b></td>',"\n";
    echo '<td><textarea name="queueDesc" rows=10 cols=35></textarea></td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>Email</b></td>',"\n";
    echo '<td><input type=text name="queueEmail" value="'.$email.'" size=20></td></tr>',"\n";

    echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
    echo '<input type=submit value=" Submit New Application " class=button> </td></tr>',"\n";
  
	  
    echo '</table>',"\n";    

    echo html_frame_end();

    echo "</form>";
  }
            
  # NEW VERSION
  else
  {
    echo html_frame_start("New Version Form",400,"",0);

    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

    // app parent
    $x = new TableVE("view");
    echo '<tr valign=top><td class=color0><b>App Parent</b></td><td>',"\n";
    $x->make_option_list("queueName",$_REQUEST['appId'],"appFamily","appId","appName");
    echo '</td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
    echo '<td><input type=text name="queueVersion" size=20></td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>App URL</b></td>',"\n";
    echo '<td><input type=text name="queueURL" size=20></td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>App Desc</b></td>',"\n";
    echo '<td><textarea name="queueDesc" rows=10 cols=35></textarea></td></tr>',"\n";

    echo '<tr valign=top><td class=color0><b>Email</b></td>',"\n";
    echo '<td><input type=text name="queueEmail" value="'.$email.'" size=20></td></tr>',"\n";

    echo '<input type=hidden name="queueVendor" value="">',"\n";
    echo '<input type=hidden name="queueCatId" value=-1>',"\n";

    echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
    echo '<input type=submit value=" Submit New Version" class=button> </td></tr>',"\n";	  
	  
    echo '</table>',"\n";    

    echo html_frame_end();

    echo "</form>";
  }
}

##########################
# HOME PAGE OF APPSUBMIT #
##########################
else
{ 
  if(!loggedin())
  {
    // you must be logged in to submit app
    apidb_header("Please login");
    echo "To submit an application to the database you must be logged in. Please <a href=\"account.php?cmd=login\">login now</a> or create a <a href=\"account.php?cmd=new\">new account</a>.","\n";
  }
  else
  {
    // choose type of app
    apidb_header("Choose Application Type");
    echo "Please search through the database first. If you cannot find your application in the database select ","\n";
    echo "<b>New Application</b>.","\n";
    echo "If you have found your application but have not found your version then choose <b>New Version</b>.","\n";
    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
    echo "<tr valign=top><td class=color0 align=center><a href='appsubmit.php?apptype=1'>New Application</a></td>","\n";
    echo "<td class=color0 align=center><a href='appsubmit.php?apptype=2'>New Version</a></td></tr>","\n";
    echo '</table>',"\n";    
  }
}

apidb_footer();
?>
