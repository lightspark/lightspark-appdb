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

if(isset($_REQUEST['submit1']))
{
    $oVersion = new Version($_REQUEST['versionId']);
    $oApp = new Application($_REQUEST['appId']);
    if($_REQUEST['submit1'] == "Update Database")
    {
        $oVersion->update($_REQUEST['versionName'], $_REQUEST['description'], $_REQUEST['maintainer_release'], $_REQUEST['maintainer_rating']);
    }
    else if($_REQUEST['submit1'] == "Update URL")
    {

        $sWhatChanged = "";
        $bAppChanged = false;

        if (!empty($_REQUEST['url_desc']) && !empty($_REQUEST['url']) )
        {
            // process added URL
            $aInsert = compile_insert_string( array('versionId' => $_REQUEST['versionId'],
                                             'type' => 'url',
                                             'description' => $_REQUEST['url_desc'],
                                             'url' => $_REQUEST['url']));
            
            $sQuery = "INSERT INTO appData ({$aInsert['FIELDS']}) VALUES ({$aInsert['VALUES']})";
	    
            if (query_appdb($sQuery))
            {
                addmsg("The URL was successfully added into the database", "green");
                $sWhatChanged .= "  Added Url:     Description: ".stripslashes($_REQUEST['url_desc'])."\n";
                $sWhatChanged .= "                         Url: ".stripslashes($_REQUEST['url'])."\n";
                $bAppChanged = true;
            }
        }
        
        // Process changed URLs  
        for($i = 0; $i < $_REQUEST['rows']; $i++)
        {
            if ($_REQUEST['adelete'][$i] == "on")
            {
	            $hResult = query_appdb("DELETE FROM appData WHERE id = '{$_REQUEST['aId'][$i]}'");

                if($hResult)
                {
                    addmsg("Successfully deleted URL ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].").","green");
                    $sWhatChanged .= "Deleted Url:     Description: ".stripslashes($_REQUEST['aOldDesc'][$i])."\n";
                    $sWhatChanged .= "                         url: ".stripslashes($_REQUEST['aOldURL'][$i])."\n";
                    $bAppChanged = true;
                }


            }
            else if( $_REQUEST['aURL'][$i] != $_REQUEST['aOldURL'][$i] || $_REQUEST['adescription'][$i] != $_REQUEST['aOldDesc'][$i])
            {
                if(empty($_REQUEST['aURL'][$i]) || empty($_REQUEST['adescription'][$i]))
                    addmsg("The URL or description was blank. URL not changed in the database", "red");
                else
                {
                    $sUpdate = compile_update_string( array( 'description' => $_REQUEST['adescription'][$i],
                                                     'url' => $_REQUEST['aURL'][$i]));
                    if (query_appdb("UPDATE appData SET $sUpdate WHERE id = '{$_REQUEST['aId'][$i]}'"))
                    {
                         addmsg("<p><b>Successfully updated ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');
                         $sWhatChanged .= "Changed Url: Old Description: ".stripslashes($_REQUEST['aOldDesc'][$i])."\n";
                         $sWhatChanged .= "                     Old Url: ".stripslashes($_REQUEST['aOldURL'][$i])."\n";
                         $sWhatChanged .= "             New Description: ".stripslashes($_REQUEST['adescription'][$i])."\n";
                         $sWhatChanged .= "                     New url: ".stripslashes($_REQUEST['aURL'][$i])."\n";
                         $bAppChanged = true;
                    }
                }
            }
        }
        if ($bAppChanged)
        {
            $sEmail = get_notify_email_address_list($_REQUEST['appId']);
            if($sEmail)
            {
                $sSubject = "Links for ".$oApp->sName." ".$oVersion->sName." have been updated by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."\n";
                $sMsg .= "\n";
                $sMsg .= "The following changes have been made:";
                $sMsg .= "\n";
                $sMsg .= $sWhatChanged."\n";
                $sMsg .= "\n";

                mail_appdb($sEmail, $sSubject ,$sMsg);
            }
        }
    }
    redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
} else
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

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name="submit1" value="Update Database" /></td></tr>',"\n";

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
     
    echo '<tr><td colspan=3 align=center class="color3"><input type="submit" name="submit1" value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();
    echo "</form>";
    echo html_back_link(1,BASE."appview.php?versionId=".$oVersion->iVersionId);
    apidb_footer();
}
?>
