<?

include("path.php");
include(BASE."include/"."incl.php");

apidb_header("Vote Stats - Top 25 Applications");


$voteQuery = "SELECT appVotes.appId, appName, count(userId) as count ".
    "FROM appVotes, appFamily ".
    "WHERE appVotes.appId = appFamily.appId ".
    "GROUP BY appId ORDER BY count DESC LIMIT 25";


$result = mysql_query($voteQuery);

if($result)
{
    echo html_frame_start("", "90%", '', 0);
    echo html_table_begin("width='100%' align=center");
    echo "<tr class=color4><td><font color=white>Application Name</font></td>\n";
    echo "<td><font color=white>Votes</font></td></tr>\n";
    
    $c = 1;
    while($row = mysql_fetch_object($result))
    {
        if ($c % 2 == 1) { $bgcolor = "color0"; } else { $bgcolor = "color1"; }
        $link = "<a href='../appview.php?appId=$row->appId'>$row->appName</a>";
	echo "<tr class='".$bgcolor."'><td width='90%'>$c. $link </td> <td> $row->count </td></tr>\n";
	$c++;
    }
    echo html_table_end();
    echo html_frame_end();
    
    echo "<center><a href='help/?topic=voting'>What does this screen mean?</a></center>\n";

}
else
{
    echo "Error: " . mysql_error();
}

apidb_footer();

?>
