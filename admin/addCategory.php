<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/category.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage();
    exit;
}
$oCat = new Category($_REQUEST['catId']);
if($_REQUEST['submit'])
{
    $oCat->update($_REQUEST['name'],$_REQUEST['description'],$_REQUEST['parentId']);
    redirect(apidb_fullurl("appbrowse.php?catId=".$oCat->iCatId));
}
else
{
apidb_header("Add Category");
$sQuery = "SELECT catId, catName FROM appCategory WHERE catId!='".$_REQUEST['catId']."'";
$hResult = query_appdb($sQuery);
while($oRow = mysql_fetch_object($hResult))
{
    $aCatsIds[]=$oRow->catId;
    $aCatsNames[]=$oRow->catName;
}
echo "<form method=\"post\" action=\"addCategory.php\">
      <input type=\"hidden\" name=\"catId\" value=\"".$oCat->iCatId."\" /> 
      <table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">
        <tr>
          <td width=\"15%\" class=\"box-label\"><b>Category name</b></td>
          <td class=\"box-body\">
            <input type=\"text\" size=\"50\" name=\"name\" value=\"".$oCat->sName."\" /> 
          </td>
        </tr>
        <tr>
          <td width=\"15%\" class=\"box-label\"><b>Description</b></td>
          <td class=\"box-body\">
            <input type=\"text\" size=\"50\" name=\"description\" value=\"".$oCat->sDescription."\" /> 
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
            <input type=\"submit\" name=\"submit\" value=\"Submit\" />
          </td>
        </tr>
      </table>
      </form>";
}
apidb_footer();
?>
