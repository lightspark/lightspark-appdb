<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/ableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

if(!is_numeric($_REQUEST['appId']) OR !is_numeric($_REQUEST['versionId']))
{
    errorpage("Wrong ID");
    exit;
}

//check for admin privs
if(!(havepriv("admin") || $_SESSION['current']->is_maintainer($_REQUEST['appId'],$_REQUEST['versionId'])))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['submit1']))
{
    if($_REQUEST['submit1'] == "Update Database")

    {
        $statusMessage = '';
        // Get the old values from the database 
        $query = "SELECT * FROM appVersion WHERE appId = ".$_REQUEST['appId']." and versionId = ".$_REQUEST['versionId'];
        $result = query_appdb($query);
        $ob = mysql_fetch_object($result);
        $old_versionName = $ob->versionName;
        $old_keywords    = $ob->keywords;
        $old_description = $ob->description;
        $old_webPage     = $ob->webPage;
        $old_rating      = $ob->maintainer_rating;
        $old_release     = $ob->maintainer_release;

        $versionName        = $_REQUEST['versionName'];
        $keywords           = $_REQUEST['keywords'];
        $description        = $_REQUEST['description'];
        $webPage            = $_REQUEST['webPage'];
        $maintainer_rating  = $_REQUEST['maintainer_rating'];
        $maintainer_release = $_REQUEST['maintainer_release'];

        
        $VersionChanged = false;
        if ($old_versionName <> $versionName)
        {
            $WhatChanged .= "Version name: Old Value: ".stripslashes($old_versionName)."\n";
            $WhatChanged .= "              New Value: ".$versionName."\n";
            $VersionChanged = true;
        } 
        if ($old_keywords <> $keywords)
        {
             $WhatChanged .= "   Key Words: Old Value: ".stripslashes($old_keywords)."\n";
             $WhatChanged .= "              New Value: ".$keywords."\n";
             $VersionChanged = true;
        }
        if ($old_webPage <> $webPage)
        {
             $WhatChanged .= "    Web Page: Old Value: ".stripslashes($old_webPage)."\n";
             $WhatChanged .= "              New Value: ".$webPage."\n";
             $VersionChanged = true;
        } 
        if ($old_description <> $description)
        {
             $WhatChanged .= " Description: Old Value:\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= stripslashes($old_description)."\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= " Description: New Value:\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= stripslashes($description)."\n";
             $WhatChanged .= "-----------------------:\n";
             $VersionChanged = true;
        } 
        if ($old_rating <> $maintainer_rating)
        {
            $WhatChanged .= "     Release: Old Value: ".stripslashes($old_rating)."\n";
            $WhatChanged .= "              New Value: ".$maintainer_rating."\n";
            $VersionChanged = true;
        } 

        if ($old_release <> $maintainer_release)
        {
            $WhatChanged .= "     Release: Old Value: ".stripslashes($old_release)."\n";
            $WhatChanged .= "              New Value: ".$maintainer_release."\n";
            $VersionChanged = true;
        } 

        //did anything change?
        if ($VersionChanged)
        {
            $sUpdate = compile_update_string( array('versionName' => $versionName,
                                              'description' => $description,
                                              'webPage' => $webPage,
                                              'keywords' => $keywords,
                                              'maintainer_rating' => $maintainer_rating,
                                              'maintainer_release' =>  $maintainer_release));
                                              
            $query = "UPDATE appVersion SET $sUpdate WHERE appId = ".$_REQUEST['appId']." and versionId = ".$_REQUEST['versionId'];
            // success            
            if (query_appdb($query))
            {  
                $sEmail = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
                if($sEmail)
                {
                    $sFullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                    $sMsg .= APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\r\n";
                    $sMsg .= "\r\n";
                    $sMsg .= $_SESSION['current']->realname." changed ".$sFullAppName."\n";
                    $sMsg .= "\r\n";
                    $sMsg .= $WhatChanged."\r\n";
                    $sMsg .= "\r\n";

                    mail_appdb($sEmail, $sFullAppName ,$sMsg);
                }
                addmsg("The Version was successfully updated in the database", "green");
                redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
      }
      else
      {
         //error
         redirect(apidb_fullurl("admin/editAppVersion.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
      }
      
        } else
        {
            addmsg("Nothing changed", "red");
            redirect(apidb_fullurl("admin/editAppVersion.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
        }
    }
    exit;     
} else
{
    $query = "SELECT versionName,  keywords, ".
        "description, webPage, maintainer_rating, maintainer_release from appVersion WHERE ".
        "appId = '".$_REQUEST['appId']."' and versionId = '".$_REQUEST['versionId']."'";
    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $result = query_appdb($query);
    list($versionName, $keywords, $description, $webPage, $maintainer_rating, $maintainer_release) = mysql_fetch_row($result);

    apidb_header("Edit Application Version");

    echo "<form method=post action='editAppVersion.php'>\n";
    echo html_frame_start("Data for Application ID: ".$_REQUEST['appId']." Version ID: ".$_REQUEST['versionId'], "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type=hidden name="appId" value='.$_REQUEST['appId'].' />';
    echo '<input type=hidden name="appId" value='.$_REQUEST['appId'].' />';
    echo '<input type=hidden name="versionId" value='.$_REQUEST['versionId'].' />';
    echo '<tr><td class=color1>Name</td><td class=color0>'.lookupAppName($_REQUEST['appId']).'</td></tr>',"\n";
    echo '<tr><td class=color4>Version</td><td class=color0><input size=80% type="text" name="versionName" type="text" value="'.$versionName.'" /></td></tr>',"\n";
    echo '<tr><td class=color1>Keywords</td><td class=color0><input size=80% type="text" name="keywords" value="'.$keywords.'" /></td></tr>',"\n";
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=$80 rows=$30 name="description">'.stripslashes($description).'</textarea></td></tr>',"\n";
    echo '<tr><td class=color1>Web Page</td><td class=color0><input size=80% type="text" name="webPage" value="'.$webPage.'" /></td></tr>',"\n";
    echo '<tr><td class=color4>Rating</td><td class=color0>',"\n";
    make_maintainer_rating_list("maintainer_rating", $maintainer_rating);
    echo '</td></tr>',"\n";
    echo '<tr><td class=color1>Release</td><td class=color0>',"\n";
    make_bugzilla_version_list("maintainer_release", $maintainer_release);
    echo '</td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name=submit1 value="Update Database" /></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();

    echo html_back_link(1);
    apidb_footer();
}

?>
