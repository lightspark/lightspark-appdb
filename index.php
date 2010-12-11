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

apidb_header("Lightspark Application Database");
?>

<div class='default_container'>    
<h1>Welcome</h1>

<p>This is the Lightspark Application Database (AppDB). Here you can get information on application
compatibility with Lightspark.</p>
<?php
$str_benefits="
    <ul>
        <li>Ability to <a href=\"".BASE."help/?sTopic=voting\" title=\"help on voting\" style=\"cursor: help\">vote</a> on your favorite applications</li>
        <li>Ability to customize the layout and behavior of the AppDB and comments system</li>
        <li>Take credit for your witty posts</li>
        <li>Ability to sign up to be an <a href=\"".BASE."help/?sTopic=maintainer_guidelines\"
            title=\"information about application maintainers\"  style=\"cursor: help\">application maintainer</a></li>
        <li>Submit new applications and versions</li>
        <li>Submit new screenshots</li>
    </ul>
";
if(!$_SESSION['current']->isLoggedIn()) 
{
    echo "
    <p>Most of the features of the Application Database require that you have a user account and
    are logged in. Some of the benefits of membership are:<p>

    $str_benefits

    <p>So, what are you waiting for? [<a href=\"".login_url()."\">Log in</a>]
    or [<a href=\"account.php?sCmd=new\">register</a>] now! Your help in
    stomping out Lightspark issues will be greatly appreciated.</p>";
} else 
{
    echo "
    <p>As an Application Database member you enjoy some exclusive benefits like:<p>

    $str_benefits

    <p>We&#8217;d like to thank you for being a member and being logged in to the system. Your help in
    stomping out Lightspark issues will be greatly appreciated.</p>";

}

?>
<?php

    $iNumApps = version::objectGetEntriesCount('accepted');

    $voteQuery = "SELECT appVotes.versionId, count(userId) as count ".
        "FROM appVotes ".
        "GROUP BY versionId ORDER BY count DESC LIMIT 1";
    $hResult = query_parameters($voteQuery);
    $oRow = query_fetch_object($hResult);

    echo "There are <b>$iNumApps</b> applications currently in the database,";

    // don't mention the top application if there are no votes yet
    if( !empty($oRow) )
    {
        if($oRow->versionId)
        {
            $shVoteAppLink = version::fullNameLink($oRow->versionId);
            echo " with $shVoteAppLink being the\n";
            echo "top <a href='votestats.php'>voted</a> application.\n";
        } else
        {
            echo " please <a href=\"".BASE."help/?sTopic=voting\" title=\"help on voting\"".
                "style=\"cursor: help\">vote</a> for your favourite application.\n";
        }
    }
?>

<br><br>

<div class="topx_style platinum">
  <div class="rating_header">
    <div class="rating_title">
      Top-10 <a href="objectManager.php?sClass=application&sTitle=Browse+Applications&iappVersion-ratingOp0=5&sappVersion-ratingData0=Platinum&sOrderBy=appName&bAscending=true">Platinum</a> List
    </div>
    Applications which install and run flawlessly on an out-of-the-box Lightspark installation
  </div>
  <div>
    <table class="platinum">
      <tr class="rowtitle">
        <th>Application</th><th>Description</th><th>Screenshot</th>
      </tr>
      <?php
      outputTopXRowAppsFromRating('Platinum', 10);
      ?>
    </table>
  </div>
</div>
<br>

<div class="topx_style gold">
  <div class="rating_header">
    <div class="rating_title">
      Top-10 <a href="objectManager.php?sClass=application&sTitle=Browse+Applications&iappVersion-ratingOp0=5&sappVersion-ratingData0=Gold&sOrderBy=appName&bAscending=true">Gold</a> List
    </div>
    Applications that work flawlessly with some special configuration
  </div>
  <div>
    <table class="gold">
      <tr class="rowtitle">
        <th>Application</th><th>Description</th><th>Screenshot</th>
      </tr>
      <?php
      outputTopXRowAppsFromRating('Gold', 10);
      ?>
    </table>
  </div>
</div>
<br>

<div class="topx_style silver">
  <div class="rating_header">
    <div class="rating_title">
      Top-10 <a href="objectManager.php?sClass=application&sTitle=Browse+Applications&iappVersion-ratingOp0=5&sappVersion-ratingData0=Silver&sOrderBy=appName&bAscending=true">Silver</a> List
    </div>
    Applications with minor issues that do not affect typical usage
  </div>
  <div>
    <table class="silver">
      <tr class="rowtitle">
        <th>Application</th><th>Description</th><th>Screenshot</th>
      </tr>
      <?php
      outputTopXRowAppsFromRating('Silver', 10);
      ?>
    </table>
  </div>
</div>

<br><br>

<?php

// promotional buttons
echo "<center>\n";
echo "<table>\n";
echo "<tr>\n";
echo "<td style='padding:10px;'>\n";
echo '<a href="http://getfirefox.com/"
	title="Get Firefox - Web browsing redefined."><img
	src="http://www.mozilla.org/products/firefox/buttons/getfirefox_large2.png"
	width="178" height="60" border="0" alt="Get Firefox"></a>'."\n";
echo "</td>\n";
echo "<td style='padding:10px;'>\n";
echo '<a href="http://xinha.python-hosting.com/" title="Xinha textarea replacement">
      <img src="images/xinha-red-95.png" width="95" height="100" alt="Xinha"></a>'."\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</center>\n";

apidb_footer();
?>
