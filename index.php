<?php
/***********************************/
/* Application Database Index Page */
/***********************************/

include("path.php");
require(BASE."include/"."incl.php");

apidb_header("Wine Application Database");

?>

    <img src="images/appdb_montage.jpg" width=391 height=266 align=right alt="Wine AppDB">
    
<h1>Welcome</h1>

<p>This is the Wine Application Database. From here you get info on application
compatibility with Wine. For developers, you can get information on the APIs used in an
application.</p>
<?php
$str_benefits="
    <ul>
        <li>Ability to Vote on Favorite Applications</li>
        <li>Access to the Application Rating System. Rate the apps that \"Don't Suck\"</li>
        <li>Ability to customize the View of the Apps DB and Comment System</li>
        <li>Take Credit for your witty posts</li>
        <li>Ability to sign up to be an application maintainer.</li>
        <li>Submit new applications and versions.</li>
    </ul>
";
if(!loggedin()) 
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
If you have screenshots or links to contribute, please browse the database and use the AppDB interface to send us your contributions.
</p>
<?php
# FIXME: This is too "wine-specific" and must be replaced.
# (for example user could submit how-to's, etc using a form and an admin or app maintainer 
# could accept or reject the how-to in the same way we are now handling screenshots.)
?>
<p>
If you have anything else to contribute (howtos, etc.), enroll to be an application maintainer or contact us at:
<a href="mailto:appdb@winehq.org">appdb@winehq.org</a><br />
Note that this address is not for end-user support, for end user support please contact the
wine-users mailing list or the wine newsgroup, for more information visit
<a href="http://www.winehq.com/site/forums">this page</a>
</p>
<?php

    $numApps = getNumberOfVersions();

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
?>

<br /><br />

<h2>Wine 0.9 Supported Applications List</h2>

<p>This is a working version of the application lists which we hope to
support 'officially' for Wine 0.9. Please send comments, and suggestions,
about the list to <a href="mailto:clozano@andago.com">Carlos Lozano</a>;
direct formatting related flames to <a href="mailto:dpaun@rogers.com">Dimitrie O. Paun</a>.</p>

<h3>The Gold List</h3> 
<p>Applications which install and run virtually flawless on a 
        out-of-the-box Wine installation make it to the Gold list: </p>
<table class=gold>
    <tr class=rowtitle>
    <th>Application</th><th>Version</th><th>Description</th><th>Tucows top</th><th>Notes</th><th>Screenshot</th>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=134'>Acrobat Reader</a></td>";
?>
        <td>5.0.5</td>
        <td>This is the solution to your PDF troubles.</td>
        <td>6</td>
        <td>Dlls installed by the program: advpack.dll, msvcrt.dll, shfolder.dll, w95inf32.dll, msvcp60.dll, oleaut32.dll, w95inf16.dll</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=145'>WS-FTP LE</a></td>";
?>
        <td>5.08</td>
        <td>A great application that allows remote file edits, chmods on UNIX boxes and file moves.</td>
        <td>9</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=77'>mIRC</a></td>";
?>
        <td>6.03</td>
        <td>This is a popular IRC client.</td>
        <td>25</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=356'>Putty</a></td>";
?>
        <td>0.52</td>
        <td>Simple Telnet/SSH client & console.</td>
        <td>29</td>
        <td>No install needed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=1110'>FTP Commander</a></td>";
?>
        <td>5.58</td>
        <td>A remote file management and command-line FTP client.</td>
        <td>83</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=284'>Pegasus Mail</a></td>";
?>
        <td>4.02</td>
        <td>E-mail client of choice for many beginner and advanced users.</td>
        <td>96</td>
        <td>You may need to mark WINSOCK.DLL as "On demand only" in Tools-&gt;Options-&gt;Advanced</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=1756'>12Ghosts Zip</a></td>";
?>
        <td>XP/31</td>
        <td>This is a fast compression utility.</td>
        <td>N/A</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=1755'>WinMerge</a></td>";
?>
        <td>2.1.4</td>
        <td>A visual text file differencing and merging tool for Win32 platforms.</td>
        <td>10@SF</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
        echo "<td><a href='".BASE."appview.php?appId=868'>FileZilla</a></td>";
?>
        <td>2.2.2</td>
        <td>FileZilla is a fast FTP client for Windows with a lot of features.</td>
        <td>11@SF</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>
</table>
<br />
<h3>The Silver List</h3> 
<p>The Silver list contains apps which we hope we can easily fix so they make it
   to Gold status:</p>
<table class=silver>
    <tr class=rowtitle>
    <th>Application</th><th>Version</th><th>Description</th><th>Tucows top</th><th>Notes</th><th>Screenshot</th>
    </tr>

    <tr class=white>
<?php
echo "<td><a href='".BASE."appview.php?appId=2'>WinZip</a></td>";
?>
        <td>8.1</td>
        <td>The most popular compression utility for Windows just got better.</td>
        <td>4</td>
        <td> Install: Yes. (Dlls installed none)<br />
      	Run: Yes,(but it needs riched20.dll to shown the text in zipped files what
        includes a message, zipped files without messages works fine)</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
echo "<td><a href='".BASE."appview.php?appId=55'>ICQ</a></td>";
?>
        <td>2002a</td>
        <td>The new and improved ICQ is here with all the great features you've come to expect -- plus a whole new set!</td>
        <td>5</td>
        <td> Install: Yes, but it need "touch /c/windows/system/setupapi.dll"
     (Dlls installed atl.dll,msvcrt.dll)<br />
    Run: No, required comctl32 (imagelist proble) and riched32 native.
     (it wasn't able to add users to contact list even with this native
      dlls, i don't know if it was a different problem, or wine related)</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
echo "<td><a href='".BASE."appview.php?appId=5'>Winamp</a></td>";
?>
        <td>3.0</td>
        <td>This program has so many possibilities and offers such a wide
range of interfaces, you'll need no other player.</td>
        <td>10</td>
        <td>Install: Yes. (Dlls installed none)<br />
    Run: No. (Need native msvcrt.dll then works)</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
echo "<td><a href='".BASE."appview.php?appId=391'>WinRAR</a></td>";
?>
        <td>3.00</td>
        <td>This is a version of the popular RAR compression format, offering significantly improved compression ratios.</td>
        <td>11</td>
        <td>Install: Yes. (It will install winrar in the directory what you are
      when run the installer, it is a buggy because you must stop the
      installation with ctrl+c when it will ask by "overwrite files", but
      it works).<br />
    Run: Yes. (minor glitches in bugzilla)
    </td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
echo "<td><a href='".BASE."appview.php?appId=288'>WinMX</a></td>";
?>
        <td>3.22</td>
        <td>Take file sharing to a new level.</td>
        <td>50</td>
        <td>Install: Yes (Dlls installed none)<br />
    Run: Yes. (listbox is not working in it (comctl32))
        </td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?php
   echo "<td><a href='".BASE."appview.php?appId=1757'>SnagIt</a></td>";
?>
        <td>6.1.1</td>
        <td>Use this to capture and manage images, text, and video.</td>
        <td>n/a</td>
        <td>    Install: Yes. (Dlls installed advpack.dll, setupapi.dll, w95inf16.dll,
      cfgmgr32.dll,shfolder.dll,w95inf32.dll)<br />
    Run: Partial. (it has too options, some options like capture avi, or
      capture web are not working)</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>
</table>

<br /><br />

<h2>Other Wine Application Compatibility Sites</h2>
<p>
<a href="http://frankscorner.org"><b>Frank's Corner</b></a>:  Frank has a fantastic Wine
application site. The site contains tips and howtos on getting listed apps to run.
</p>
<p>
<a href="http://sidenet.ddo.jp/winetips/config.html"><b>Sidenet wine configuration utility</b></a>:  Installs Internet Explorer 6 and Windows Media Player 7 automatically (works also with MSN Messenger and RealPlayer).
</p>
<a href="http://www.von-thadden.de/Joachim/WineTools/"><b>WineTools</b></a>: WineTools is an menu driven installer for installing Windows programs under wine (DCOM98, IE6, Windows Core Fonts, Windows System Software, Office & Office Viewer, Adobe Photoshop 7, Illustrator 9, Acrobat Reader 5.1, ...).
</p>
<p>&nbsp;</p>

<?php
apidb_footer();
?>
