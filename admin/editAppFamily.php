<?php
/**********************************/
/* Edit application family        */
/**********************************/

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");
require(BASE."include/"."application.php");
require(BASE."include/"."category.php");

if(!is_numeric($_REQUEST['appId']))
{
    errorpage("Wrong ID");
    exit;
}

if(!(havepriv("admin") || $_SESSION['current']->is_super_maintainer($_REQUEST['appId'])))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['submit']))
{
    $statusMessage = '';
    
    // commit changes of form to database
    if($_REQUEST['submit'] == "Update Database")
    {
        // Get the old values from the database 
        $sQuery = "SELECT * FROM appFamily WHERE appId = ".$_REQUEST['appId'];
        $hResult = query_appdb($sQuery);
        $ob = mysql_fetch_object($hResult);
        $sOld_appName     = $ob->AppName;
        $sOld_description = $ob->description;
        $iOld_vendorId    = $ob->vendorId;
        $iOld_catId       = $ob->catId;
        $sOld_keywords    = $ob->keywords;
        $sOld_webPage     = $ob->webPage;

        $sWhatChanged = "";
        $bAppChanged = false;
        if ($sOld_appName <> $_REQUEST['appName'])
        {
            $sWhatChanged .= "    App name: Old Value: ".stripslashes($sOld_appName)."\n";
            $sWhatChanged .= "              New Value: ".stripslashes($_REQUEST['appName'])."\n";
            $bAppChanged = true;
        }

        if ($iOld_vendorId <> $_REQUEST['vendorId'])
        {
            $sWhatChanged .= "      Vendor: Old Value: ".lookupVendorName($iOld_vendorId)."\n";
            $sWhatChanged .= "              New Value: ".lookupVendorName($_REQUEST['vendorId'])."\n";
            $bAppChanged = true;
        }

        if ($old_description <> $description)
        {
            $sWhatChanged .= " Description: Old Value:\n";
            $sWhatChanged .= "-----------------------:\n";
            $sWhatChanged .= stripslashes($sOld_description)."\n";
            $sWhatChanged .= "-----------------------:\n";
            $sWhatChanged .= " Description: New Value:\n";
            $sWhatChanged .= "-----------------------:\n";
            $sWhatChanged .= stripslashes($_REQUEST['description'])."\n";
            $sWhatChanged .= "-----------------------:\n";
            $bAppChanged = true;
        }

        if ($iOld_catId <> $_REQUEST['catId'])
        {
            $sWhatChanged .= "    Category: Old Value: ".lookupCategoryName($iOld_catId)."\n";
            $sWhatChanged .= "              New Value: ".lookupCategoryName($_REQUEST['catId'])."\n";
            $bAppChanged = true;
        }

        if ($sOld_keywords <> $_REQUEST['keywords'])
        {
            $sWhatChanged .= "    keywords: Old Value: ".stripslashes($sOld_keywords)."\n";
            $sWhatChanged .= "              New Value: ".stripslashes($_REQUEST['keywords'])."\n";
            $bAppChanged = true;
        }

        if ($sOld_webPage <> $_REQUEST['webPage'])
        {
            $sWhatChanged .= "    Web Page: Old Value: ".stripslashes($sOld_webPage)."\n";
            $sWhatChanged .= "              New Value: ".stripslashes($_REQUEST['webPage'])."\n";
            $bAppChanged = true;
        }

        //did anything change?
        if ($bAppChanged)
        {
            $sUpdate = compile_update_string(array( 'appName' => $_REQUEST['appName'],
                                                    'description' => $_REQUEST['description'],
                                                    'webPage' => $_REQUEST['webPage'],
                                                    'vendorId' => $_REQUEST['vendorId'],
                                                    'keywords' => $_REQUEST['keywords'],
                                                    'catId' =>  $_REQUEST['catId'] ));
                                               
            if (query_appdb("UPDATE `appFamily` SET $sUpdate WHERE `appId` = {$_REQUEST['appId']}"))
            {

                addmsg("Database Updated", "green");

            }
        }
    }
    else if($_REQUEST['submit'] == "Update URL")
    {

        $sWhatChanged = "";
        $bAppChanged = false;

        if (!empty($_REQUEST['url_desc']) && !empty($_REQUEST['url']) )
        {
            // process added URL
            if(debugging()) { echo "<p align=center><b>{$_REQUEST['url']}:</b> {$_REQUEST['url_desc']} </p>"; }
        
            $aInsert = compile_insert_string( array( 'appId' => $_REQUEST['appId'],
                                             'type' => 'url',
                                             'description' => $_REQUEST['url_desc'],
                                             'url' => $_REQUEST['url']));
            
            $sQuery = "INSERT INTO appData ({$aInsert['FIELDS']}) VALUES ({$aInsert['VALUES']})";
	    
            if(debugging()) { echo "<p align=center><b>query:</b> $sQuery </p>"; }
	    
            if (query_appdb($sQuery))
            {
                addmsg("The URL was successfully added into the database", "green");
                $sWhatChanged .= "  Added Url:     Description: ".stripslashes($_REQUEST['url_desc'])."\n";
                $sWhatChanged .= "                         Url: ".stripslashes($_REQUEST['url'])."\n";
                $bAppChanged = true;
            }
        }
        
        // Process changed URL's
        
        for($i = 0; $i < $_REQUEST['rows']; $i++)
        {
            if(debugging()) { echo "<p align=center><b>{$_REQUEST['adescription'][$i]}:</b> {$_REQUEST['aURL'][$i]}: {$_REQUEST['adelete'][$i]} : {$_REQUEST['aId'][$i]} : .{$_REQUEST['aOldDesc'][$i]}. : {$_REQUEST['aOldURL'][$i]}</p>"; }
            
            if ($_REQUEST['adelete'][$i] == "on")
            {
	            $hResult = query_appdb("DELETE FROM appData WHERE id = '{$_REQUEST['aId'][$i]}'");

                if($hResult)
                {
                    addmsg("<p><b>Successfully deleted URL ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');
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
    }
    if ($bAppChanged)
    {
        $email = getNotifyEmailAddressList($_REQUEST['appId']);
        if($email)
        {
            $fullAppName = "Application: ".lookupAppName($_REQUEST['appId']);
            $ms .= APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."\n";
            $ms .= "\n";
            $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." changed ".$fullAppName."\n";
            $ms .= "\n";
            $ms .= $sWhatChanged."\n";
            $ms .= "\n";
            $ms .= STANDARD_NOTIFY_FOOTER;

            mail( "", "[AppDB] ".$fullAppName ,$ms, "Bcc: ".stripslashes( $email));
        } else
        {
            $email = "no one";
        }
        addmsg("mesage sent to: ".$email, green);
    }

    redirect(apidb_fullurl("appview.php?appId={$_REQUEST['appId']}"));
    exit;
}
else
// Show the form for editing the Application Family 
{
    $family = new TableVE("edit");

    $result = query_appdb("SELECT * from appFamily WHERE appId = '{$_REQUEST['appId']}'");
    
    if(!mysql_num_rows($result))
    {
        errorpage('Application does not exist');
        exit;
    }
    
    $ob = mysql_fetch_object($result);
    
    if(debugging()) { echo "<p align=center><b>appName:</b> $ob->appName </p>"; }

     apidb_header("Edit Application Family");

    echo "<form method=post action='editAppFamily.php'>\n";
    echo html_frame_start("Data for Application ID $ob->appId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type=hidden name="appId" value='.$ob->appId.'>';
    echo '<tr><td class=color1>Name</td><td class=color0><input size=80% type="text" name="appName" type="text" value="'.$ob->appName.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Vendor</td><td class=color0>';
    $family->make_option_list("vendorId", $ob->vendorId, "vendor", "vendorId", "vendorName");
    echo '</td></tr>',"\n";
    echo '<tr><td class=color1>Keywords</td><td class=color0><input size=80% type="text" name="keywords" value="'.$ob->keywords.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=50 rows=10 name="description">'.stripslashes($ob->description).'</textarea></td></tr>',"\n";
    echo '<tr><td class=color1>Web Page</td><td class=color0><input size=80% type="text" name="webPage" value="'.$ob->webPage.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Category</td><td class=color0>';
    $family->make_option_list("catId", $ob->catId, "appCategory", "catId", "catName");
    echo '</td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name=submit value="Update Database"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();


    // url edit form
    echo '<form enctype="multipart/form-data" action="editAppFamily.php" method="post">',"\n";
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
    $i = 0;
    $result = mysql_query("SELECT * FROM appData WHERE appId = $ob->appId AND type = 'url' AND versionId = 0");
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
            echo '<td class=color3><input size=45% type="text" name="'.$temp1.'" value ="'.stripslashes($ob->description).'"</td>',"\n";
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
    echo "<input type=hidden name='rows' value='$i'>";

    echo '<tr><td class=color1>New</td><td class=color1><input size=45% type="text" name="url_desc"></td>',"\n";
    echo '<td class=color1><input size=45% name="url" type="text"></td></tr>',"\n";
     
    echo '<tr><td colspan=3 align=center class=color3><input type="submit" name=submit value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();

    echo html_back_link(1,BASE."appview.php?appId=$ob->appId");

}

apidb_footer();

?>
