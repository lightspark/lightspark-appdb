<?

// Check the input of a submitted form. And output with a list
// of errors. (<ul></ul>)
function checkAppMaintainerInput( $fields )
{
    $errors = "";

    if ( empty( $fields['maintainReason']) )
    {
        $errors .= "<li>Please enter say why you would like to be an app maintainer.</li>\n";
    }

    if ( empty($errors) )
    {
        return "";
    }
    else
    {
        return $errors;
    }
}

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

/* if the user is already a maintainer don't add them again */
if(isMaintainer($appId, $versionId))
{
    echo "You are already a maintainer of this app!";
    exit;
}

if($_REQUEST['maintainReason'])
{
    // check the input for empty/invalid fields
    $errors = checkAppMaintainerInput($_REQUEST);
    if(!empty($errors))
    {
        errorpage("We found the following errors:","<ul>$errors</ul><br>Please go back and correct them.");
        exit;
    }

    // header
    apidb_header("Submit Maintainer Request");    

    // add to queue
    $query = "INSERT INTO appMaintainerQueue VALUES (null, '".
            addslashes($_REQUEST['appId'])."', '".
            addslashes($_REQUEST['versionId'])."', '".
            addslashes($current->userid)."', '".
            addslashes($_REQUEST['maintainReason'])."',".
            "NOW()".");";

    mysql_query($query);

    if ($error = mysql_error())
    {
        echo "<p><font color=red><b>Error:</b></font></p>\n";
        echo "<p>$error</p>\n";
    }
    else
    {
        echo "<p>Your maintainer request has been submitted for Review. You should hear back\n";
        echo "soon about the status of your submission</p>\n";
    }
} else
{
	// header
    apidb_header("Request to become an application maintainer of ".appIdToName($appId).versionIdToName($versionId));

    // show add to queue form
	
    echo '<form name="newApp" action="maintainersubmit.php" method="post" enctype="multipart/form-data">',"\n";

    echo "<p>This page is for submitting a request to become an application maintainer.\n";
    echo "An application maintainer is someone who runs the application \n";
    echo "regularly and who is willing to be active in reporting regressions with newer \n";
    echo "versions of wine and to help other users run this application under wine.";
    echo "<p>Being an application maintainer comes with responsibilities. ";
    echo "<br><br><b>You are expected to:</b>";
    echo "<li>You are expected to keep the application comments clean, all stale data should be removed</li>";
    echo "<li>Be an active user of that application and version</li>";
    echo "<li>Keep up-to-date with all wine releases, if there are regressions these should be reported to wine-devel</li>";

    echo "<p><b>You will:</b>";
    echo "<li>Receive an email anytime a comment is posted or deleted for the application or the application information is modified</li>";
    echo "<p>We ask that all maintainers explain why they want to be an app maintainer,\n";
    echo "why they think they will do a good job and a little about their experience\n";
    echo "with wine.  This is both to give you time to\n";
    echo "think about whether you really want to be an app maintainer and also for the\n";
    echo "appdb admins to identify people that are best suited for the job.  Your request\n";
    echo "may be denied if there are already a handful of maintainers for this app or if you\n";
    echo "don't have the experience with wine that is necessary to help other users out.\n";
    echo "<br><br>";

    echo html_frame_start("New Maintainer Form",400,"",0);

    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
    echo "<tr valign=top><td class=color0>";
    echo '<b>Application</br></td><td>'.appIdToName($appId).' '.versionIdToName($versionId);
    echo '</td></tr>',"\n";
    echo "<input type=hidden name='appId' value=$appId>";
    echo "<input type=hidden name='versionId' value=$versionId>";
    echo '<tr valign=top><td class=color0><b>Why you want to and should be an app maintainer</b></td><td><textarea name="maintainReason" rows=15 cols=70></textarea></td></tr>',"\n";
    echo '<tr valign=top><td class=color3 align=center colspan=2> <input type=submit value=" Submit Maintainer Request " class=button> </td></tr>',"\n";
    echo '</table>',"\n";

    echo html_frame_end();

	echo "</form>";
}

apidb_footer();

?>
