<?php
/**
 * Application database index page.
 *
 * TODO:
 *  - rename outputTopXRowAppsFromRating according to our coding standards
 *  - rename variables that don't follow our coding standards
 */

// application environment
require("path.php");
require(BASE."include/incl.php");

apidb_header("Wine Application Database");
?>
    <img src="images/appdb_montage.jpg" width=391 height=266 align=right alt="Wine AppDB">
    
<h1>Welcome</h1>

<p>This is the Wine Application Database (AppDB). From here you get info on application
compatibility with Wine.</p>
<?php
$str_benefits="
    <ul>
        <li>Ability to <a href=\"".BASE."help/?sTopic=voting\" title=\"help on voting\" style=\"cursor: help\">vote</a> on your favourite applications</li>
        <li>Ability to customize the layout and behaviour of the AppDB and comments system</li>
        <li>Take credit for your witty posts</li>
        <li>Ability to sign up to be an <a href=\"".BASE."help/?sTopic=maintainer_guidelines\" title=\"informations about application maintainers\"  style=\"cursor: help\">application maintainer</a>.</li>
        <li>Submit new applications and versions.</li>
        <li>Submit new screenshots.</li>
    </ul>
";
if(!$_SESSION['current']->isLoggedIn()) 
{
    echo "
    <p>Most of the features of the Application Database require that you have a user account and
    are logged in. Some of the benefits of membership are:<p>

    $str_benefits

    <p>So what are you waiting for, [<a href=\"account.php?sCmd=login\">login now</a>]. Your help in
    stomping out Wine issues will be greatly appreciated.</p>";
} else 
{
    echo "
    <p>As an Application Database member you enjoy some exclusive benefits like:<p>

    $str_benefits

    <p>We&#8217;d like to thank you for being a member and being logged in to the system. Your help in
    stomping out Wine issues will be greatly appreciated.</p>";

}
?>
<p>
If you have screenshots or links to contribute, please browse the database and use the AppDB interface to send us your contributions (any member can send screenshots; if you want to send how-to's or other information, you can either enroll to be a maintainer or post this information as a comment for the application of interest).
</p>
<?php

    $numApps = getNumberOfVersions();

    $voteQuery = "SELECT appVotes.versionId, count(userId) as count ".
        "FROM appVotes ".
        "GROUP BY versionId ORDER BY count DESC LIMIT 1";
    $hResult = query_parameters($voteQuery);
    $oRow = mysql_fetch_object($hResult);

    // don't mention the top application if there are no votes yet
    if($oRow->versionId)
    {
       $shVoteAppLink = version::fullNameLink($oRow->versionId);
       echo "There are <b>$numApps</b> applications currently in the database with\n";
       echo "$shVoteAppLink being the\n";
       echo "top <a href='votestats.php'>voted</a> application.\n";
    } else
    {
       echo "There are <b>$numApps</b> applications currently in the database, please\n";
       echo "<a href=\"".BASE."help/?sTopic=voting\" title=\"help on voting\" style=\"cursor: help\">vote</a> for your favourite application.\n";
    }
?>

<br /><br />

<h2>Top Voted Applications</h2>

<p>This is a list of applications that are known to be working well and for which many users have voted.</p>

<h3>The Top-10 <a href="browse_by_rating.php?sRating=Platinum">Platinum</a> List</h3> 
<p>Only applications which install and run flawlessly on an out-of-the-box Wine installation make it to the Platinum list.</p>
<table class="platinum">
    <tr class="rowtitle">
    <th>Application</th><th>Description</th><th>Screenshot</th>
    </tr>
<?php
  outputTopXRowAppsFromRating('Platinum', 10);
?>
</table>
<br />

<h3>The Top-10 <a href="browse_by_rating.php?sRating=Gold">Gold</a> List</h3> 
<p>Applications that work flawlessly with some DLL overrides or other settings, crack etc. make it to the Gold list.</p>
<table class="gold">
    <tr class="rowtitle">
    <th>Application</th><th>Description</th><th>Screenshot</th>
    </tr>
<?php
  outputTopXRowAppsFromRating('Gold', 10);
?>
</table>
<br />

<h3>The Top-10 <a href="browse_by_rating.php?sRating=Silver">Silver List</a></h3> 
<p>The Silver list contains apps which we hope we can easily fix so they make it to Gold status.</p>
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
application site, with tips and how-to's on getting listed apps to run.
</p>
<?php
apidb_footer();
?>
