<?php
/************************************/
/* code to Submit a new application */
/************************************/
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/mail.php");
require(BASE."include/application.php");

    /*
     * Templates
     * FIXME: put templates in config file or somewhere else.
     */
    $sAppDescription = "<p>Enter a description of the application here</p>";
    $sVersionDescription = "<p>This is a template; enter version-specific description here</p>
                            <p>
                               <span class=\"title\">Wine compatibility</span><br />
                               <span class=\"subtitle\">What works:</span><br />
                               - settings<br />
                               - help<br />
                               <br /><span class=\"subtitle\">What doesn't work:</span><br />
                               - erasing<br />
                               <br /><span class=\"subtitle\">What was not tested:</span><br />
                               - burning<br />
                               </p>
                               <p><span class=\"title\">Tested versions</span><br /><table class=\"historyTable\" width=\"90%\" border=\"1\">
                            <thead class=\"historyHeader\"><tr>
                            <td>App. version</td><td>Wine version</td><td>Installs?</td><td>Runs?</td><td>Rating</td>
                            </tr></thead>
                            <tbody><tr>
                            <td class=\"gold\">3.23</td><td class=\"gold\">20050111</td><td class=\"gold\">yes</td><td class=\"gold\">yes</td><td class=\"gold\">Gold</td>
                            </tr><tr>
                            <td class=\"silver\">3.23</td><td class=\"silver\">20041201</td><td class=\"silver\">yes</td><td class=\"silver\">yes</td><td class=\"silver\">Silver</td>
                            </tr><tr>
                            <td class=\"bronze\">3.21</td><td class=\"bronze\">20040615</td><td class=\"bronze\">yes</td><td class=\"bronze\">yes</td><td class=\"bronze\">Bronze</td>
                            </tr></tbody></table></p><p><br /></p>";

if(!$_SESSION['current']->isLoggedIn())
{
    // you must be logged in to submit app
    apidb_header("Please login");
    echo "To submit an application to the database you must be logged in. Please <a href=\"account.php?cmd=login\">login now</a> or create a <a href=\"account.php?cmd=new\">new account</a>.","\n";
    exit;
}

// Check the input of a submitted form. And output with a list
// of errors. (<ul></ul>)
function checkInput($fields)
{
  $errors = "";

  if (strlen($fields['appName']) > 200 )
    $errors .= "<li>Your application name is too long.</li>\n";

  if (empty($fields['appName']) && !$fields['appId'])
    $errors .= "<li>Please enter an application name.</li>\n";

  if (empty($fields['versionName']))
    $errors .= "<li>Please enter an application version.</li>\n";

  // No vendor entered, and nothing in the list is selected
  if (empty($fields['vendorName']) && !$fields['vendorId'] && !$fields['appId'])
    $errors .= "<li>Please enter a vendor.</li>\n";

  if (empty($fields['appDescription']) && !$fields['appId'])
    $errors .= "<li>Please enter a description of your application.</li>\n";

  if (empty($errors))
    return "";
  else
    return $errors;
}

/*
 * User submitted an application
 */
if (isset($_REQUEST['appName']))
{
    // Check input and exit if we found errors

    $errors = checkInput($_REQUEST);
    if(empty($errors))
    {
        if($vendorName) $_REQUEST['vendorId']="";

        $oApplication = new Application();
        $oApplication->create($_REQUEST['appName'], $_REQUEST['appDescription'], $_REQUEST['keywords']." *** ".$_REQUEST['vendorName'], $_REQUEST['webpage'], $_REQUEST['vendorId'], $_REQUEST['catId']);
        $oVersion = new Version();
        $oVersion->create($_REQUEST['versionName'], $_REQUEST['versionDescription'], null, null, $oApplication->iAppId);
        redirect(apidb_fullurl("index.php"));
    }

} 

/*
 * User submitted a version
 */
elseif (isset($_REQUEST['versionName']) && is_numeric($_REQUEST['appId']))
{
    // Check input and exit if we found errors
    $errors = checkInput($_REQUEST);
    if(empty($errors))
    {

        $oVersion = new Version();
        $oVersion->create($_REQUEST['versionName'], $_REQUEST['versionDescription'], null, null, $_REQUEST['appId']);
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

    // new application and version
    if ($_REQUEST['apptype'] == 1)
    {
        HtmlAreaLoaderScript(array("editor", "editor2"));

        echo html_frame_start("New Application Form",400,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>Application name</b></td>',"\n";
        echo '<td><input type="text" name="appName" value="'.$_REQUEST['appName'].'" size="20"></td></tr>',"\n";

        // app Category
        $w = new TableVE("view");
        echo '<tr valign=top><td class="color0"><b>Category</b></td><td>',"\n";
        $w->make_option_list("catId",$_REQUEST['catId'],"appCategory","catId","catName");
        echo '</td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Vendor</b></td>',"\n";
        echo '<td><input type=text name="vendorName" value="'.$_REQUEST['vendorName'].'" size="20"></td></tr>',"\n";

        // alt vendor
        $x = new TableVE("view");
        echo '<tr valign=top><td class="color0">&nbsp;</td><td>',"\n";
        $x->make_option_list("vendorId",$_REQUEST['vendorId'],"vendor","vendorId","vendorName");
        echo '</td></tr>',"\n";
  
        echo '<tr valign=top><td class="color0"><b>URL</b></td>',"\n";
        echo '<td><input type=text name="webpage" value="'.$_REQUEST['webpage'].'" size=20></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Keywords</b></td>',"\n";
        echo '<td><input size="80%" type="text" name="keywords" value="'.$_REQUEST['keywords'].'"></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Application Description</b></td>',"\n";
        if(trim(strip_tags($_REQUEST['appDescription']))=="")
        {
            $_REQUEST['appDescription'] = $sAppDescription;
        }   
        echo '<td><p><textarea cols="80" rows="20" id="editor" name="appDescription">';
        echo $_REQUEST['appDescription'].'</textarea></p></td></tr>',"\n";

    }           
    // new version
    else
    {
        HtmlAreaLoaderScript(array("editor2"));

        echo html_frame_start("New Version Form",400,"",0);

        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // app parent
        $x = new TableVE("view");
        echo '<tr valign=top><td class=color0><b>Application</b></td><td>',"\n";
        $x->make_option_list("appId",$_REQUEST['appId'],"appFamily","appId","appName");
        echo '</td></tr>',"\n";
    }
    echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
    echo '<td><input type="text" name="versionName" value="'.$_REQUEST['versionName'].'" size="20"></td></tr>',"\n";
    if(trim(strip_tags($_REQUEST['versionDescription']))=="")
    {
        $_REQUEST['versionDescription'] = $sVersionDescription;
    }   
    echo '<tr valign=top><td class=color0><b>Version description</b></td>',"\n";
    echo '<td><p style="width:700px">',"\n";
    echo '<textarea cols="80" rows="20" id="editor2" name="versionDescription">',"\n";

    /* if magic quotes are enabled we need to strip them before we output the 'versionDescription' */
    /* again.  Otherwise we will stack up magic quotes each time the user resubmits after having */
    /* an error */
    if(get_magic_quotes_gpc())
        echo stripslashes($_REQUEST['versionDescription']).'</textarea></p></td></tr>',"\n";
    else
        echo $_REQUEST['versionDescription'].'</textarea></p></td></tr>',"\n";

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
    echo html_frame_end();
    echo "</form>";
}
apidb_footer();
?>
