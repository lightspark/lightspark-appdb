<?php
/********************/
/* Users Management */
/********************/

include("path.php");
include(BASE."include/"."incl.php");

apidb_header("Admin Users Management");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}

// we want to delete a user
if($_REQUEST['action'] == "delete" && is_numeric($_REQUEST['userId']))
{
    $oUser = new User($_REQUEST['userId']);
    $sEmail = $oUser->sEmail;
    if($sEmail)
    {
        $oUser->delete();
    }
}

// search form
echo html_frame_start("Users Management","400","",0)
?>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" METHOD="POST">
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="color1">Pattern</td>
            <td><input type="text" name="sSearch" value="<?php echo$_REQUEST['sSearch'];?>"/><br /><small>(leave blank to match all)</small></td>
        </tr>
        <tr>
            <td class="color1">Show first</td>
            <td>
                <select name="iLimit">
                    <option value="100"<?php if($_REQUEST['iLimit']=="100")echo" SELECTED";?>>100 results</option>
                    <option value="200"<?php if($_REQUEST['iLimit']=="200")echo" SELECTED";?>>200 results</option>
                    <option value="500"<?php if($_REQUEST['iLimit']=="500")echo" SELECTED";?>>500 result</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="color1">Order by</td>
            <td>
                <select NAME="sOrderBy">
                    <option value="email"<?php if($_REQUEST['sOrderBy']=="email")echo" SELECTED";?>>e-mail</option>
                    <option value="realname"<?php if($_REQUEST['sOrderBy']=="realname")echo" SELECTED";?>>real name</option>
                    <option value="created"<?php if($_REQUEST['sOrderBy']=="created")echo" SELECTED";?>>creation date</option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan=2 class=color3 align=center><input type="SUBMIT" name="sSubmit" value="List Users" class="button"></td>
        </tr>
    </table>
  </form>
<?php
echo html_frame_end();

// if the search form was submitted
if($_REQUEST['sSubmit'])
{
    echo html_frame_start("Query Results","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
    echo "<tr class=color4>\n";
    echo "    <td>Real name</td>\n";
    echo "    <td>E-mail</td>\n";
    echo "    <td>Creation date</td>\n";
    echo "    <td>Last connected</td>\n";
    echo "    <td>Roles</td>\n";
    echo "    <td>&nbsp;</td>\n";
    echo "</tr>\n\n";
    if(is_numeric($_REQUEST['iLimit']) && in_array($_REQUEST['sOrderBy'],array("email","realname","created")))
    {
        $sSearch = addslashes($_REQUEST['sSearch']);
        $sQuery = "SELECT * FROM user_list 
                   WHERE realname LIKE '%".$sSearch."%' OR email LIKE '%".$sSearch."%'
                   ORDER BY ".$_REQUEST['sOrderBy']."
                   LIMIT ".$_REQUEST['iLimit'];
        $hResult = query_appdb($sQuery);
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
            echo "    <td>[<a onclick=\"if(!confirm('".$sAreYouSure."'))return false;\" \"href=\"".$_SERVER['PHP_SELF']."?action=delete&userId=".$oRow->userid."&sSearch=".$sSearch."&iLimit=".$_REQUEST['iLimit']."&sOrderBy=".$_REQUEST['sOrderBy']."&sSubmit=true\">delete</a>]&nbsp;[<a href=\"../preferences.php?userId=".$oRow->userid."&sSearch=".$sSearch."&iLimit=".$_REQUEST['iLimit']."&sOrderBy=".$_REQUEST['sOrderBy']."\">edit</a>]</td>\n";
            echo "</tr>\n\n";
        }
    }
    echo "</table>";
    echo html_frame_end();
}
apidb_footer();
?>
