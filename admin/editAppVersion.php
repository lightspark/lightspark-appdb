<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

if(!is_numeric($_REQUEST['appId']) OR !is_numeric($_REQUEST['versionId']))
{
    errorpage("Wrong ID");
    exit;
}

/* Check for admin privs */
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($_REQUEST['versionId']) && !$_SESSION['current']->isSuperMaintainer($_REQUEST['appId']))
{
    errorpage("Insufficient Privileges!");
    exit;
}

/* process the changes the user entered into the web form */
if(isset($_REQUEST['submit']))
{
    process_app_version_changes(true);
    redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
} else /* or display the webform for making changes */
{
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<!-- load HTMLArea -->
<script type="text/javascript" src="../htmlarea/htmlarea_loader.js"></script>
<?php
    $oVersion = new Version($_REQUEST['versionId']);

    apidb_header("Edit Application Version");

    echo "<form method=post action='editAppVersion.php'>\n";
    echo html_frame_start("Data for Application ID: ".$oVersion->iAppId." Version ID: ".$oVersion->iVersionId, "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");
    echo '<input type="hidden" name="appId" value='.$oVersion->iAppId.' />';
    echo '<input type="hidden" name="versionId" value='.$oVersion->iVersionId.' />';
    echo '<tr><td class=color1>Name</td><td class=color0>'.lookup_app_name($oVersion->iAppId).'</td></tr>',"\n";
    echo '<tr><td class=color4>Version</td><td class=color0><input size=80% type="text" name="versionName" type="text" value="'.$oVersion->sName.'" /></td></tr>',"\n";
    echo '<tr><td class="color4">Version specific description</td><td class="color0">', "\n";
    // FIXME: put templates in config file or somewhere else.
    if(trim(strip_tags($oVersion->sDescription))=="")
    {
        $oVersion->sDescription  = "<p>This is a template; enter version-specific description here</p>";
        $oVersion->sDescription .= "<p>
                               <span class=\"title\">Wine compatibility</span><br />
                               <span class=\"subtitle\">What works:</span><br />
                               - settings<br />
                               - help<br />
                               <br /><span class=\"subtitle\">What doesn't work:</span><br />
                               - erasing<br />
                               <br /><span class=\"subtitle\">What was not tested:</span><br />
                               - burning<br />
                               </p>";
        $oVersion->sDescription .= "<p><span class=\"title\">Tested versions</span><br /><table class=\"historyTable\" width=\"90%\" border=\"1\">
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
    }
    echo '<p style="width:700px">', "\n";
    echo '<textarea cols="80" rows="30" id="editor" name="description">'.$oVersion->sDescription.'</textarea></td></tr>',"\n";
    echo '</p>';
    echo '<tr><td class="color4">Rating</td><td class="color0">',"\n";
    make_maintainer_rating_list("maintainer_rating", $oVersion->sTestedRating);
    echo '</td></tr>',"\n";
    echo '<tr><td class=color1>Release</td><td class=color0>',"\n";
    make_bugzilla_version_list("maintainer_release", $oVersion->sTestedRelease);
    echo '</td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name="submit" value="Update Database" /></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();
    echo "</form>";

    // url edit form
    echo '<form enctype="multipart/form-data" action="editAppVersion.php" method="post">',"\n";
    echo '<input type=hidden name="appId" value='.$oVersion->iAppId.'>';
    echo '<input type=hidden name="versionId" value='.$oVersion->iVersionId.'>';
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
    $i = 0;
    $result = query_appdb("SELECT * FROM appData WHERE versionId = ".$oVersion->iVersionId." AND type = 'url'");
    if($result && mysql_num_rows($result) > 0)
    {
        echo '<tr><td class=color1><b>Delete</b></td><td class=color1>',"\n";
        echo '<b>Description</b></td><td class=color1><b>URL</b></td></tr>',"\n";
        while($ob = mysql_fetch_object($result))
        {
            $temp0 = "adelete[".$i."]";
            $temp1 = "adescription[".$i."]";
            $temp2 = "aURL[".$i."]";
            $temp3 = "aId[".$i."]";
            $temp4 = "aOldDesc[".$i."]";
            $temp5 = "aOldURL[".$i."]";
            echo '<tr><td class=color3><input type="checkbox" name="'.$temp0.'"></td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp1.'" value ="'.stripslashes($ob->description).'"</td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp2.'" value="'.$ob->url.'"></td></tr>',"\n";
            echo '<input type="hidden" name="'.$temp3.'" value="'.$ob->id.'" />';
            echo '<input type="hidden" name="'.$temp4.'" value="'.stripslashes($ob->description).'" />';
            echo '<input type="hidden" name="'.$temp5.'" value="'.$ob->url.'" />',"\n";
            $i++;
        }
    } else
    {
        echo '<tr><td class="color1"></td><td class="color1"><b>Description</b></td>',"\n";
        echo '<td class=color1><b>URL</b></td></tr>',"\n";
    }
    echo "</td></tr>\n";
    echo "<input type=hidden name='rows' value='$i'>";
    echo '<tr><td class=color1>New</td><td class=color1><input size="45" type="text" name="url_desc"></td>',"\n";
    echo '<td class=color1><input size=45% name="url" type="text"></td></tr>',"\n";
     
    echo '<tr><td colspan=3 align=center class="color3"><input type="submit" name="submit" value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();
    echo "</form>";

    /* only admins can move versions */
    if($_SESSION['current']->hasPriv("admin"))
    {
        // move version form
        echo '<form enctype="multipart/form-data" action="moveAppVersion.php" method="post">',"\n";
        echo '<input type=hidden name="appId" value='.$oVersion->iAppId.'>';
        echo '<input type=hidden name="versionId" value='.$oVersion->iVersionId.'>';
        echo html_frame_start("Move version to another application","90%","",0);
        echo '<center><input type="submit" name="view" value="Move this version"></center>',"\n";
        echo html_frame_end();
    }

    echo html_back_link(1,BASE."appview.php?versionId=".$oVersion->iVersionId);
    apidb_footer();
}
?>
