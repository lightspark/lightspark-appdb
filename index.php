<?

/*
 * Application Database Index Page
 *
 */

include("path.php");
require(BASE."include/"."incl.php");

apidb_header("Wine Application Database");

?>

    <img src="images/appdb_montage.jpg" width=391 height=266 border=0 align=right alt="Wine AppDB">
    
	<p><big><b>Welcome</b></big></p>
	
	<p>This is the Wine Application Database. From here you get info on application
	compatibility with Wine. For developers, you can get information on the APIs used in an
	application.</p>
	
	<p>Most of the features of the Application database require that you have a user account and
	are logged in. Some of the benefits of membership are:<p>
	
	<ul>
		<li>Ability to Vote on Favorite Applications</li>
		<li>Access to the Application Rating System. Rate the apps that "Don't Suck"</li>
		<li>Ability to customize the View of the Apps DB and Comment System</li>
		<li>Take Credit for your witty posts.</li>
	</ul>

	<p>So what are you waiting for, [<a href="account.php?cmd=login">login now</a>]. Your help in
	stomping out Wine issues will be greatly appreciated.</p>

	<p>
	If you have anything to contribute (screenshots, howtos), contact us at:
	<a href="mailto:appdb@winehq.org">appdb@winehq.org</a><br>
	Note that this address is not for end-user support, for end user support please contact the
	wine-users mailing list or the wine newsgroup, for more information visit
	<a href="http://www.winehq.com/site/forums">this page</a>
	</p>
<?

    $countQuery = "SELECT count(versionId) as hits FROM appVersion WHERE versionName != 'NONAME'";
    $result = mysql_query($countQuery);
    $ob = mysql_fetch_object($result);
    
    $numApps = $ob->hits;

    $voteQuery = "SELECT appVotes.appId, appName, count(userId) as count ".
        "FROM appVotes, appFamily ".
        "WHERE appVotes.appId = appFamily.appId ".
        "GROUP BY appId ORDER BY count DESC LIMIT 1";
    $result = mysql_query($voteQuery);
    $ob = mysql_fetch_object($result);

    $voteAppId = $ob->appId;
    $voteAppName = $ob->appName;

    
    echo "There are <b>$numApps</b> applications currently in the database with\n";
    echo "<a href='appview.php?appId=$voteAppId'>$voteAppName</a> being the\n";
    echo "top <a href='votestats.php'>voted</a> application.\n";

    echo "<p>&nbsp;</p>\n";

apidb_footer();




?>
