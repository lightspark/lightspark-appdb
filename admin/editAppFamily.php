<?php


include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

if(!(havepriv("admin") || $_SESSION['current']->is_super_maintainer($_REQUEST['appId'])))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if($_POST)
{
    $statusMessage = '';
    // commit changes of form to database
    if($submit1 == "Update Database")
    {
        $statusMessage = '';
        $appName       = addslashes($appName);
        $description   = addslashes($description);
        $webPage       = addslashes($webPage);
        if (!mysql_query("UPDATE appFamily SET appName = '".$appName."', ".
            "vendorId = $vendorId, keywords = '".$keywords."', ".
            "description = '".$description."', ".
            "webPage = '".$webPage."', ".
            "catId = $catId".
            " WHERE appId = $appId"))
        {
            $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
            addmsg($statusMessage, "red");
	}
        else
            addmsg("Database Updated", "green");
    }
    else if($submit1 == "Update URL")
    {
        //process added URL
        if(debugging()) { echo "<p align=center><b>$url:</b> $url_desc </p>"; }
        
        if ($url_desc && $url )
        {
            $query = "INSERT INTO appData VALUES (null, $appId, 0, 'url','$url_desc', '$url')";
	    
            if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }
	    
            if (mysql_query($query))
            {
            //success
                addmsg("The URL was successfully added into the database", "green");
            }
            else
            {
                //error
                $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
                addmsg($statusMessage, "red");
            }
        }
        else if ($url_desc != $url) // not both blank
        {
            addmsg("The URL or description was blank. URL not added into the database", "red");
        }
        
        // Process changed URL's
        for($i = 0; $i < $rows; $i++)
        {
            if(debugging()) { echo "<p align=center><b>$adescription[$i]:</b> $aURL[$i]: $adelete[$i] : $aId[$i] : .$aOldDesc[$i]. : $aOldURL[$i]</p>"; }
            
            if ($adelete[$i] == "on")
            {
                if(debugging()) { echo "<p align=center><b>$adescription[$i]:</b> $aURL[$i]: $adelete[$i] : $aId[$i] : $aOldDesc[$i] : $aOldURL[$i]</p>"; }
	        $result = mysql_query("DELETE FROM appData WHERE id = '$aId[$i]'");

                if(!$result)
                {
                    //error
                    $statusMessage = "<p><b>Database Error!<br>".mysql_error()." deleting URL ".$aOldDesc[$i]." (".$aOldURL[$i].")</b></p>\n";
                    addmsg($statusMessage, "red");
                    $i = $rows+1;
                }
                else
                {
                    $statusMessage = "<p><b>Successfully deleted URL ".$aOldDesc[$i]." (".$aOldURL[$i].")</b></p>\n";
                    addmsg($statusMessage, "green");
                }  
            }
            else if( $aURL[$i] != $aOldURL[$i] || $adescription[$i] != $aOldDesc[$i])
            {
                if(!$aURL[$i] || !$adescription[$i])
                    addmsg("The URL or description was blank. URL not changed in the database", "red");
                else
                {
                    if(debugging()) { echo "<p align=center><b>$adescription[$i]:</b> $aURL[$i]: $adelete[$i] : $aId[$i] : $aOldDesc[$i] : $aOldURL[$i]</p>"; }
                    $adescription[$i] = addslashes($adescription[$i]);
                    $aURL[$i] = addslashes($aURL[$i]);
                    if (!mysql_query("UPDATE appData SET description = '".$adescription[$i]."' , url = '".$aURL[$i]."'".
                        " WHERE Id = $aId[$i]"))
                    {
                        //error
                        $statusMessage = "<p><b>Database Error!<br>".mysql_error()." updateing URL ".$aOldDesc[$i]." (".$aOldURL[$i].")</b></p>\n";
                        addmsg($statusMessage, "red");
                       $i = $rows+1;
                    }
                    else
                    {
                         $statusMessage = "<p><b>Successfully updated ".$aOldDesc[$i]." (".$aOldURL[$i].")</b></p>\n";
                         addmsg($statusMessage, "green");
                    }
                }
            }            
        }
    }
}
//Show the form for editing the Application Family 
{
    $family = new TableVE("edit");

    $result = mysql_query("SELECT appId, appName, vendorId, keywords, ".
			      "description, webPage, catId from appFamily WHERE ".
			      "appId = '$appId'");
    if(!$result)
    {
        errorpage("You must be logged in to edit preferences");
        exit;
    }

    list($appId, $appName, $vendorId, $keywords, $description, $webPage, $catId) = mysql_fetch_row($result);
    if(debugging()) { echo "<p align=center><b>appName:</b> $appName </p>"; }

    // show edit app family form
    $table = "appFamily";
    $query = "SELECT * FROM $table WHERE appId = $appId";

    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }
    apidb_header("Edit Application Family");

    echo "<form method=post action='editAppFamily.php'>\n";
    echo html_frame_start("Data for Application ID $appId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type=hidden name="appId" value='.$appId.'>';
    echo '<tr><td class=color1>Name</td><td class=color0><input size=80% type="text" name="appName" type="text" value="'.$appName.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Vendor</td><td class=color0>';
    $family->make_option_list("vendorId", $vendorId, "vendor", "vendorId", "vendorName");
    echo '</td></tr>',"\n";
    echo '<tr><td class=color1>Keywords</td><td class=color0><input size=80% type="text" name="keywords" value="'.$keywords.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=50 rows=10 name="description">'.stripslashes($description).'</textarea></td></tr>',"\n";
    echo '<tr><td class=color1>Web Page</td><td class=color0><input size=80% type="text" name="webPage" value="'.$webPage.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Category</td><td class=color0>';
    $family->make_option_list("catId", $catId, "appCategory", "catId", "catName");
    echo '</td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name=submit1 value="Update Database"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();


    // url edit form
    echo '<form enctype="multipart/form-data" action="editAppFamily.php" method="post">',"\n";
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
    $i = 0;
    $result = mysql_query("SELECT * FROM appData WHERE appId = $appId AND type = 'url' AND versionId = 0");
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
            echo '<td class=color3><input size=45% type="text" name = "'.$temp1.'" value ="'.stripslashes($ob->description).'"</td>',"\n";
            echo '<td class=color3><input size=45% type="text" name="'.$temp2.'" value="'.$ob->url.'"></td></tr>',"\n";
            echo '<input type=hidden name="'.$temp3.'" value='.$ob->id.'>';
            echo '<input type=hidden name="'.$temp4.'" value="'.stripslashes($ob->description).'">';
            echo '<input type=hidden name="'.$temp5.'" value="'.$ob->url.'">',"\n";
            $i++;
	}
    } else
    {
        echo '<tr><td class=color1></td><td class=color1><b>Description</b></td>',"\n";
        echo '<td class=color1><b>URL</b></td></tr>',"\n";
    }
    echo "</td></tr>\n";
    echo '<input type=hidden name="rows" value='.$i.'>';

    echo '<tr><td class=color1>New</td><td class=color1><input size=45% type="text" name="url_desc"></td>',"\n";
    echo '<td class=color1><input size=45% name="url" type="text"></td></tr>',"\n";
     
    echo '<tr><td colspan=3 align=center class=color3><input type="submit" name=submit1 value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();

    echo html_back_link(1,BASE."appview.php?appId=$appId");

}

apidb_footer();

?>
