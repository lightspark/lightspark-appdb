<?php
/************************/
/* Add Application Note */
/************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

//FIXME: get rid of appId references everywhere, as version is enough.
$sQuery = "SELECT appId FROM appVersion WHERE versionId = '".$_REQUEST['versionId']."'";
$hResult = query_appdb($sQuery);
$oRow = mysql_fetch_object($hResult);
$appId = $oRow->appId; 

//check for admin privs
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($_REQUEST['versionId']) && !$_SESSION['current']->isSuperMaintainer($_REQUEST['appId']))
{
    errorpage("Insufficient Privileges!");
    exit;
}

//set link for version
if(is_numeric($_REQUEST['versionId']) and !empty($_REQUEST['versionId']))
{
    $versionLink = "versionId={$_REQUEST['versionId']}";
}
else 
    exit;


if($_REQUEST['sub'] == "Submit")
{
    $oNote = new Note();
    $oNote->create($_REQUEST['noteTitle'], $_REQUEST['noteDesc'], $_REQUEST['versionId']);
    redirect(apidb_fullurl("appview.php?".$versionLink));
    exit;
}
else if($_REQUEST['sub'] == 'Preview' OR empty($_REQUEST['submit']))
{
    HtmlAreaLoaderScript(array("editor"));
    
    apidb_header("Add Application Note");

    echo "<form method=post action='addAppNote.php'>\n";
    echo html_frame_start("Add Application Note", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo "<input type=\"hidden\" name=\"versionId\" value=\"{$_REQUEST['versionId']}\">";
    echo add_br($_REQUEST['noteDesc']);

    if ($_REQUEST['noteTitle'] == "HOWTO" || $_REQUEST['noteTitle'] == "WARNING")
    {
        echo "<input type=hidden name='noteTitle' value='{$_REQUEST['noteTitle']}'>";
        echo "<tr><td class=color1>Type</td><td class=color0>{$_REQUEST['noteTitle']}</td></tr>\n";
    }
    else
    {
        echo "<tr><td class=color1>Title</td><td class=color0><input size='80%' type='text' name='noteTitle' type='text' value='{$_REQUEST['noteTitle']}'></td></tr>\n";
    }
    echo '<tr><td class="color4">Description</td><td class="color0">', "\n";
    if(trim(strip_tags($_REQUEST['noteDesc']))=="") $_REQUEST['noteDesc']="<p>Enter note here</p>";
    echo '<p style="width:700px">', "\n";
    echo '<textarea cols="80" rows="20" id="editor" name="noteDesc">'.stripslashes($_REQUEST['noteDesc']).'</textarea>',"\n";
    echo '</p>';
    echo '</td></tr><tr><td colspan="2" align="center" class="color3">',"\n";
    echo '<input type="submit" name="sub" value="Preview">&nbsp',"\n";
    echo '<input type="submit" name="sub" value="Submit"></td></tr>',"\n";
    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link(1,BASE."appview.php?".$versionLink);
    apidb_footer();
}
?>
