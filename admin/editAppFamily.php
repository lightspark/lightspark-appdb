<?php
/**********************************/
/* Edit application family        */
/**********************************/

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

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
        $sUpdate = compile_update_string(array( 'appName' => $_REQUEST['appName'],
                                               'description' => $_REQUEST['description'],
                                               'webPage' => $_REQUEST['webPage'],
                                               'vendorId' => $_REQUEST['vendorId'],
                                               'keywords' => $_REQUEST['keywords'],
                                               'catId' =>  $_REQUEST['catId'] ));
                                               
        if (query_appdb("UPDATE `appFamily` SET $sUpdate WHERE `appId` = {$_REQUEST['appId']}"))
            addmsg("Database Updated", "green");
    }
    else if($_REQUEST['submit'] == "Update URL")
    {
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
                addmsg("The URL was successfully added into the database", "green");
        }
        
        // Process changed URL's
        
        for($i = 0; $i < $_REQUEST['rows']; $i++)
        {
            if(debugging()) { echo "<p align=center><b>{$_REQUEST['adescription'][$i]}:</b> {$_REQUEST['aURL'][$i]}: {$_REQUEST['adelete'][$i]} : {$_REQUEST['aId'][$i]} : .{$_REQUEST['aOldDesc'][$i]}. : {$_REQUEST['aOldURL'][$i]}</p>"; }
            
            if ($_REQUEST['adelete'][$i] == "on")
            {
	            $hResult = query_appdb("DELETE FROM appData WHERE id = '{$_REQUEST['aId'][$i]}'");

                if($hResult)
                    addmsg("<p><b>Successfully deleted URL ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');

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
                         addmsg("<p><b>Successfully updated ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');
                }
            }            
        }
    }
    
    redirect(apidb_fullurl("appview.php?appId={$_REQUEST['appId']}"));
}
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
