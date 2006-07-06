<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/category.php");

$aClean = array(); //array of filtered user input

$aClean['iCatId'] = makeSafe($_REQUEST['iCatId']);
$aClean['sName'] = makeSafe($_REQUEST['sName']);
$aClean['sDescription'] = makeSafe($_REQUEST['sDescription']);
$aClean['iParentId'] = makeSafe($_REQUEST['iParentId']);
$aClean['sSubmit'] = makeSafe($_REQUEST['sSubmit']);

if(!$_SESSION['current']->hasPriv("admin"))
{
    util_show_error_page();
    exit;
}
$oCat = new Category($aClean['iCatId']);
if($aClean['sSubmit'])
{
    $oCat->update($aClean['sName'],$aClean['sDescription'],$aClean['iParentId']);
    redirect(apidb_fullurl("appbrowse.php?iCatId=".$oCat->iCatId));
}
else
{
    apidb_header("Add Category");
    $sQuery = "SELECT catId, catName FROM appCategory WHERE catId!='?'";
    $hResult = query_parameters($sQuery, $aClean['iCatId']);
    while($oRow = mysql_fetch_object($hResult))
    {
        $aCatsIds[]=$oRow->catId;
        $aCatsNames[]=$oRow->catName;
    }
    echo "<form method=\"post\" action=\"addCategory.php\">
      <input type=\"hidden\" name=\"iCatId\" value=\"".$oCat->iCatId."\" /> 
      <table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">
        <tr>
          <td width=\"15%\" class=\"box-label\"><b>Category name</b></td>
          <td class=\"box-body\">
            <input type=\"text\" size=\"50\" name=\"sName\" value=\"".$oCat->sName."\" /> 
          </td>
        </tr>
        <tr>
          <td width=\"15%\" class=\"box-label\"><b>Description</b></td>
          <td class=\"box-body\">
            <input type=\"text\" size=\"50\" name=\"sDescription\" value=\"".$oCat->sDescription."\" /> 
          </td>
        </tr>
        <tr>
          <td width=\"15%\" class=\"box-label\"><b>Parent</b></td>
          <td class=\"box-body\">
            ".html_select("parentId",$aCatsIds,$oCat->iParentId,$aCatsNames)." 
          </td>
        </tr>
        <tr>
          <td colspan=\"2\" class=\"box-body\">
            <input type=\"submit\" name=\"sSubmit\" value=\"Submit\" />
          </td>
        </tr>
      </table>
      </form>";
}
apidb_footer();
?>
