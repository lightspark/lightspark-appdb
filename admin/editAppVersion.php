<?php


include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");
require(BASE."include/"."application.php");


//check for admin privs
if(!loggedin() || (!havepriv("admin") && !$_SESSION['current']->is_maintainer($_REQUEST['appId'], $_REQUEST['versionId'])) )
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
        $result = mysql_query($query);
        $ob = mysql_fetch_object($result);
        $old_versionName = $ob->versionName;
        $old_keywords    = $ob->keywords;
        $old_description = $ob->description;
        $old_webPage     = $ob->webPage;
        $old_rating      = $ob->maintainer_rating;
        $old_release     = $ob->maintainer_release;

        $versionName        = addslashes($_REQUEST['versionName']);
        $keywords           = $_REQUEST['keywords'];
        $description        = addslashes($_REQUEST['description']);
        $webPage            = addslashes($_REQUEST['webPage']);
        $maintainer_rating  = $_REQUEST['maintainer_rating'];
        $maintainer_release = $_REQUEST['maintainer_release'];

        
        $VersionChanged = false;
        if ($old_versionName <> $versionName)
        {
            $WhatChanged .= "Version name: Old Value: ".stripslashes($old_versionName)."\n";
            $WhatChanged .= "              New Value: ".stripslashes($versionName)."\n";
            $VersionChanged = true;
        } 
        if ($old_keywords <> $keywords)
        {
             $WhatChanged .= "   Key Words: Old Value: ".stripslashes($old_keywords)."\n";
             $WhatChanged .= "              New Value: ".stripslashes($keywords)."\n";
             $VersionChanged = true;
        }
        if ($old_webPage <> $webPage)
        {
             $WhatChanged .= "    Web Page: Old Value: ".stripslashes($old_webPage)."\n";
             $WhatChanged .= "              New Value: ".stripslashes($webPage)."\n";
             $VersionChanged = true;
        } 
        if ($old_description <> $description)
        {
             $WhatChanged .= " Description: Old Value:\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= stripslashes($old_description)."\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= " Description: Vew Value:\n";
             $WhatChanged .= "-----------------------:\n";
             $WhatChanged .= stripslashes($description)."\n";
             $WhatChanged .= "-----------------------:\n";
             $VersionChanged = true;
        } 
        if ($old_rating <> $maintainer_rating)
        {
            $WhatChanged .= "     Release: Old Value: ".stripslashes($old_rating)."\n";
            $WhatChanged .= "              New Value: ".stripslashes($maintainer_rating)."\n";
            $VersionChanged = true;
        } 

        if ($old_release <> $maintainer_release)
        {
            $WhatChanged .= "     Release: Old Value: ".stripslashes($old_release)."\n";
            $WhatChanged .= "              New Value: ".stripslashes($maintainer_release)."\n";
            $VersionChanged = true;
        } 

        //did anything change?
        if ($VersionChanged)
        {
            $query = "UPDATE appVersion SET versionName = '".$versionName."', ".
                "keywords = '".$_REQUEST['keywords']."', ".
                "description = '".$description."', ".
                "webPage = '".$webPage."',".
                "maintainer_rating = '".$maintainer_rating."',".
                "maintainer_release = '".$maintainer_release."'".
                " WHERE appId = ".$_REQUEST['appId']." and versionId = ".$_REQUEST['versionId'];
            if (mysql_query($query))
            {  
          //success
                $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
                if($email)
                {
                    $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                    $ms .= APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                    $ms .= "\n";
                    $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." changed ".$fullAppName."\n";
                    $ms .= "\n";
                    $ms .= $WhatChanged."\n";
                    $ms .= "\n";
                    $ms .= STANDARD_NOTIFY_FOOTER;

                    mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);

                } else
                {
                $email = "no one";
                }
                addmsg("mesage sent to: ".$email, green);

                addmsg("The Version was successfully updated in the database", "green");
                redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
      }
      else
      {
         //error
               $statusMessage = "<p><b>Database Error!<br />".mysql_error()."</b></p>\n";
               addmsg($statusMessage, "red");
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

    $result = mysql_query($query);
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
