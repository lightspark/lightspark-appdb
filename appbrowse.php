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
require_once(BASE."include/"."appdb.php");
require_once(BASE."include/"."category.php");

function admin_menu()
{
    global $aClean;

    $m = new htmlmenu("Admin");
    $m->add('Edit this Category', BASE."objectManager.php?iId=${aClean[iCatId]}&sClass=category&sAction=edit");
    $url = BASE."admin/deleteAny.php?sWhat=category&iCatId=".$aClean['iCatId']."&sConfirmed=yes";

    /* We only allow deletion of the category if it is empty */
    $oCat = new category($aClean['iCatId']);
    if(!sizeof($oCat->aApplicationsIds))
        $m->add('Delete this Category', BASE."objectManager.php?iId=${aClean[iCatId]}&sClass=category&sAction=delete");

    $m->done();
}

$iCatId = isset($aClean['iCatId']) ? $aClean['iCatId'] : 0;
// list sub categories
$oCat = new Category( $iCatId );
$sCatFullPath = Category::make_cat_path($oCat->getCategoryPath());
$subs = $oCat->aSubcatsIds;

//display admin box
if($_SESSION['current']->hasPriv("admin") && isset($aClean['iCatId']) && $aClean['iCatId'] != 0 )
    apidb_sidebar_add("admin_menu");

//output header
apidb_header("Browse Applications");

echo "<div class='default_container'>\n";

if($subs)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $sCatFullPath ."</b><br />\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);

    $oTable = new Table();
    $oTable->SetWidth("100%");
    $oTable->SetBorder(0);
    $oTable->SetCellPadding(3);
    $oTable->SetCellSpacing(1);

    $oTableRow = new TableRow();
    $oTableRow->SetClass("color4");
    $oTableRow->AddTextCell("Sub Category");
    $oTableRow->AddTextCell("Description");
    $oTableRow->AddTextCell("No. Apps");
    $oTable->SetHeader($oTableRow);
    
    while(list($i,$iSubcatId) = each($subs))
    {
        $oSubCat= new Category($iSubcatId);

        //set row color
        $sColor = ($i % 2) ? "color0" : "color1"; 

        $oTableRowHighlight = GetStandardRowHighlight($i);

        $sUrl = "appbrowse.php?iCatId=$iSubcatId";

        $oTableRowClick = new TableRowClick($sUrl);
        $oTableRowClick->SetHighlight($oTableRowHighlight);

        //get number of apps in this sub-category
        $iAppcount = $oSubCat->getApplicationCount();

        //format desc
        $sDesc = substr(stripslashes($oSubCat->sDescription),0,70);

        //display row
        $oTableRow = new TableRow();
        $oTableRow->SetClass($sColor);
        $oTableRow->SetRowClick($oTableRowClick);

        $oTableCell = new TableCell($oSubCat->sName);
        $oTableCell->SetCellLink($sUrl);
        $oTableRow->AddCell($oTableCell);
        $oTableRow->AddTextCell("$sDesc &nbsp;");
        $oTableRow->AddTextCell("$iAppcount &nbsp;");

        $oTable->AddRow($oTableRow);
    }

    // output the table
    echo $oTable->GetString();

    echo html_frame_end( count($subs) . ' categories');
}



// list applications in this category
$apps = $oCat->aApplicationsIds;
if($apps)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $sCatFullPath ."</b><br />\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);

    $oTable = new Table();
    $oTable->SetWidth("100%");
    $oTable->SetBorder(0);
    $oTable->SetCellPadding(3);
    $oTable->SetCellSpacing(1);

    $oTableRow = new TableRow();
    $oTableRow->SetClass("color4");
    $oTableRow->AddTextCell("Application name");
    $oTableRow->AddTextCell("Description");
    $oTableRow->AddTextCell("No. Versions");

    $oTable->SetHeader($oTableRow);
	    
    while(list($i, $iAppId) = each($apps))
    {
        $oApp = new Application($iAppId);

        //set row color
        $sColor = ($i % 2) ? "color0" : "color1";

        $oTableRowHighlight = GetStandardRowHighlight($i);

        $sUrl = $oApp->objectMakeUrl();

        $oTableRowClick = new TableRowClick($sUrl);
        $oTableRowClick->SetHighlight($oTableRowHighlight);
        
        //format desc
        $sDesc = util_trim_description($oApp->sDescription);
	
        //display row
        $oTableRow = new TableRow();
        $oTableRow->SetRowClick($oTableRowClick);
        $oTableRow->SetClass($sColor);
        $oTableRow->AddTextCell($oApp->objectMakeLink());
        $oTableRow->AddTextCell("$sDesc &nbsp;");
        $oTableRow->AddTextCell(sizeof($oApp->aVersionsIds));

        $oTable->AddRow($oTableRow);
    }
    
    // output table
    echo $oTable->GetString();

    echo html_frame_end( count($apps) . " applications in this category");
}

// Disabled for now
//if ($aClean['iCatId'] != 0)
//{
//	log_category_visit($cat->id);
//}

echo p();

echo "</div>\n";

apidb_footer();

?>
