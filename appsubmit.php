<?php
/************************************/
/* code to Submit a new application */
/************************************/
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");

// Send user to the correct branch of code even if they try to bypass
// the first page (appsubmit.php without parameters)
if(!$_SESSION['current']->isLoggedIn())
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
        errorpage("We found the following errors:","<ul>$errors</ul><br />Please go back and correct them.");
        echo html_back_link(1);
        exit;
    }

    /* if the user picked the vendor we need to retrieve the vendor name */
    /* and store it into the $queueVendor */
    if(isset($_REQUEST['altvendor']))
    {
        /* retrieve the actual name here */
        $sQuery = "select * from vendor where vendorId = ".$_REQUEST['altvendor'].";";
        $result = query_appdb($sQuery);
        if($result && mysql_num_rows($result) > 0 )
        {
            $ob = mysql_fetch_object($result);
            $_REQUEST['queueVendor'] = $ob->vendorName;
        }
    }
    
    $aFields = compile_insert_string(
                    array( 'queueName' => $_REQUEST['queueName'],
                    'queueVersion' => $_REQUEST['queueVersion'],
                    'queueVendor' => $_REQUEST['queueVendor'],
                    'queueDesc' => $_REQUEST['queueDesc'],
                    'queueEmail' => $_REQUEST['queueEmail'],
                    'queueURL' => $_REQUEST['queueURL'],
                    'queueCatId' => $_REQUEST['queueCatId']));

    $sQuery = "INSERT INTO appQueue ({$aFields['FIELDS']},`submitTime`) VALUES ({$aFields['VALUES']}, NOW())";
    
    if(query_appdb($sQuery))
    {
        addmsg("Your application has been submitted for review. You should hear back soon".
               " about the status of your submission.",'green');
    }
    
    redirect(apidb_fullurl("index.php"));
}

#######################################
# USER WANTS TO SUBMIT APP OR VERSION #
#######################################
else if (isset($_REQUEST['apptype']))
{
//FIXME: use absolute path in htmlarea_loader.js to avoid code duplication here
?>
<link rel="stylesheet" href="./application.css" type="text/css">
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
    var editor = new HTMLArea("editor",config);
    editor.config.pageStyle = "@import url(./application.css);";
    editor.registerPlugin(DynamicCSS);
    editor.generate();
}

onload = function() {
    HTMLArea.loadPlugin("DynamicCSS");
    HTMLArea.init();
    HTMLArea.onload = initDocument;
}
</script>
<script type="text/javascript" src="./htmlarea/htmlarea.js"></script>
<?php
    // set email field if logged in
    if ($_SESSION['current']->isLoggedIn())
        $email = $_SESSION['current']->sEmail;

  // header
  apidb_header("Submit Application");

  // show add to queue form
  echo '<form name="newApp" action="appsubmit.php" method="post" enctype="multipart/form-data">',"\n";
  echo "<p>This page is for submitting new applications to be added to this\n";
  echo "database. The application will be reviewed by the AppDB Administrator\n";
  echo "and you will be notified via email if this application will be added to\n";
  echo "the database.</p>\n";
  echo "<p>Please don't forget to mention which Wine version you used, how well it worked\n";
  echo "and if any workaround were needed. Having app descriptions just sponsoring the app\n";
  echo "(Yes, some vendor want to use the appdb for this) or saying \"I haven't tried this app with Wine\" ";
  echo "won't help Wine development or Wine users.</p>\n";
  echo "<p>After your application has been added you'll be able to submit screenshots for it.</p>";

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
    $sDescription = "<p>Enter description here</p>";
    echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
    echo '<td><p style="width:700px"><textarea cols="80" rows="20" id="editor" name="queueDesc">'.$sDescription.'</textarea></p></td></tr>',"\n";

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
    $sDescription  = "<p>This is a template; enter version-specific description here</p>";
    $sDescription .= "<p>
                               <span class=\"title\">Wine compatibility</span><br />
                               <span class=\"subtitle\">What works:</span><br />
                               - settings<br />
                               - help<br />
                               <br /><span class=\"subtitle\">What doesn't work:</span><br />
                               - erasing<br />
                               <br /><span class=\"subtitle\">What was not tested:</span><br />
                               - burning<br />
                               </p>";
    $oRow->description .= "<p><span class=\"title\">Tested versions</span><br /><table class=\"historyTable\" width=\"90%\" border=\"1\">
                            <thead class=\"historyHeader\"><tr>
                            <td>App. version</td><td>Wine version</td><td>Installs?</td><td>Runs?</td><td>Rating</td>
                            </tr></thead>
                            <tbody><tr>
                            <td class=\"gold\">3.23</td><td class=\"gold\">20050111</td><td class=\"gold\">yes</td><td class=\"gold\">yes</td><td class=\"gold\">Gold</td>
                            </tr><tr>
                            <td class=\"silver\">3.23</td><td class=\"silver\">20041201</td><td class=\"silver\">yes</td><td class=\"silver\">yes</td><td class=\"silver\">Silver</td>
                            </tr><tr>
                            <td class=\"bronze\">3.21</td><td class=\"bronze\">20040615</td><td class=\"bronze\">yes</td><td class=\"bronze\">yes</td><td class=\"bronze\">Bronze</td>
                            </tr></tbody></table></p><p> <br /> </p>";

    echo '<tr valign=top><td class=color0><b>App Desc</b></td>',"\n";
    echo '<td><p style="width:700px"><textarea cols="80" rows="20" id="editor" name="queueDesc">'.$sDescription.'</textarea></p></td></tr>',"\n";

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
  if(!$_SESSION['current']->isLoggedIn())
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
