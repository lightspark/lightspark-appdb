<?php
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/category.php");

if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit();

$oCat = new Category($aClean['iCatId']);
if($aClean['sSubmit'])
{
    $oCat->update($aClean['sName'],$aClean['sDescription'],$aClean['iParentId']);
    util_redirect_and_exit(apidb_fullurl("appbrowse.php?iCatId=".$oCat->iCatId));
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
