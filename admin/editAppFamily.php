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
    $statusMessage = '';
    
    // commit changes of form to database
    if($_REQUEST['submit'] == "Update Database")
    {
        // Get the old values from the database 
        $sQuery = "SELECT * FROM appFamily WHERE appId = ".$_REQUEST['appId'];
        $hResult = query_appdb($sQuery);
        $ob = mysql_fetch_object($hResult);
        $sOld_appName     = $ob->appName;
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

        if ($old_description <> $_REQUEST['description'])
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
            
            // success                                               
            if (query_appdb("UPDATE `appFamily` SET $sUpdate WHERE `appId` = {$_REQUEST['appId']}"))
            {  
                $sEmail = get_notify_email_address_list($_REQUEST['appId']);
                if($sEmail)
                {
                    $sSubject = lookupAppName($_REQUEST['appId'])." has been modified by ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."\n";
                    $sMsg .= "\n";
                    $sMsg .= "The following changes have been made:";
                    $sMsg .= "\n";
                    $sMsg .= $sWhatChanged."\n";
                    $sMsg .= "\n";

                    mail_appdb($sEmail, $sSubject ,$sMsg);
                }
                addmsg("The application was successfully updated in the database", "green");
                redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId']));
            } else
            {
                //error
                redirect(apidb_fullurl("admin/editAppVersion.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            }   
        }
    }
}
else
// Show the form for editing the Application Family 
{
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<!-- load HTMLArea -->
<script type="text/javascript" src="../htmlarea/htmlarea_loader.js"></script>
<?php
    $family = new TableVE("edit");

    $result = query_appdb("SELECT * from appFamily WHERE appId = '{$_REQUEST['appId']}'");
    
    if(!mysql_num_rows($result))
    {
        errorpage('Application does not exist');
        exit;
    }
    
    $ob = mysql_fetch_object($result);
    
    if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>appName:</b> $ob->appName </p>"; }

     apidb_header("Edit Application Family");

    echo "<form method=\"post\" action=\"editAppFamily.php\">\n";
    echo html_frame_start("Data for Application ID $ob->appId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type="hidden" name="appId" value="'.$ob->appId.'">';
    echo '<tr><td class=color1>Name</td><td class=color0><input size=80% type="text" name="appName" type="text" value="'.$ob->appName.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Vendor</td><td class=color0>';
    $family->make_option_list("vendorId", $ob->vendorId, "vendor", "vendorId", "vendorName");
    echo '</td></tr>',"\n";
    echo '<tr><td class=color1>Keywords</td><td class=color0><input size=80% type="text" name="keywords" value="'.$ob->keywords.'"></td></tr>',"\n";
    echo '<tr><td class="color4">Description</td><td class="color0">', "\n";
    if(trim(strip_tags($ob->description))=="") $ob->description="<p>Enter description here</p>";
    echo '<p style="width:700px">', "\n";
    echo '<textarea rows="20" cols="80" id="editor" name="description">'.$ob->description.'</textarea></td></tr>',"\n";
    echo '</p>';
    echo '<tr><td class=color1>Web Page</td><td class=color0><input size=80% type="text" name="webPage" value="'.$ob->webPage.'"></td></tr>',"\n";
    echo '<tr><td class=color4>Category</td><td class=color0>';
    $family->make_option_list("catId", $ob->catId, "appCategory", "catId", "catName");
    echo '</td></tr>',"\n";
    echo '<tr><td colspan=2 align=center class=color3><input type="submit" name=submit value="Update Database"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();
    echo "</form>";

    echo html_back_link(1,BASE."appview.php?appId=$ob->appId");

}

apidb_footer();
?>
