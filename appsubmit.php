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
    if(!empty($errors))
    {
        errorpage("We found the following errors:","<ul>$errors</ul><br />Please go back and correct them.");
        echo html_back_link(1);
        exit;
    }
    
    if($vendorName) $_REQUEST['vendorId']="";
    $oApplication = new Application();
// FIXME When two htmlarea will be able to live on the same page without problems under gecko, remove the <p></p> around appDescrion
    $oApplication->create($_REQUEST['appName'], "<p>".$_REQUEST['appDescription']."</p>", $_REQUEST['keywords']." *** ".$_REQUEST['vendorName'], $_REQUEST['webpage'],$_REQUEST['vendorId'], $_REQUEST['catId']);
    $oVersion = new Version();
    $oVersion->create($_REQUEST['versionName'], $_REQUEST['versionDescription'], null, null, $oApplication->iAppId);
    redirect(apidb_fullurl("index.php"));
} 
/*
 * User submitted a version
 */
elseif (isset($_REQUEST['versionName']) && is_numeric($_REQUEST['appId']))
{
    // Check input and exit if we found errors
    $errors = checkInput($_REQUEST);
    if(!empty($errors))
    {
        errorpage("We found the following errors:","<ul>$errors</ul><br />Please go back and correct them.");
        echo html_back_link(1);
        exit;
    }

    $oVersion = new Version();
    $oVersion->create($_REQUEST['versionName'], $_REQUEST['versionDescription'], null, null, $_REQUEST['appId']);
    redirect(apidb_fullurl("index.php"));
}
/*
 * User wants to submit an application or version
 */
elseif (isset($_REQUEST['apptype']))
{
// header
apidb_header("Submit Application");

//FIXME: use absolute path in htmlarea_loader.js to avoid code duplication here
?>
<!-- load HTMLArea -->
<script type="text/javascript">
_editor_url = "./htmlarea/";
_editor_lang = "en";
function initDocument() {
    config = new HTMLArea.Config();
    config.toolbar = [
                [ "bold", "italic", "underline", "strikethrough", "separator",
                  "copy", "cut", "paste", "space", "undo", "redo", "separator",
                  "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator",
                  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
                  "forecolor", "hilitecolor", "separator",
                  "inserthorizontalrule", "createlink", "inserttable" ]
        ];
    config.width = 700;
    config.pageStyle = "@import url(./application.css);";
// FIXME: when both editors and stylesheets are used, sometimes one of the editor is readonly under gecko
// var editor = new HTMLArea("editor",config);
// editor.registerPlugin(DynamicCSS);
<?php
if($_REQUEST['apptype'] == 1) // we have two editors, one for application and one for version.
{?>
    var editor2 = new HTMLArea("editor2",config);
    editor2.generate();
    editor2.registerPlugin(DynamicCSS);
<?php
}?>
// FIXME: when both editors and stylesheets are used, sometimes one of the editor is readonly under gecko
// editor.generate();
}

onload = function() {
    HTMLArea.loadPlugin("DynamicCSS");
    HTMLArea.init();
    HTMLArea.onload = initDocument;
}
</script>
<script type="text/javascript" src="./htmlarea/htmlarea.js"></script>
<?php

    /*
     * Templates
     * FIXME: put templates in config file or somewhere else.
     */
    //$sAppDescription = "<p>Enter description here</p>";
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


    // show add to queue form
    echo '<form name="newApp" action="appsubmit.php" method="post">'."\n";
    echo "<p>This page is for submitting new applications to be added to this\n";
    echo "database. The application will be reviewed by the AppDB Administrator\n";
    echo "and you will be notified via email if this application will be added to\n";
    echo "the database.</p>\n";
    echo "<p>Please don't forget to mention which Wine version you used, how well it worked\n";
    echo "and if any workaround were needed. Having app descriptions just sponsoring the app\n";
    echo "(Yes, some vendor want to use the appdb for this) or saying \"I haven't tried this app with Wine\" ";
    echo "won't help Wine development or Wine users.</p>\n";
    echo "<p>After your application has been added you'll be able to submit screenshots for it.</p>";

    // new application and version
    if ($_REQUEST['apptype'] == 1)
    {
        echo html_frame_start("New Application Form",400,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>Application name</b></td>',"\n";
        echo '<td><input type=text name="appName" value="" size="20"></td></tr>',"\n";
        echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
        echo '<td><input type=text name="versionName" value="" size="20"></td></tr>',"\n";

        // app Category
        $w = new TableVE("view");
        echo '<tr valign=top><td class="color0"><b>Category</b></td><td>',"\n";
        $w->make_option_list("catId","","appCategory","catId","catName");
        echo '</td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Vendor</b></td>',"\n";
        echo '<td><input type=text name="vendorName" value="" size="20"></td></tr>',"\n";

        // alt vendor
        $x = new TableVE("view");
        echo '<tr valign=top><td class="color0">&nbsp;</td><td>',"\n";
        $x->make_option_list("vendorId","","vendor","vendorId","vendorName");
        echo '</td></tr>',"\n";
  
        echo '<tr valign=top><td class="color0"><b>URL</b></td>',"\n";
        echo '<td><input type=text name="webpage" value="" size=20></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Keywords</b></td>',"\n";
        echo '<td><input size="80%" type="text" name="keywords"></td></tr>',"\n";


        echo '<tr valign=top><td class="color0"><b>Application Description</b></td>',"\n";
        echo '<td><p style="width:700px"><textarea cols="80" rows="20" id="editor" name="appDescription">'.$sAppDescription.'</textarea></p></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Version Description</b></td>',"\n";
        echo '<td><p style="width:700px"><textarea cols="80" rows="20" id="editor2" name="versionDescription">'.$sVersionDescription.'</textarea></p></td></tr>',"\n";


        echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
        echo '<input type=submit value="Submit New Application" class="button"> </td></tr>',"\n";
        echo '</table>',"\n";    

        echo html_frame_end();

        echo "</form>";
    }           
    // new version
    else
    {
        echo html_frame_start("New Version Form",400,"",0);

        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // app parent
        $x = new TableVE("view");
        echo '<tr valign=top><td class=color0><b>Application</b></td><td>',"\n";
        $x->make_option_list("appId",$_REQUEST['appId'],"appFamily","appId","appName");
        echo '</td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
        echo '<td><input type=text name="versionName" size="20"></td></tr>',"\n";
    
        echo '<tr valign=top><td class=color0><b>Version description</b></td>',"\n";
        echo '<td><p style="width:700px"><textarea cols="80" rows="20" id="editor" name="versionDescription">'.$sVersionDescription.'</textarea></p></td></tr>',"\n";

        echo '<tr valign=top><td class="color3" align="center" colspan="2">',"\n";
        echo '<input type=submit value="Submit New Version" class="button"> </td></tr>',"\n";	  
  	  
        echo '</table>',"\n";    

        echo html_frame_end();

        echo "</form>";
    }
}
apidb_footer();
?>
