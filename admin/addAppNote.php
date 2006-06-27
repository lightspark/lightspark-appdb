<?php
/************************/
/* Add Application Note */
/************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

$aClean = array(); //array of filtered user input

$aClean['versionId'] = makeSafe($_REQUEST['versionId']);
$aClean['appId'] = makeSafe( $_REQUEST['appId']);
$aClean['sub'] = makeSafe($_REQUEST['sub']);
$aClean['submit'] = makeSafe($_REQUEST['submit']);
$aClean['noteTitle'] = makeSafe($_REQUEST['noteTitle']);
$aClean['noteDesc'] = makeSafe($_REQUEST['noteDesc']);

//FIXME: get rid of appId references everywhere, as version is enough.
$sQuery = "SELECT appId FROM appVersion WHERE versionId = '?'";
$hResult = query_parameters($sQuery, $aClean['versionId']);
$oRow = mysql_fetch_object($hResult);
$appId = $oRow->appId; 

//check for admin privs
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($aClean['versionId']) && !$_SESSION['current']->isSuperMaintainer($aClean['appId']))
{
    errorpage("Insufficient Privileges!");
    exit;
}

//set link for version
if(is_numeric($aClean['versionId']) and !empty($aClean['versionId']))
{
    $versionLink = "versionId={$aClean['versionId']}";
}
else 
    exit;


if($aClean['sub'] == "Submit")
{
    $oNote = new Note();
    $oNote->create($aClean['noteTitle'], $aClean['noteDesc'], $aClean['versionId']);
    redirect(apidb_fullurl("appview.php?".$versionLink));
    exit;
}
else if($aClean['sub'] == 'Preview' OR empty($aClean['submit']))
{
    HtmlAreaLoaderScript(array("editor"));
    
    apidb_header("Add Application Note");

    echo "<form method=post action='addAppNote.php'>\n";
    echo html_frame_start("Add Application Note", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo "<input type=\"hidden\" name=\"versionId\" value=\"{$aClean['versionId']}\">";
    echo add_br($aClean['noteDesc']);

    if ($aClean['noteTitle'] == "HOWTO" || $aClean['noteTitle'] == "WARNING")
    {
        echo "<input type=hidden name='noteTitle' value='{$aClean['noteTitle']}'>";
        echo "<tr><td class=color1>Type</td><td class=color0>{$aClean['noteTitle']}</td></tr>\n";
    }
    else
    {
        echo "<tr><td class=color1>Title</td><td class=color0><input size='80%' type='text' name='noteTitle' type='text' value='{$aClean['noteTitle']}'></td></tr>\n";
    }
    echo '<tr><td class="color4">Description</td><td class="color0">', "\n";
    if ( $aClean['noteDesc'] == "" ) $aClean['noteDesc']="<p>Enter note here</p>";
    echo '<p style="width:700px">', "\n";
    echo '<textarea cols="80" rows="20" id="editor" name="noteDesc">'.stripslashes($aClean['noteDesc']).'</textarea>',"\n";
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
