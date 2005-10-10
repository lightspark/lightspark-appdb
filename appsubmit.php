<?php
/************************************/
/* code to Submit a new application */
/************************************/
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/mail.php");
require(BASE."include/application.php");

if(!$_SESSION['current']->isLoggedIn())
{
    // you must be logged in to submit app
    apidb_header("Please login");
    echo "To submit an application to the database you must be logged in. Please <a href=\"account.php?cmd=login\">login now</a> or create a <a href=\"account.php?cmd=new\">new account</a>.","\n";
    exit;
}

/*
 * User submitted an application
 */
if (isset($_REQUEST['appName']))
{
    $errors = "";

    // Check input and exit if we found errors
    $oApplication = new Application();
    $errors .= $oApplication->CheckOutputEditorInput();

    $oVersion = new Version();
    $errors .= $oVersion->CheckOutputEditorInput();

    if(empty($errors))
    {
        if($_REQUEST['appVendorName'])
        {
             $_REQUEST['vendorId']="";
             //FIXME: fix this when we fix vendor submission
             if($_SESSION['current']->hasPriv("admin"))
             {
                $oVendor = new Vendor();
                $oVendor->create($_REQUEST['appVendorName'],$_REQUEST['appWebpage']);
             }
        }
        $oApplication->GetOutputEditorValues(); /* load the values from $_REQUEST */

        //FIXME: remove this when we fix vendor submission
        $oApplication->sKeywords = $_REQUEST['appKeywords']." *** ".$_REQUEST['appVendorName'];
        
        $oApplication->create();

        $oVersion->GetOutputEditorValues();
        $oVersion->iAppId = $oApplication->iAppId; /* get the iAppId from the application that was just created */
        $oVersion->create();

        redirect(apidb_fullurl("index.php"));
    }

} 

/*
 * User submitted a version
 */
elseif (isset($_REQUEST['versionName']) && is_numeric($_REQUEST['appId']))
{
    // Check input and exit if we found errors

    $oVersion = new Version();
    $errors = $oVersion->CheckOutputEditorInput();

    if(empty($errors))
    {
        $oVersion->GetOutputEditorValues();
        $oVersion->create();
        redirect(apidb_fullurl("index.php"));
    }
}

/*
 * User wants to submit an application or version
 */
if (isset($_REQUEST['apptype']))
{
    // header
    apidb_header("Submit Application");

    // show add to queue form
    echo '<form name="newApp" action="appsubmit.php" method="post">'."\n";
    echo "<p>This page is for submitting new applications to be added to this\n";
    echo "database. The application will be reviewed by the AppDB Administrator\n";
    echo "and you will be notified via email if this application will be added to\n";
    echo "the database.</p>\n";
    echo "<p><h2>Before continuing please check that you have:</h2>\n";
    echo "<ul>\n";
    if ($_REQUEST['apptype'] == 1)
    {
        echo " <li>Searched for this application in the database.  Duplicate submissions will be rejected.</li>\n";
        echo " <li>Really want to submit an application instead of a new version of an application\n";
        echo "   that is already in the database. If this is the case browse to the application\n";
        echo "   and click on 'Submit new version'</li>\n";
    }
    echo " <li>Entered a valid version for this application.  This is the application\n";
    echo "   version, NOT the wine version(which goes in the testing results section of the template)</li>\n";
    echo " <li>Tested this application under Wine.  There are tens of thousands of applications\n";
    echo "   for windows, we don't need placeholder entries in the database.  Please enter as complete \n";
    echo "   as possible testing results in the version template provided below</li>\n";
    echo "</ul></p>";
    echo "<p>Please don't forget to mention which Wine version you used, how well it worked\n";
    echo "and if any workaround were needed. Having app descriptions just sponsoring the app\n";
    echo "(Yes, some vendors want to use the appdb for this) or saying \"I haven't tried this app with Wine\" ";
    echo "won't help Wine development or Wine users.</p>\n";
    echo "<b><span style=\"color:red\">Please only submit applications/versions that you have tested.\n";
    echo "Submissions without testing information or not using the provided template will be rejected.\n";
    echo "If you can't see the in-browser editors below please try Firefox, Mozilla or Opera browsers.\n</span></b>";
    echo "<p>After your application has been added you'll be able to submit screenshots for it, post";
    echo " messages in its forums or become a maintainer to help others trying to run the application.</p>";
    if(!empty($errors))
    {
        echo '<font color="red">',"\n";
        echo '<p class="red"> We found the following errors:</p><ul>'.$errors.'</ul>Please correct them.';
        echo '</font><br />',"\n";
        echo '<p></p>',"\n";
    }

    if($_REQUEST['apptype'] == 1 && (trim(strip_tags($_REQUEST['appDescription']))==""))
    {
        $_REQUEST['appDescription'] = GetDefaultApplicationDescription();
    }

    if(trim(strip_tags($_REQUEST['versionDescription']))=="")
    {
        $_REQUEST['versionDescription'] = GetDefaultVersionDescription();
    }

    $oApp = new Application();
    $oApp->GetOutputEditorValues(); /* retrieve the values from the current $_REQUEST */
    $oVersion = new Version();
    $oVersion->GetOutputEditorValues(); /* retrieve the values from the current $_REQUEST */

    /* output the appropriate editors depending on whether we are processing an */
    /* application and a version or just a version */
    if($_REQUEST['apptype'] == 1)
    {
        $oApp->OutputEditor($_REQUEST['appVendorName']);
        $oVersion->OutputEditor(false);
    } else
    {
        $oVersion->OutputEditor(true);
    }

    echo '<input type="hidden" name="apptype" value="'.$_REQUEST['apptype'].'">',"\n";

    // new application and version
    if ($_REQUEST['apptype'] == 1)
    {
        echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
        echo '<input type=submit value="Submit New Application" class="button"> </td></tr>',"\n";
    }
    // new version
    else
    {
        echo '<tr valign=top><td class="color3" align="center" colspan="2">',"\n";
        echo '<input type=submit value="Submit New Version" class="button"> </td></tr>',"\n";	  
    }
    echo '</table>',"\n";    
    echo "</form>";
}
apidb_footer();
?>
