<?php
/***********************************/
/* application database index page */
/***********************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");

apidb_header("Wine Application Database");
?>

    <img src="images/appdb_montage.jpg" width=391 height=266 align=right alt="Wine AppDB">
    
<h1>Welcome</h1>

<p>This is the Wine Application Database (AppDB). From here you get info on application
compatibility with Wine.</p>
<?php
$str_benefits="
    <ul>
        <li>Ability to <a href=\"".BASE."help/?topic=voting\" title=\"help on voting\" style=\"cursor: help\">vote</a> on Favorite Applications</li>
        <li>Ability to customize the layout and behaviour of the AppDB and comments system</li>
        <li>Take credit for your witty posts</li>
        <li>Ability to sign up to be an <a href=\"".BASE."help/?topic=maintainer_guidelines\" title=\"informations about application maintainers\"  style=\"cursor: help\">application maintainer</a>.</li>
        <li>Submit new applications and versions.</li>
        <li>Submit new screenshots.</li>
    </ul>
";
if(!$_SESSION['current']->isLoggedIn()) 
{
    echo "
    <p>Most of the features of the Application database require that you have a user account and
    are logged in. Some of the benefits of membership are:<p>

    $str_benefits

    <p>So what are you waiting for, [<a href=\"account.php?cmd=login\">login now</a>]. Your help in
    stomping out Wine issues will be greatly appreciated.</p>";
} else 
{
    echo "
    <p>As an Application database member you enjoy some exclusive benefits like:<p>

    $str_benefits

    <p>We'd like to thank your for being a member and being logged in the system. Your help in
    stomping out Wine issues will be greatly appreciated.</p>";

}
?>
<p>
If you have screenshots or links to contribute, please browse the database and use the AppDB interface to send us your contributions (any member can send screenshots, if you want to send how-to's or other informations you can either enroll to be a maintainer or post this information as a comment for the application of interest).
</p>
<?php

    $numApps = getNumberOfVersions();

    $voteQuery = "SELECT appVotes.appId, appName, count(userId) as count ".
        "FROM appVotes, appFamily ".
        "WHERE appVotes.appId = appFamily.appId ".
        "GROUP BY appId ORDER BY count DESC LIMIT 1";
    $hResult = query_appdb($voteQuery);
    $oRow = mysql_fetch_object($hResult);

    $voteAppId = $oRow->appId;
    $voteAppName = $oRow->appName;

    /* don't mention the top application if there are no votes yet */
    if($voteAppId != "")
    {
       echo "There are <b>$numApps</b> applications currently in the database with\n";
       echo "<a href='appview.php?appId=$voteAppId'>$voteAppName</a> being the\n";
       echo "top <a href='votestats.php'>voted</a> application.\n";
    } else
    {
       echo "There are <b>$numApps</b> applications currently in the database, please\n";
       echo "<a href=\"".BASE."help/?topic=voting\" title=\"help on voting\" style=\"cursor: help\">vote</a> for your favorite application.\n";
    }
?>

<br /><br />

<h2>Top Voted Applications</h2>

<p>This is a list of applications that are known to be working well and for which many AppDB users voted.</p>

<h3>The top-10 Platinum List</h3> 
<p>Only Applications which install and run flawless on a out-of-the-box Wine installation make it to the Platinum list: </p>
<table class="platinum">
    <tr class="rowtitle">
    <th>Application</th><th>Description</th><th>Screenshot</th>
    </tr>
<?php
  outputTopXRowAppsFromRating('Platinum', 10);
?>
</table>
<br />

<h3>The top-10 Gold List</h3> 
<p>Applications that work flawlessly with some DLL overrides or other settings, crack etc make it to the Gold list: </p>
<table class="gold">
    <tr class="rowtitle">
    <th>Application</th><th>Description</th><th>Screenshot</th>
    </tr>
<?php
  outputTopXRowAppsFromRating('Gold', 10);
?>
</table>
<br />

<h3>The top-10 Silver List</h3> 
<p>The Silver list contains apps which we hope we can easily fix so they make it to Gold status:</p>
<table class=silver>
    <tr class=rowtitle>
      <th>Application</th><th>Description</th><th>Screenshot</th>
    </tr>
<?php
  outputTopXRowAppsFromRating('Silver', 10);
?>
</table>

<br /><br />

<h2>Other Wine Application Compatibility Sites</h2>
<p>
<a href="http://frankscorner.org"><b>Frank's Corner</b></a>:  Frank has a fantastic Wine
application site. The site contains tips and howtos on getting listed apps to run.
</p>
<p>
<a href="http://sidenet.ddo.jp/winetips/config.html"><b>Sidenet Wine configuration utility</b></a>:  Installs Internet Explorer 6 and Windows Media Player 7 automatically (works also with MSN Messenger and RealPlayer).
</p>
<p>
<a href="http://www.von-thadden.de/Joachim/WineTools/"><b>WineTools</b></a>: WineTools is an menu driven installer for installing Windows programs under Wine (DCOM98, IE6, Windows Core Fonts, Windows System Software, Office & Office Viewer, Adobe Photoshop 7, Illustrator 9, Acrobat Reader 5.1, ...).
</p>
<?php
apidb_footer();
?>
