<?


include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");
require(BASE."include/"."application.php");


//check for admin privs
if(!loggedin() || (!havepriv("admin") && !isMaintainer($appId, $versionId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

if($HTTP_POST_VARS)
{
    if($submit1 == "Update Database")

    {
        $statusMessage = '';
        // Get the old values from the database 
        $query = "SELECT * FROM appVersion WHERE appId = $appId and versionId = $versionId";
        $result = mysql_query($query);
        $ob = mysql_fetch_object($result);
        $old_versionName = $ob->versionName;
        $old_keywords    = $ob->keywords;
        $old_description = $ob->description;
        $old_webPage     = $ob->webPage;

        $versionName   = addslashes($versionName);
        $description   = addslashes($description);
        $webPage       = addslashes($webPage);
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
        //did anything change?
        if ($VersionChanged)
        {
            $query = "UPDATE appVersion SET versionName = '".$versionName."', ".
                "keywords = '".$keywords."', ".
                "description = '".$description."', ".
                "webPage = '".$webPage."'".
                " WHERE appId = $appId and versionId = $versionId";
            if (mysql_query($query))
            {  
	        //success
                $email = getNotifyEmailAddressList($appId, $versionId);
                if($email)
                {
                    $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
                    $ms .= APPDB_ROOT."appview.php?appId=$appId&versionId=$versionId"."\n";
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
                redirect(apidb_fullurl("appview.php?appId=$appId&versionId=$versionId"));
	    }
	    else
	    {
	       //error
               $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
               addmsg($statusMessage, "red");
               redirect(apidb_fullurl("admin/editAppVersion.php?appId=$appId&versionId=$versionId"));
	    }
	    
        } else
        {
            addmsg("Nothing changed", "red");
            redirect(apidb_fullurl("admin/editAppVersion.php?appId=$appId&versionId=$versionId"));
        }
    }
    exit;   	
} else
{
    $query = "SELECT versionName,  keywords, ".
        "description, webPage from appVersion WHERE ".
        "appId = '$appId' and versionId = '$versionId'";
    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $result = mysql_query($query);
    list($versionName, $keywords, $description, $webPage) = mysql_fetch_row($result);

    apidb_header("Edit Application Version");

    echo "<form method=post action='editAppVersion.php'>\n";
    echo html_frame_start("Data for Application ID: $appId Version ID: $versionId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type=hidden name="appId" value='.$appId.'>';
    echo '<input type=hidden name="appId" value='.$appId.'>';
    echo '<input type=hidden name="versionId" value='.$versionId.'>';
    echo '<tr><td class=color1>Name</td><td class=color0>'.lookupAppName($appId).'</td></tr>',"\n";
    echo '<tr><td class=color4>Version</td><td class=color0><input size=80% type="text" name="versionName" type="text" value="'.$versionName.'"></td></tr>',"\n";
    echo '<tr><td class=color1>Keywords</td><td class=color0><input size=80% type="text" name="keywords" value="'.$keywords.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=$80 rows=$30 name="description">'.stripslashes($description).'</textarea></td></tr>',"\n";
    echo '<tr><td class=color1>Web Page</td><td class=color0><input size=80% type="text" name="webPage" value="'.$webPage.'"></td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name=submit1 value="Update Database"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();

    echo html_back_link(1);
    apidb_footer();
}

?>
