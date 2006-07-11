<?php
/**
 * Application browser.
 *
 * Optional parameters:
 *  - iCatId, shows applications that belong to the category identified by iCatId
 */

// application environment
require("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/filter.php");
require_once(BASE."include/"."appdb.php");
require_once(BASE."include/"."category.php");

function admin_menu()
{
    global $aClean;

    $m = new htmlmenu("Admin");
    $m->add("Edit this Category", BASE."admin/addCategory.php?iCatId=".$aClean['iCatId']);
    $url = BASE."admin/deleteAny.php?sWhat=category&iCatId=".$aClean['iCatId']."&sConfirmed=yes";
    $m->add("Delete this Category", "javascript:deleteURL(\"Are you sure?\", \"".$url."\")");

    $m->done();
}

// list sub categories
$oCat = new Category($aClean['iCatId']?$aClean['iCatId']:"0");
$sCatFullPath = Category::make_cat_path($oCat->getCategoryPath());
$subs = $oCat->aSubcatsIds;

//display admin box
if($_SESSION['current']->hasPriv("admin") && $aClean['iCatId'] != 0)
    apidb_sidebar_add("admin_menu");

//output header
apidb_header("Browse Applications");

if($subs)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $sCatFullPath ."</b><br />\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";
    
    echo "<tr class=color4>\n";
    echo "    <td>Sub Category</td>\n";
    echo "    <td>Description</td>\n";
    echo "    <td>No. Apps</td>\n";
    echo "</tr>\n\n";
    
    while(list($i,$iSubcatId) = each($subs))
    {
        $oSubCat= new Category($iSubcatId);

        //set row color
        $bgcolor = ($i % 2) ? "color0" : "color1"; 
	
        //get number of apps in this sub-category
        $appcount = $oSubCat->getApplicationCount();

        //format desc
        $desc = substr(stripslashes($oSubCat->sDescription),0,70);

        //display row
        echo "<tr class=$bgcolor>\n";
        echo "    <td><a href='appbrowse.php?iCatId=$iSubcatId'>".$oSubCat->sName."</a></td>\n";
        echo "    <td>$desc &nbsp;</td>\n";
        echo "    <td>$appcount &nbsp;</td>\n";
        echo "</tr>\n\n";
    }
    
    echo "</table>\n\n";
    echo html_frame_end("$c categories");
}



// list applications in this category
$apps = $oCat->aApplicationsIds;
if($apps)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $sCatFullPath ."</b><br />\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";
    
    echo "<tr class=color4>\n";
    echo "    <td>Application Name</td>\n";
    echo "    <td>Description</td>\n";
    echo "    <td>No. Versions</td>\n";
    echo "</tr>\n\n";
	    
    while(list($i, $iAppId) = each($apps))
    {
        $oApp = new Application($iAppId);

        //set row color
        $bgcolor = ($i % 2) ? "color0" : "color1";
        
        //format desc
        $desc = util_trim_description($oApp->sDescription);
	
        //display row
        echo "<tr class=$bgcolor>\n";
        echo "    <td><a href='appview.php?iAppId=$iAppId'>".$oApp->sName."</a></td>\n";
        echo "    <td>$desc &nbsp;</td>\n";
        echo "    <td>".sizeof($oApp->aVersionsIds)."</td>\n";
        echo "</tr>\n\n";
    }
    
    echo "</table>\n\n";
    echo html_frame_end("$c applications in this category");
}

// Disabled for now
//if ($aClean['iCatId'] != 0)
//{
//	log_category_visit($cat->id);
//}

echo p();

apidb_footer();

?>
