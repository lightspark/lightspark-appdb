<?php
/**********************************/
/* Edit application family        */
/**********************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/category.php");
require(BASE."include/mail.php");

if(!is_numeric($_REQUEST['appId']))
{
    errorpage("Wrong ID");
    exit;
}

if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isSuperMaintainer($_REQUEST['appId'])))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['submit']))
{
    process_app_version_changes(false);
    redirect(apidb_fullurl("appview.php?appId={$_REQUEST['appId']}"));
}
else
// Show the form for editing the Application Family 
{
    $family = new TableVE("edit");


    $oApp = new Application($_REQUEST['appId']);
    
    if(!$oApp)
    {
        errorpage('Application does not exist');
    }
    
    if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>appName:</b> $oApp->sName </p>"; }

     apidb_header("Edit Application Family");

    echo "<form method=\"post\" action=\"editAppFamily.php\">\n";

    $oApp->OutputEditor("");

    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">', "\n";
    echo '<tr><td colspan=2 align=center><input type="submit" name=submit value="Update Database"></td></tr>',"\n";
    echo '</table>', "\n";
    echo "</form>";

    echo "<p>";

   // url edit form
    echo '<form enctype="multipart/form-data" action="editAppFamily.php" method="post">',"\n";
    echo '<input type=hidden name="appId" value='.$oApp->iAppId.'>';
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";

    $i = 0;
    $result = query_appdb("SELECT * FROM appData WHERE appId = $oApp->iAppId AND type = 'url' AND versionId = 0");
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
    echo "</form>";

    echo html_back_link(1,BASE."appview.php?appId=$ob->appId");
}

apidb_footer();
?>
