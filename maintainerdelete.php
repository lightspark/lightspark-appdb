<?

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");

if(!loggedin())
{
    errorpage("You need to be logged in to apply to be a maintainer.");
    exit;
}

opendb();

$appId = strip_tags($_POST['appId']);
$versionId = strip_tags($_POST['versionId']);
$confirmed = strip_tags($_POST['confirmed']);
$superMaintainer = strip_tags($_POST['superMaintainer']);

// header

if($superMaintainer)
    apidb_header("Confirm supermaintainer resignation of ".appIdToName($appId));
else
    apidb_header("Confirm maintainer resignation of ".appIdToName($appId).versionIdToName($versionId));

if($confirmed)
{
    global $current;

    echo html_frame_start("Removing",400,"",0);

    if($superMaintainer)
        $query = "DELETE FROM appMaintainers WHERE userId = '$current->userid' AND appId = '$appId' AND superMaintainer = '$superMaintainer';";
    else
        $query = "DELETE FROM appMaintainers WHERE userId = '$current->userid' AND appId = '$appId' AND versionId = '$versionId' AND superMaintainer = '$superMaintainer';";

    echo "$query";

    $result = mysql_query($query);
    if($result)
    {
        if($superMaintainer)
            echo "You were removed as a supermaintainer of ".appIdToName($appId);
        else
            echo "You were removed as a maintainer of ".appIdToName($appId).versionIdToName($versionId);
    } else
    {
        //error
        echo "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
    }
} else
{
    echo '<form name="deleteMaintainer" action="maintainerdelete.php" method="post" enctype="multipart/form-data">',"\n";

    echo html_frame_start("Confirm",400,"",0);
    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
    echo "<input type=hidden name='appId' value=$appId>";
    echo "<input type=hidden name='versionId' value=$versionId>";
    echo "<input type=hidden name='superMaintainer' value=$superMaintainer>";
    echo "<input type=hidden name='confirmed' value=1>";

    if($superMaintainer)
    {
        echo "<tr><td>Are you sure that you want to be removed as a super maintainer of this application?</tr></td>\n";
        echo '<tr><td align=center><input type=submit value=" Confirm resignation as supermaintainer " class=button>', "\n";
    } else
    {
        echo "<tr><td>Are you sure that you want to be removed as a maintainer of this application?</tr></td>\n";
        echo '<tr><td align=center><input type=submit value=" Confirm resignation as maintainer " class=button>', "\n";
    }

    echo "</td></tr></table>";
}

echo html_frame_end();

apidb_footer();

?>
