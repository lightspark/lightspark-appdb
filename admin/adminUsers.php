<?php
/********************/
/* Users Management */
/********************/

include("path.php");
include(BASE."include/incl.php");

$aClean = array(); //filtered user input

$aClean['sAction'] = makeSafe($_REQUEST['sAction']);
$aClean['iUserId'] = makeSafe($_REQUEST['iUserId']);
$aClean['sSearch'] = makeSafe($_REQUEST['sSearch']);
$aClean['iLimit'] = makeSafe($_REQUEST['iLimit']);
$aClean['sOrderBy'] = makeSafe($_REQUEST['sOrderBy']);
$aClean['sSubmit'] = makeSafe($_REQUEST['sSubmit']);

apidb_header("Admin Users Management");

if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient privileges.");


// we want to delete a user
if($aClean['sAction'] == "delete" && is_numeric($aClean['iUserId']))
{
    $oUser = new User($aClean['iUserId']);
    $oUser->delete();
}

// search form
echo html_frame_start("Users Management","400","",0)
?>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="color1">Pattern</td>
            <td><input type="text" name="sSearch" value="<?php echo $aClean['sSearch'];?>"/><br /><small>(leave blank to match all)</small></td>
        </tr>
        <tr>
            <td class="color1">Show first</td>
            <td>
                <select name="iLimit">
                    <option value="100"<?php if($aClean['iLimit']=="100")echo" SELECTED";?>>100 results</option>
                    <option value="200"<?php if($aClean['iLimit']=="200")echo" SELECTED";?>>200 results</option>
                    <option value="500"<?php if($aClean['iLimit']=="500")echo" SELECTED";?>>500 result</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="color1">Order by</td>
            <td>
                <select name="sOrderBy">
                    <option value="email"<?php if($aClean['sOrderBy']=="email")echo" SELECTED";?>>e-mail</option>
                    <option value="realname"<?php if($aClean['sOrderBy']=="realname")echo" SELECTED";?>>real name</option>
                    <option value="created"<?php if($aClean['sOrderBy']=="created")echo" SELECTED";?>>creation date</option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan=2 class=color3 align=center><input type="submit" name="sSubmit" value="List Users" class="button"></td>
        </tr>
    </table>
  </form>
<?php
echo html_frame_end();

// if the search form was submitted
if($aClean['sSubmit'])
{
    echo html_frame_start("Query Results","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
    echo "<tr class=color4>\n";
    echo "    <td>Real name</td>\n";
    echo "    <td>E-mail</td>\n";
    echo "    <td>Creation date</td>\n";
    echo "    <td>Last connected</td>\n";
    echo "    <td>Roles</td>\n";
    echo "    <td align=\"center\">Action</td>\n";
    echo "</tr>\n\n";
    if(is_numeric($aClean['iLimit']) && in_array($aClean['sOrderBy'],array("email","realname","created")))
    {
        $sSearch = $aClean['sSearch'];
        $sQuery = "SELECT * FROM user_list 
                   WHERE realname LIKE '%?%' OR email LIKE '%?%'
                   ORDER BY ?
                   LIMIT ?";
        $hResult = query_parameters($sQuery, $sSearch, $sSearch, $aClean['sOrderBy'],
                                $aClean['iLimit']);
        $i=0;
        while($hResult && $oRow = mysql_fetch_object($hResult))
        {
            $oUser = new User($oRow->userid);
            $sAreYouSure = "Are you sure that you want to delete user ".addslashes($oUser->sRealname)." ?";
            echo "<tr class=\"color".(($i++)%2)."\">\n";
            echo "    <td>".$oUser->sRealname."</td>\n";
            echo "    <td>".$oUser->sEmail."</td>\n";
            echo "    <td>".$oUser->sDateCreated."</td>\n";
            echo "    <td>".$oUser->sStamp."</td>\n";
            echo "    <td>";
            if($oUser->hasPriv("admin")) echo "A";
            if($oUser->isMaintainer()) echo "M";
            echo "    </td>\n";
            echo "    <td align=\"center\">[<a href=\"../preferences.php?iUserId=".$oRow->userid."&sSearch=".$sSearch."&iLimit=".$aClean['iLimit']."&sOrderBy=".$aClean['sOrderBy']."\">edit</a>]&nbsp;[<a onclick=\"if(!confirm('".$sAreYouSure."'))return false;\" \"href=\"".$_SERVER['PHP_SELF']."?sAction=delete&iUserId=".$oRow->userid."&sSearch=".$sSearch."&iLimit=".$aClean['iLimit']."&sOrderBy=".$aClean['sOrderBy']."&sSubmit=true\">delete</a>]</td>\n";
            echo "</tr>\n\n";
        }
    }
    echo "</table>";
    echo html_frame_end();
}
apidb_footer();
?>
