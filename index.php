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
<h1>Wine 0.9 Supported Applications List</h1>

<p>This is a working version of the application lists which we hope to
support 'officially' for Wine 0.9. Please send comments, and suggestions,
about the list to <a href="mailto:clozano@andago.com">Carlos Lozano</a>;
direct formatting related flames to <a href="mailto:dpaun@rogers.com">Dimitrie O. Paun</a>.</p>

<h2>The Gold List</h2> 
<p>Applications which install and run virtually flawless on a 
        out-of-the-box Wine installation make it to the Gold list: <br>
<table class=gold>
    <tr class=rowtitle>
    <th>Application</th><th>Version</th><th>Description</th><th>Tucows top</th><th>Notes</th><th>Screenshot</th>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=134'>Acrobat Reader</a></td>";
?>
        <td>5.0.5</td>
        <td>This is the solution to your PDF troubles.</td>
        <td>6</td>
        <td>Dlls installed by the program: advpack.dll, msvcrt.dll, shfolder.dll, w95inf32.dll, msvcp60.dll, oleaut32.dll, w95inf16.dll</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=145'>WS-FTP LE</a></td>";
?>
        <td>5.08</td>
        <td>A great application that allows remote file edits, chmods on UNIX boxes and file moves.</td>
        <td>9</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=77'>mIRC</a></td>";
?>
        <td>6.03</td>
        <td>This is a popular IRC client.</td>
        <td>25</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=356'>Putty</a></td>";
?>
        <td>0.52</td>
        <td>Simple Telnet/SSH client & console.</td>
        <td>29</td>
        <td>No install needed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=1110'>FTP Commander</a></td>";
?>
        <td>5.58</td>
        <td>A remote file management and command-line FTP client.</td>
        <td>83</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=284'>Pegasus Mail</a></td>";
?>
        <td>4.02</td>
        <td>E-mail client of choice for many beginner and advanced users.</td>
        <td>96</td>
        <td>You may need to mark WINSOCK.DLL as "On demand only" in Tools-&gt;Options-&gt;Advanced</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=1756'>12Ghosts Zip</a></td>";
?>
        <td>XP/31</td>
        <td>This is a fast compression utility.</td>
        <td>N/A</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=1755'>WinMerge</a></td>";
?>
        <td>2.1.4</td>
        <td>A visual text file differencing and merging tool for Win32 platforms.</td>
        <td>10@SF</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>

    <tr class=white>
<?
        echo "<td><a href='".$apidb_root."appview.php?appId=868'>FileZilla</a></td>";
?>
        <td>2.2.2</td>
        <td>FileZilla is a fast FTP client for Windows with a lot of features.</td>
        <td>11@SF</td>
        <td>No dlls installed.</td>
        <td><span class=todo>[TODO]</span></td>
    </tr>
</table>

<h2>The Silver List</h2> 
<p>The Silver list contains apps which we hope we can easily fix so they make it
   to Gold status:
<dl class=color4>

<?
echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=2'>WinZip</a></b>";
?>
 - The most popular compression utility for Windows just got better.</dt>
<dd>
   <ul class=white>
     <li> VERSION 8.1
     <li> Install: Yes. (Dlls installed none)
     <li> Run: Yes,(but it needs riched20.dll to shown the text in zipped files what
        includes a message, zipped files without messages works fine)
     <li> Tucows top 100 ladder: 4
  </ul>
</dd>

<?
echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=55'>ICQ for Windows</a></b>";
?>
 - The new and improved ICQ is here with all the great
features you've come to expect -- plus a whole new set!</dt>
<dd>
   <ul class=white>
   <li> VERSION 2002a
   <li> Install: Yes, but it need "touch /c/windows/system/setupapi.dll"
     (Dlls installed atl.dll,msvcrt.dll)
   <li> Run: No, required comctl32 (imagelist proble) and riched32 native.
     (it wasn't able to add users to contact list even with this native
      dlls, i don't know if it was a different problem, or wine related)
   <li> Tucows top 100 ladder: 5
  </ul>
</dd>

<?
echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=5'>Winamp</a></b>";
?>
 - This program has so many possibilities and offers such a wide
range of interfaces, you'll need no other player.</dt>
<dd>
   <ul class=white>
    <li> VERSION 3.0
    <li> Install: Yes. (Dlls installed none)
    <li> Run: No. (Need native msvcrt.dll then works)
    <li> Tucows top 100 ladder: 10
  </ul>
</dd>

<?
 echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=391'>WinRAR</a></b>";
?>
 - This is a version of the popular RAR compression format, offering
significantly improved compression ratios.</dt>
<dd>
   <ul class=white>
    <li> VERSION 3.00
    <li> Install: Yes. (It will install winrar in the directory what you are
      when run the installer, it is a buggy because you must stop the
      installation with ctrl+c when it will ask by "overwrite files", but
      it works).
    <li> Run: Yes. (minor glitches in bugzilla)
    <li> Tucows top 100 ladder: 11
  </ul>
</dd>

<?
    echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=288'>WinMX</a></b>";
?>
 - Take file sharing to a new level.</dt>
<dd>
   <ul class=white>
    <li> VERSION 3.22
    <li> Install: Yes (Dlls installed none)
    <li> Run: Yes. (listbox is not working in it (comctl32))
    <li> Tucows top 100 ladder: 50
  </ul>
</dd>

<?
    echo "<dt class=ltgrey><b><a href='".$apidb_root."appview.php?appId=1757'>SnagIt</a></b>";
?>
 - Use this to capture and manage images, text, and video.</dt>
<dd>
   <ul class=white>
    <li> VERSION 6.1.1
    <li> Install: Yes. (Dlls installed advpack.dll, setupapi.dll, w95inf16.dll,
      cfgmgr32.dll,shfolder.dll,w95inf32.dll)
    <li> Run: Partial. (it has too options, some options like capture avi, or
      capture web are not working)
    <li> Tucows top 100 ladder: No
  </ul>
</dd>

</dl>

<h1>Other Wine Application Compatibility Sites</h1>

<p>
<a href="http://frankscorner.org"><b>Frank's Corner</b></a>:  Frank has a fantastic Wine
application site. The site contains tips and howtos on getting listed apps to run.
</p>

<?
    echo "<p>&nbsp;</p>\n";

apidb_footer();


?>
