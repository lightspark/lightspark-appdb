<?php
function build_urlarg($vars)
{
	$arr = array();
	while(list($key, $val) = each($vars))
	    {
		if(is_array($val))
		    {
			while(list($idx, $value) = each($val))
			    {
				//echo "Encoding $key / $value<br>";
				$arr[] = rawurlencode($key."[]")."=".rawurlencode($value);
			    }
		    }
		else
		    $arr[] = $key."=".rawurlencode($val);
	    }
	return implode("&", $arr);
}


/*
 * return all values of a mapping as an array
 */
function values($arr)
{
    $res = array();
    while(list($k, $v) = each($arr))
        $res[] = $v;
    return $res;
}


/*
 * format date
 */
function print_date($sTimestamp)
{
    return date("F d Y  H:i:s", $sTimestamp);
}

function mysqltimestamp_to_unixtimestamp($sTimestamp)
{
  $d = substr($sTimestamp,6,2); // day
  $m = substr($sTimestamp,4,2); // month
  $y = substr($sTimestamp,0,4); // year
  $hours = substr($sTimestamp,8,2); // year
  $minutes = substr($sTimestamp,10,2); // year
  $seconds = substr($sTimestamp,12,2); // year
  return mktime($hours,$minutes,$seconds,$m, $d, $y);
}

function mysqldatetime_to_unixtimestamp($sDatetime)
{
    sscanf($sDatetime, "%4s-%2s-%2s %2s:%2s:%2s",
           &$y, &$m, &$d,
           &$hours, &$minutes, &$seconds);
    return mktime($hours,$minutes,$seconds,$m, $d, $y);
}

function get_remote()
{
    global $REMOTE_HOST, $REMOTE_ADDR;

    if($REMOTE_HOST)
	$ip = $REMOTE_HOST;
    else
	$ip = $REMOTE_ADDR;

    return $ip;
}

function htmlify_urls($text)
{
    //FIXME: wonder what the syntax is, this doesn't seem to work
    //    $text = strip_tags($text, "<a>,<b>,<i>,<ul>,<li>");

    // html-ify urls
    $urlreg = "([a-zA-Z]+://([^\t\r\n ]+))";
    $text = ereg_replace($urlreg, "<a href=\"\\1\"> \\2 </a>", $text);

    $emailreg = "([a-zA-Z0-9_%+.-]+@[^\t\r\n ]+)";
    $text = ereg_replace($emailreg, " <a href='mailto:\\1'>\\1</a>", $text);

    $text = str_replace("\n", "<br>", $text);

    return $text;
}

// open file and display contents of selected tag
function get_xml_tag ($file, $mode = null)
{
    if ($mode and file_exists($file))
    {
        $fp = @fopen($file, "r");
	$data = fread($fp, filesize($file));
	@fclose($fp);
	if (eregi("<" . $mode . ">(.*)</" . $mode . ">", $data, $out))
	{
	    return $out[1];
	}
    }
    else
    {
        return null;
    }
}

/* bugzilla functions */
function make_bugzilla_version_list($varname, $cvalue)
{
    $table = BUGZILLA_DB.".versions";
    $where = "WHERE product_id=".BUGZILLA_PRODUCT_ID;
    $query = "SELECT value FROM $table $where ORDER BY value";

    $result = query_bugzilladb($query);
    if(!$result) return;

    echo "<select name='$varname'>\n";
    echo "<option value=\" \">Choose ...</option>\n";
    while(list($value) = mysql_fetch_row($result))
    {
        if($value == $cvalue)
            echo "<option value=$value selected>$value\n";
        else
            echo "<option value=$value>$value\n";
    }
    echo "</select>\n";
}

function make_maintainer_rating_list($varname, $cvalue)
{
    
    echo "<select name='$varname'>\n";
    echo "<option value=\" \">Choose ...</option>\n";
    $aRating = array("Gold", "Silver", "Bronze", "Garbage");
    $iMax = count($aRating);

    for($i=0; $i < $iMax; $i++)
    {
        if($aRating[$i] == $cvalue)
            echo "<option value=$aRating[$i] selected>$aRating[$i]\n";
        else
            echo "<option value=$aRating[$i]>$aRating[$i]\n";
    }
    echo "</select>\n";
}

/* get the number of queued applications */
function getQueuedAppCount()
{
    $qstring = "SELECT count(*) as queued_apps FROM appFamily WHERE queued='true'";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_apps;
}

function getQueuedVersionCount()
{
    $qstring = "SELECT count(*) as queued_versions FROM appVersion WHERE queued='true'";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);

    /* we don't want to count the versions that are implicit in the applications */
    /* that are in the queue */
    return $ob->queued_versions - getQueuedAppCount();
}


/* get the number of queued appdata */
function getQueuedAppDataCount()
{
    $qstring = "SELECT count(*) as queued_appdata FROM appData WHERE queued='true'";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_appdata;
}

/* get the number of queued maintainers */
function getQueuedMaintainerCount()
{
    $qstring = "SELECT count(*) as queued_maintainers FROM appMaintainerQueue";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_maintainers;
}

/* get the total number of maintainers and applications in the appMaintainers table */
function getMaintainerCount()
{
    $qstring = "SELECT count(*) as maintainers FROM appMaintainers";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->maintainers;
}

/* get the total number of vendors from the vendor table */
function getVendorCount()
{
    $qstring = "SELECT count(*) as vendors FROM vendor";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->vendors;
}

/* Get the number of users in the database */
function getNumberOfComments()
{
    $result = query_appdb("SELECT count(*) as num_comments FROM appComments;");
    $row = mysql_fetch_object($result);
    return $row->num_comments;
}

/* Get the number of versions in the database */
function getNumberOfVersions()
{
    $result = query_appdb("SELECT count(versionId) as num_versions FROM appVersion WHERE versionName != 'NONAME';");
    $row = mysql_fetch_object($result);
    return $row->num_versions;
}

/* Get the number of maintainers in the database */
function getNumberOfMaintainers()
{
    $result = query_appdb("SELECT DISTINCT userId FROM appMaintainers;");
    return mysql_num_rows($result);
}

/* Get the number of app familes in the database */
function getNumberOfAppFamilies()
{
    $result = query_appdb("SELECT count(*) as num_appfamilies FROM appFamily;");
    $row = mysql_fetch_object($result);
    return $row->num_appfamilies;
}

/* Get the number of images in the database */
function getNumberOfImages()
{
    $result = query_appdb("SELECT count(*) as num_images FROM appData WHERE type='image';");
    $row = mysql_fetch_object($result);
    return $row->num_images;
}

function lookupVendorName($vendorId)
{
    $sResult = query_appdb("SELECT * FROM vendor ".
               "WHERE vendorId = ".$vendorId);
    if(!$sResult || mysql_num_rows($sResult) != 1)
        return "Unknown vendor";

    $vendor = mysql_fetch_object($sResult);
    return $vendor->vendorName;
}

/* Output the rows for the Top-X tables on the main page */
function outputTopXRowAppsFromRating($rating, $num_apps)
{
    $sQuery = "SELECT appVotes.appId AS appId, COUNT( appVotes.appId ) AS c
           FROM appVotes, appVersion
           WHERE appVersion.maintainer_rating = '$rating'
           AND appVersion.appId = appVotes.appId
           GROUP BY appVotes.appId
           ORDER BY c DESC
           LIMIT $num_apps";
    $hResult = query_appdb($sQuery);
    $num_apps-=mysql_num_rows($hResult); /* take away the rows we are outputting here */
    while($oRow = mysql_fetch_object($hResult))
    {
        $oApp = new Application($oRow->appId);
        // image
        $img = get_screenshot_img($oRow->appId);
        echo '
    <tr class="white">
      <td><a href="appview.php?appId='.$oRow->appId.'">'.$oApp->sName.'</a></td>
        <td>'.trim_description($oApp->sDescription).'</td>
        <td>'.$img.'</td>
    </tr>';
    }

    /* if we have any empty spots in the list, get these from applications with images */
    $sQuery = "SELECT DISTINCT appVersion.appId as appId
           FROM appVersion, appData
           WHERE appVersion.maintainer_rating = '$rating'
           AND appVersion.versionId = appData.versionId
           AND appData.type = 'image'
           AND appData.queued = 'false'
           LIMIT $num_apps";
    $hResult = query_appdb($sQuery);
    while($oRow = mysql_fetch_object($hResult))
    {
        $oApp = new Application($oRow->appId);
        // image
        $img = get_screenshot_img($oRow->appId);
        echo '
    <tr class="white">
      <td><a href="appview.php?appId='.$oRow->appId.'">'.$oApp->sName.'</a></td>
        <td>'.trim_description($oApp->sDescription).'</td>
        <td>'.$img.'</td>
    </tr>';
    }
}

/* search the database and return a hResult from the query_appdb() */
function searchForApplication($search_words)
{
    /* split search words up so we can see if any of them match a vendor name or vendor url */
    $split_words = split(" ", $search_words);
    $vendorIdArray = array();

    /* find all of the vendors whos names or urls match words in our */
    /* search parameters */
    foreach ($split_words as $key=>$value)
    {
        $sQuery = "SELECT vendorId from vendor where vendorName LIKE '%".addslashes($value)."%'
                                       OR vendorURL LIKE '%".addslashes($value)."%'";
        $hResult = query_appdb($sQuery);
        while($oRow = mysql_fetch_object($hResult))
        {
            array_push($vendorIdArray, $oRow->vendorId);
        }
    }

    /* base query */
    $sQuery = "SELECT *
           FROM appFamily, vendor
           WHERE appName != 'NONAME'
           AND appFamily.vendorId = vendor.vendorId
           AND queued = 'false'
           AND (appName LIKE '%".addslashes($search_words)."%'
           OR keywords LIKE '%".addslashes($search_words)."%'";

    /* append to the query any vendors that we matched with */
    foreach($vendorIdArray as $key=>$value)
    {
        $sQuery.=" OR appFamily.vendorId=$value";
    }

    $sQuery.=" ) ORDER BY appName";

    $hResult = query_appdb($sQuery);
    return $hResult;
}

function searchForApplicationFuzzy($search_words, $minMatchingPercent)
{
    $foundAValue = false;
    $excludeAppIdArray = array();
    $appIdArray = array();

    /* add on all of the like matches that we can find */
    $hResult = searchForApplication($search_words);
    while($oRow = mysql_fetch_object($hResult))
    {
        array_push($excludeAppIdArray, $oRow->appId);
    }

    /* add on all of the fuzzy matches we can find */
    $sQuery = "SELECT appName, appId FROM appFamily WHERE queued = 'false'";
    foreach ($excludeAppIdArray as $key=>$value)
    {
        $sQuery.=" AND appId != '$value'";
    }
    $sQuery.=";";

    /* capitalize the search words */
    $search_words = strtoupper($search_words);

    $hResult = query_appdb($sQuery);
    while($oRow = mysql_fetch_object($hResult))
    {
        $oRow->appName = strtoupper($oRow->appName); /* convert the appname to upper case */
        similar_text($oRow->appName, $search_words, $similarity_pst);
        if(number_format($similarity_pst, 0) > $minMatchingPercent)
        {
            $foundAValue = true;
            array_push($appIdArray, $oRow->appId);
        }
    }

    if($foundAValue == false)
        return null;

    $sQuery = "SELECT * from appFamily WHERE ";

    $firstEntry = true;
    foreach ($appIdArray as $key=>$value)
    {
        if($firstEntry == true)
        {
            $sQuery.="appId='$value'";
            $firstEntry = false;
        } else
        {
            $sQuery.=" OR appId='$value'";
        }
    }
    $sQuery.=" ORDER BY appName;";

    $hResult = query_appdb($sQuery);
    return $hResult;
}

function outputSearchTableForhResult($search_words, $hResult)
{
    if(($hResult == null) || (mysql_num_rows($hResult) == 0))
    {
        // do something
        echo html_frame_start("","98%");
        echo "No matches found for '". urlencode($search_words) .  "'\n";
        echo html_frame_end();
    } else
    {
        echo html_frame_start("","98%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Description</font></td>\n";
        echo "    <td><font color=white>No. Versions</font></td>\n";
        echo "</tr>\n\n";

        $c = 0;
        while($ob = mysql_fetch_object($hResult))
        {
            //skip if a NONAME
            if ($ob->appName == "NONAME") { continue; }
		
            //set row color
            $bgcolor = ($c % 2) ? 'color0' : 'color1';
		
            //count versions
            $query = query_appdb("SELECT count(*) as versions FROM appVersion WHERE appId = $ob->appId AND versionName != 'NONAME'");
            $y = mysql_fetch_object($query);
		
            //display row
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".html_ahref($ob->appName,BASE."appview.php?appId=$ob->appId")."</td>\n";
            echo "    <td>".trim_description($ob->description)."</td>\n";
            echo "    <td>$y->versions &nbsp;</td>\n";
            echo "</tr>\n\n";
		
            $c++;    
        }

        echo "<tr><td colspan=3 class=color4><font color=white>$c match(es) found</font></td></tr>\n";
        echo "</table>\n\n";
        echo html_frame_end();
    }
}

/**
 * display the versions
 * Used in appview.php and adminAppQueue.php
 */
function display_versions($iAppId, $aVersionsIds)
{
    if ($aVersionsIds)
    {
        echo html_frame_start("","98%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"1\">\n\n";

        echo "<tr class=color4>\n";
        echo "    <td width=\"80\">Version</td>\n";
        echo "    <td>Description</td>\n";
        echo "    <td width=\"80\">Rating</td>\n";
        echo "    <td width=\"80\">Wine version</td>\n";
        echo "    <td width=\"40\">Comments</td>\n";
        echo "</tr>\n\n";
      
        $c = 0;
        foreach($aVersionsIds as $iVersionId)
        {
            $oVersion = new Version($iVersionId);

            // set row color
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

            //display row
            echo "<tr class=$bgcolor>\n";
            echo "    <td><a href=\"".BASE."appview.php?versionId=".$iVersionId."\">".$oVersion->sName."</a></td>\n";
            echo "    <td>".trim_description($oVersion->sDescription)."</td>\n";
            echo "    <td align=center>".$oVersion->sTestedRating."</td>\n";
            echo "    <td align=center>".$oVersion->sTestedRelease."</td>\n";
            echo "    <td align=center>".sizeof($oVersion->aCommentsIds)."</td>\n";
            echo "</tr>\n\n";

            $c++;   

        }
        echo "</table>\n";
        echo html_frame_end("Click the Version Name to view the details of that Version");
    }
}

/* pass in $isVersion of true if we are processing changes for an app version */
/* or false if processing changes for an application family */
function process_app_version_changes($isVersion)
{
    if($isVersion)
    {
        $oVersion = new Version($_REQUEST['versionId']);
        $oApp = new Application($_REQUEST['appId']);
    }

    // commit changes of form to database
    if(($_REQUEST['submit'] == "Update Database") && $isVersion) /* is a version */
    {
        $oVersion->update($_REQUEST['versionName'], $_REQUEST['description'], $_REQUEST['maintainer_release'], $_REQUEST['maintainer_rating']);
    } else if(($_REQUEST['submit'] == "Update Database") && !$isVersion) /* is an application */
    {
        $oApp = new Application($_REQUEST['appId']);
        $oApp->update($_REQUEST['appName'], $_REQUEST['description'], $_REQUEST['keywords'], $_REQUEST['webPage'], $_REQUEST['vendorId'], $_REQUEST['catId']);
    } else if($_REQUEST['submit'] == "Update URL")
    {

        $sWhatChanged = "";
        $bAppChanged = false;

        if (!empty($_REQUEST['url_desc']) && !empty($_REQUEST['url']) )
        {
            // process added URL
            if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>{$_REQUEST['url']}:</b> {$_REQUEST['url_desc']} </p>"; }

            if($isVersion)
            {
                $aInsert = compile_insert_string( array('versionId' => $_REQUEST['versionId'],
                                             'type' => 'url',
                                             'description' => $_REQUEST['url_desc'],
                                             'url' => $_REQUEST['url']));
            } else
            {
                $aInsert = compile_insert_string( array( 'appId' => $_REQUEST['appId'],
                                             'type' => 'url',
                                             'description' => $_REQUEST['url_desc'],
                                             'url' => $_REQUEST['url']));
            
            }
            
            $sQuery = "INSERT INTO appData ({$aInsert['FIELDS']}) VALUES ({$aInsert['VALUES']})";
	    
            if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>query:</b> $sQuery </p>"; }

            if (query_appdb($sQuery))
            {
                addmsg("The URL was successfully added into the database", "green");
                $sWhatChanged .= "  Added Url:     Description: ".stripslashes($_REQUEST['url_desc'])."\n";
                $sWhatChanged .= "                         Url: ".stripslashes($_REQUEST['url'])."\n";
                $bAppChanged = true;
            }
        }
        
        // Process changed URLs  
        for($i = 0; $i < $_REQUEST['rows']; $i++)
        {
            if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>{$_REQUEST['adescription'][$i]}:</b> {$_REQUEST['aURL'][$i]}: {$_REQUEST['adelete'][$i]} : {$_REQUEST['aId'][$i]} : .{$_REQUEST['aOldDesc'][$i]}. : {$_REQUEST['aOldURL'][$i]}</p>"; }

            if ($_REQUEST['adelete'][$i] == "on")
            {
	            $hResult = query_appdb("DELETE FROM appData WHERE id = '{$_REQUEST['aId'][$i]}'");

                if($hResult)
                {
                    addmsg("<p><b>Successfully deleted URL ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');
                    $sWhatChanged .= "Deleted Url:     Description: ".stripslashes($_REQUEST['aOldDesc'][$i])."\n";
                    $sWhatChanged .= "                         url: ".stripslashes($_REQUEST['aOldURL'][$i])."\n";
                    $bAppChanged = true;
                }


            }
            else if( $_REQUEST['aURL'][$i] != $_REQUEST['aOldURL'][$i] || $_REQUEST['adescription'][$i] != $_REQUEST['aOldDesc'][$i])
            {
                if(empty($_REQUEST['aURL'][$i]) || empty($_REQUEST['adescription'][$i]))
                    addmsg("The URL or description was blank. URL not changed in the database", "red");
                else
                {
                    $sUpdate = compile_update_string( array( 'description' => $_REQUEST['adescription'][$i],
                                                     'url' => $_REQUEST['aURL'][$i]));
                    if (query_appdb("UPDATE appData SET $sUpdate WHERE id = '{$_REQUEST['aId'][$i]}'"))
                    {
                         addmsg("<p><b>Successfully updated ".$_REQUEST['aOldDesc'][$i]." (".$_REQUEST['aOldURL'][$i].")</b></p>\n",'green');
                         $sWhatChanged .= "Changed Url: Old Description: ".stripslashes($_REQUEST['aOldDesc'][$i])."\n";
                         $sWhatChanged .= "                     Old Url: ".stripslashes($_REQUEST['aOldURL'][$i])."\n";
                         $sWhatChanged .= "             New Description: ".stripslashes($_REQUEST['adescription'][$i])."\n";
                         $sWhatChanged .= "                     New url: ".stripslashes($_REQUEST['aURL'][$i])."\n";
                         $bAppChanged = true;
                    }
                }
            }
        }
        if ($bAppChanged)
        {
            $sEmail = get_notify_email_address_list($_REQUEST['appId']);
            if($sEmail)
            {
                if($isVersion)
                    $sSubject = "Links for ".$oApp->sName." ".$oVersion->sName." have been updated by ".$_SESSION['current']->sRealname;
                else
                    $sSubject = "Links for ".$oApp->sName." have been updated by ".$_SESSION['current']->sRealname;
                    
                $sMsg  = APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."\n";
                $sMsg .= "\n";
                $sMsg .= "The following changes have been made:";
                $sMsg .= "\n";
                $sMsg .= $sWhatChanged."\n";
                $sMsg .= "\n";

                mail_appdb($sEmail, $sSubject ,$sMsg);
            }
        }
    }
}

function perform_search_and_output_results($search_words)
{
    /* trim off leading and trailing spaces in $search_words */
    /* to improve matching accuracy */
    $search_words = trim($search_words);

    /* Remove any of the words in the ignore_words array.  these are far too common */
    /* and will result in way too many matches if we leave them in */
    /* We will also remove any single letter search words */
    $ignore_words = array('I', 'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com',
                          'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of',
                          'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when',
                          'where', 'who', 'will', 'with', 'und', 'the', 'www');

    $filtered_search = "";

    /* search each word in $search_words */
    $split_words = split(" ", $search_words);
    foreach($split_words as $key=>$value)
    {
        /* search each item in the $ignore_words array */
        $found = false;
        foreach($ignore_words as $ik=>$iv)
        {
            /* if we find a match we should flag it as so */
            if(strtoupper($value) == strtoupper($iv))
            {
                $found = true;
                break; /* break out of this foreach loop */
            }
        }

        /* remove all single letters */
        if((strlen($value) == 1) && !is_numeric($value))
            $found = true;

        /* if we didn't find this word, keep it */
        if($found == false)
        {
            if($filtered_search)
                $filtered_search.=" $value";
            else
                $filtered_search="$value";
        } else
        {
            if($removed_words == "")
                $removed_words.="'".$value."'";
            else
                $removed_words.=", '".$value."'";
        }
    }

    echo "<b>Searching for '".$filtered_search."'";
    if($removed_words)
        echo ", removed '".$removed_words."' from your search as they are too common</b>";
    echo "<center><b>Like matches</b></center>";

    /* replace the existing search with the filtered_search */
    $search_words = $filtered_search;

    $hResult = searchForApplication($search_words);
    outputSearchTableForhResult($search_words, $hResult);

    $minMatchingPercent = 60;
    echo "<center><b>Fuzzy matches - minimum ".$minMatchingPercent."% match</b></center>";
    $hResult = searchForApplicationFuzzy($search_words, $minMatchingPercent);
    outputSearchTableForhResult($search_words, $hResult);
}

?>