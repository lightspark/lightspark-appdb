<?php
/**
 * display an error page
 */
function util_show_error_page_and_exit($text = null, $message = null)
{
    if (!$text) {
        $text = "You must be logged in to perform that operation.";
    }
    header("HTTP/1.0 404 Object not found or user is not logged in");
    apidb_header("Oops");
    echo "<div align=center><font color=red><b>$text</b></font></div>\n";
    echo "<p>$message</p>\n";
    apidb_footer();
    exit;
}

/**
 * redirect to $url
 */
function util_redirect_and_exit($url)
{
    header("Location: ".$url); 
    exit;
}

function makeSafe($var)
{
/* Disable addslashes() until we can use more finely grained filtering on user input */
/*    $var = trim(addslashes($var)); */
    return $var;
}

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


// print the month, day, year, hour, minute, second
function print_date($sTimestamp)
{
    return date("F d Y  H:i:s", $sTimestamp);
}

// print the month, day and year
function print_short_date($sTimestamp)
{
    return date("F d Y", $sTimestamp);
}

function mysqltimestamp_to_unixtimestamp($sTimestamp)
{
    $sResult = mysql_get_server_info();
    $fVersion = substr($sResult, 0, 3);

    /* This differs between MySQL versions, newer ones are in the form
       yyyy-mm-dd hh:mm:ss */
    if($fVersion >= 4.1)
    {
        $iDay = substr($sTimestamp, 8, 2);
        $iMonth = substr($sTimestamp, 5, 2);
        $iYear = substr($sTimestamp, 0, 4);
        $iHours = substr($sTimestamp, 11, 2);
        $iMinutes = substr($sTimestamp, 14, 2);
        $iSeconds = substr($sTimestamp, 17, 2);
    } else
    /* The old ones are in the form yyyymmddhhmmss */
    {
        $iDay = substr($sTimestamp,6,2);
        $iMonth = substr($sTimestamp,4,2);
        $iYear = substr($sTimestamp,0,4);
        $iHours = substr($sTimestamp,8,2);
        $iMinutes = substr($sTimestamp,10,2);
        $iSeconds = substr($sTimestamp,12,2);
    }
    return mktime($iHours, $iMinutes, $iSeconds, $iMonth, $iDay, $iYear);
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
    $sQuery = "SELECT value FROM $table $where ORDER BY value";

    $hResult = query_bugzilladb($sQuery);
    if(!$hResult) return;

    echo "<select name='$varname'>\n";
    echo "<option value=\"\">Choose ...</option>\n";
    while(list($value) = mysql_fetch_row($hResult))
    {
        if($value == "unspecified")
        {
            // We do not unspecified versions!!!
        } else
        {
            if($value == $cvalue)
                echo "<option value=$value selected>$value\n";
            else
                echo "<option value=$value>$value\n";
        }
    }
    echo "</select>\n";
}

function make_maintainer_rating_list($varname, $cvalue)
{
    
    echo "<select name='$varname'>\n";
    echo "<option value=\"\">Choose ...</option>\n";
    $aRating = array("Platinum", "Gold", "Silver", "Bronze", "Garbage");
    $iMax = count($aRating);

    for($i=0; $i < $iMax; $i++)
    {
        if($aRating[$i] == $cvalue)
            echo "<option class=$aRating[$i] value=$aRating[$i] selected>$aRating[$i]\n";
        else
            echo "<option class=$aRating[$i] value=$aRating[$i]>$aRating[$i]\n";
    }
    echo "</select>\n";
}

/* Get the number of users in the database */
function getNumberOfComments()
{
    $hResult = query_parameters("SELECT count(*) as num_comments FROM appComments;");
    $oRow = mysql_fetch_object($hResult);
    return $oRow->num_comments;
}

/* Get the number of queued bug links in the database */
function getNumberOfQueuedBugLinks()
{
    $hResult = query_parameters("SELECT count(*) as num_buglinks FROM buglinks WHERE queued='true';");
    if($hResult)
    {
      $oRow = mysql_fetch_object($hResult);
      return $oRow->num_buglinks;
    }
    return 0;
}

/* Get the number of bug links in the database */
function getNumberOfBugLinks()
{
    $hResult = query_parameters("SELECT count(*) as num_buglinks FROM buglinks;");
    if($hResult)
    {
      $oRow = mysql_fetch_object($hResult);
      return $oRow->num_buglinks;
    }
    return 0;
}

/* used by outputTopXRowAppsFromRating() to reduce duplicated code */
function outputTopXRow($oRow)
{
    $oVersion = new Version($oRow->versionId);
    $oApp = new Application($oVersion->iAppId);
    $img = Screenshot::get_random_screenshot_img(null, $oRow->versionId, false); // image, disable extra formatting
    html_tr_highlight_clickable($oVersion->objectMakeUrl(), "white", "#f0f6ff", "white");
    echo '
      <td class="app_name">'.version::fullNameLink($oVersion->iVersionId).'</td>
      <td>'.util_trim_description($oApp->sDescription).'</td>
      <td><center>'.$img.'</center></td>
    </tr>';
}

/* Output the rows for the Top-X tables on the main page */
function outputTopXRowAppsFromRating($sRating, $iNumApps)
{
    /* clean the input values so we can continue to use query_appdb() */
    $sRating = mysql_real_escape_string($sRating);
    $iNumApps = mysql_real_escape_string($iNumApps);

    /* list of versionIds we've already output, so we don't output */
    /* them again when filling in any empty spots in the list */
    $aVersionId = array();

    $sQuery = "SELECT appVotes.versionId, COUNT( appVotes.versionId ) AS c
           FROM appVotes, appVersion
           WHERE appVersion.maintainer_rating = '?'
           AND appVersion.versionId = appVotes.versionId
           GROUP BY appVotes.versionId
           ORDER BY c DESC
           LIMIT ?";
    $hResult = query_parameters($sQuery, $sRating, $iNumApps);
    $iNumApps -= mysql_num_rows($hResult); /* take away the rows we are outputting here */
    while($oRow = mysql_fetch_object($hResult))
    {
        /* keep track of the apps we've already output */
        $aVersionId[] = $oRow->versionId;
        outputTopXRow($oRow);
    }

    /* if we have no more app entries we should stop now and save ourselves a query */
    if(!$iNumApps) return;

    /* if we have any empty spots in the list, get these from applications with images */
    $sQuery = "SELECT DISTINCT appVersion.versionId
           FROM appVersion, appData
           WHERE appVersion.maintainer_rating = '$sRating'
           AND appVersion.versionId = appData.versionId
           AND appData.type = 'screenshot'
           AND appData.queued = 'false'";

    /* make sure we exclude any apps we've already output */
    foreach($aVersionId as $key=>$value)
        $sQuery.="AND appVersion.versionId != '".$value."' ";

    $sQuery .= " LIMIT $iNumApps";

    /* get the list that will fill the empty spots */
    $hResult = query_appdb($sQuery);
    while($oRow = mysql_fetch_object($hResult))
        outputTopXRow($oRow);
}

/* return true if this word is in the list of words to ignore */
function isIgnoredWord($sWord)
{
    $ignore_words = array('I', 'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com',
                          'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of',
                          'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when',
                          'where', 'who', 'will', 'with', 'und', 'the', 'www', 'game');

    $found = false;

    /* search each item in the $ignore_words array */
    foreach($ignore_words as $ik=>$iv)
    {
        /* if we find a match we should flag it as so */
        if(strtoupper($sWord) == strtoupper($iv))
        {
            $found = true;
            break; /* break out of this foreach loop */
        }
    }

    return $found;
}

/* remove common words from $search_words to improve our searching results */
function cleanupSearchWords($search_words)
{
    /* trim off leading and trailing spaces in $search_words */
    /* to improve matching accuracy */
    $search_words = trim($search_words);

    $filtered_search = "";

    /* search each word in $search_words */
    $split_words = split(" ", $search_words);
    foreach($split_words as $key=>$value)
    {
        /* see if this word is in the ignore list */
        /* we remove any of the words in the ignore_words array.  these are far too common */
        /* and will result in way too many matches if we leave them in */
        /* We will also remove any single letter search words */
        $found = isIgnoredWord($value);

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

    /* replace the existing search with the filtered_search */
    $search_words = $filtered_search;

    return $search_words;
}

/* search the database and return a hResult from the query_appdb() */
function searchForApplication($search_words)
{
    /* cleanup search words */
    $search_words = cleanupSearchWords($search_words);

    /* remove any search words less than 4 letters */
    $split_words = array();
    $split_search_words = split(" ", $search_words);
    foreach($split_search_words as $key=>$value)
    {
        if(strlen($value) >= 4)
            array_push($split_words, $value);
    }

    $vendorIdArray = array();

    /* find all of the vendors whos names or urls match words in our */
    /* search parameters */
    foreach ($split_words as $key=>$value)
    {
        $sQuery = "SELECT vendorId from vendor where vendorName LIKE '%?%'
                                       OR vendorURL LIKE '%?%'";
        $hResult = query_parameters($sQuery, $value, $value);
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
           AND appFamily.queued = 'false'
           AND (appName LIKE '%".mysql_real_escape_string($search_words)."%'
           OR keywords LIKE '%".mysql_real_escape_string($search_words)."%'";

    /* append to the query any vendors that we matched with */
    foreach($vendorIdArray as $key=>$value)
    {
        $sQuery.=" OR appFamily.vendorId=".mysql_real_escape_string($value);
    }

    $sQuery.=" ) ORDER BY appName";

    $hResult = query_appdb($sQuery);
    return $hResult;
}

function searchForApplicationFuzzy($search_words, $minMatchingPercent)
{
    /* cleanup search words */
    $search_words = cleanupSearchWords($search_words);

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
        $sQuery.=" AND appId != '".mysql_real_escape_string($value)."'";
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
            $sQuery.="appId='".mysql_real_escape_string($value)."'";
            $firstEntry = false;
        } else
        {
            $sQuery.=" OR appId='".mysql_real_escape_string($value)."'";
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
        while($oRow = mysql_fetch_object($hResult))
        {
            $oApp = new application($oRow->appId);
            //skip if a NONAME
            if ($oRow->appName == "NONAME") { continue; }
		
            //set row color
            $bgcolor = ($c % 2) ? 'color0' : 'color1';
		
            //count versions
            $hResult2 = query_parameters("SELECT count(*) as versions FROM appVersion WHERE appId = '?' AND versionName != 'NONAME' and queued = 'false'",
                                     $oRow->appId);
            $y = mysql_fetch_object($hResult2);
		
            //display row
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".html_ahref($oRow->appName,$oApp->objectMakeUrl())."</td>\n";
            echo "    <td>".util_trim_description($oRow->description)."</td>\n";
            echo "    <td>$y->versions &nbsp;</td>\n";
            echo "</tr>\n\n";
		
            $c++;    
        }

        echo "<tr><td colspan=3 class=color4><font color=white>$c match(es) found</font></td></tr>\n";
        echo "</table>\n\n";
        echo html_frame_end();
    }
}

/* pass in $isVersion of true if we are processing changes for an app version */
/* or false if processing changes for an application family */
function process_app_version_changes($bIsVersion)
{
    global $aClean;

    /* load up the version or application depending on which values are set */
    if($bIsVersion)
        $oVersion = new Version($aClean['iVersionId']);
    else
        $oApp = new Application($aClean['iAppId']);

    // commit changes of form to database
    if(($aClean['sSubmit'] == "Update Database") && $bIsVersion) /* is a version */
    {
        $oVersion->GetOutputEditorValues($aClean);
        $oVersion->update();
    } else if(($aClean['sSubmit'] == "Update Database") && !$bIsVersion) /* is an application */
    {
        $oApp->GetOutputEditorValues($aClean);
        $oApp->update();
    }
}

function perform_search_and_output_results($search_words)
{
    echo "<b>Searching for '".$search_words."'";

    echo "<center><b>Like matches</b></center>";
    $hResult = searchForApplication($search_words);
    outputSearchTableForhResult($search_words, $hResult);

    $minMatchingPercent = 60;
    echo "<center><b>Fuzzy matches - minimum ".$minMatchingPercent."% match</b></center>";
    $hResult = searchForApplicationFuzzy($search_words, $minMatchingPercent);
    outputSearchTableForhResult($search_words, $hResult);
}

function display_page_range($iCurrentPage=1, $iPageRange=1, $iTotalPages=1, $sLinkurl=NULL)
{
    if($sLinkurl==NULL)
    {
        $sLinkurl = $_SERVER['PHP_SELF']."?";
    }
    /* display the links to each of these pages */
    $iCurrentPage = max(1,(min($iCurrentPage,$iTotalPages)));
    $iPageRange = min($iPageRange,$iTotalPages);

    if($iCurrentPage <= ceil($iPageRange/2))
    {
        $iStartPage = 1;
        $iEndPage = $iPageRange;
    } else
    {
        if($iCurrentPage + ($iPageRange/2) > $iTotalPages)
        {
            $iStartPage = $iTotalPages - $iPageRange;
            $iEndPage = $iTotalPages;
        } else
        {
            $iStartPage = $iCurrentPage - floor($iPageRange/2);
            $iEndPage = $iCurrentPage + floor($iPageRange/2);
        }
    }
    $iStartPage = max(1,$iStartPage);

    if($iCurrentPage != 1)
    {
        echo "<a href='".$sLinkurl."&iPage=1'>|&lt</a>&nbsp";
        $iPreviousPage = $iCurrentPage - 1;
        echo "<a href='".$sLinkurl."&iPage=$iPreviousPage'>&lt</a>&nbsp";
    } else
    {
        echo "|&lt &lt ";
    }
    /* display the desired range */
    for($iPg = $iStartPage; $iPg <= $iEndPage; $iPg++)
    {
        if($iPg != $iCurrentPage)
            echo "<a href='".$sLinkurl."&iPage=".$iPg."'>$iPg</a> ";
        else
            echo "$iPg ";
    }

    if($iCurrentPage < $iTotalPages)
    {
        $iNextPage = $iCurrentPage + 1;
        echo "<a href='".$sLinkurl."&iPage=$iNextPage'>&gt</a> ";
        echo "<a href='".$sLinkurl."&iPage=$iTotalPages'>&gt|</a> ";
    } else
    {
        echo "&gt &gt|";
    }
}

// Expand a path like /something/somedirectory/../ to /something
// from http://us2.php.net/realpath
function SimplifyPath($path) {
  $dirs = explode('/',$path);

  for($i=0; $i<count($dirs);$i++)
  {
   if($dirs[$i]=="." || $dirs[$i]=="")
   {
     array_splice($dirs,$i,1);
     $i--;
   }

   if($dirs[$i]=="..")
   {
     $cnt = count($dirs);
     $dirs=Simplify($dirs, $i);
     $i-= $cnt-count($dirs);
   }
  }
  return implode('/',$dirs);
}

function Simplify($dirs, $idx)
{
  if($idx==0) return $dirs;

  if($dirs[$idx-1]=="..") Simplify($dirs, $idx-1);
  else  array_splice($dirs,$idx-1,2);

  return $dirs;
}
// End of snippet of code copied from php.net

// Use the directory of PHP_SELF and BASE and the relative path
// to get a simplified path to an appdb directory or file
// Used for the Xinha _editor_url because some plugins like SpellChecker
// won't work with relative paths like ../xinha
function GetSimplifiedPath($relative)
{
    return "/".SimplifyPath(dirname($_SERVER[PHP_SELF])."/".BASE.$relative);
}

function HtmlAreaLoaderScript($aTextareas)
{
    static $outputIndex = 0;

    echo '
  <script type="text/javascript">';
    // You must set _editor_url to the URL (including trailing slash) where
    // where xinha is installed, it's highly recommended to use an absolute URL
    //  eg: _editor_url = "/path/to/xinha/";
    // You may try a relative URL if you wish]
    //  eg: _editor_url = "../";
    // in this example we do a little regular expression to find the absolute path.
    // NOTE: we use GetSimplifiedPath() because we cannot use a relative path and have
    //   all of the plugins work correctly.  Specifically the SpellChecker plugin
    //   requires a absolute url path to the xinha directory
    echo '
    _editor_url  = "'.GetSimplifiedPath("xinha/").'", \'\';
    _editor_lang = "en";      // And the language we need to use in the editor.
  </script>';

    echo '
  <!-- Load up the actual editor core -->
  <script type="text/javascript" src="'.BASE.'xinha/XinhaCore.js"></script>

  <script type="text/javascript">
    xinha_editors_'.$outputIndex.' = null;
    xinha_init_'.$outputIndex.'    = null;';

    /* only need to nll out the first set of config and plugins */
    /* as we will reuse these for additional htmlareas */
    if($outputIndex == 0)
    {
        echo '
    xinha_config_'.$outputIndex.'  = null;
    xinha_plugins_'.$outputIndex.' = null;';
    }

    echo '
    // This contains the names of textareas we will make into Xinha editors
    xinha_init_'.$outputIndex.' = xinha_init_'.$outputIndex.' ? xinha_init_'.$outputIndex.' : function()
    {';

      /** STEP 1 ***************************************************************
       * First, what are the plugins you will be using in the editors on this
       * page.  List all the plugins you will need, even if not all the editors
       * will use all the plugins.
       ************************************************************************/
    if($outputIndex == 0)
    {
      echo '
      xinha_plugins_'.$outputIndex.' = xinha_plugins_'.$outputIndex.' ? xinha_plugins_'.$outputIndex.' :
      [
       \'CharacterMap\',
       \'CharCounter\',
       \'ContextMenu\',
       \'FullScreen\',
       \'ListType\',
       \'SpellChecker\',
       \'Stylist\',
       \'SuperClean\',
       \'TableOperations\',
       \'DynamicCSS\',
       \'FindReplace\'
      ];

      // THIS BIT OF JAVASCRIPT LOADS THE PLUGINS, NO TOUCHING  :)
      if(!HTMLArea.loadPlugins(xinha_plugins_'.$outputIndex.', xinha_init_'.$outputIndex.')) return;';
    } else
    {
      echo '
      // THIS BIT OF JAVASCRIPT LOADS THE PLUGINS, NO TOUCHING  :)
      if(!HTMLArea.loadPlugins(xinha_plugins_0, xinha_init_'.$outputIndex.')) return;';   
    }

      /** STEP 2 ***************************************************************
       * Now, what are the names of the textareas you will be turning into
       * editors?
       ************************************************************************/

      // NOTE: we generate the editor names here so we can easily have any number of htmlarea editors
      //  and can reuse all of this code
      echo '
      xinha_editors_'.$outputIndex.' = xinha_editors_'.$outputIndex.' ? xinha_editors_'.$outputIndex.' :
      [';

      $firstEntry = true;
      foreach($aTextareas as $key=>$value)
      {
          if($firstEntry)
          {
              echo "'$value'";
              $firstEntry = false;
          } else
          {
              echo ", '$value'";
          }
      }

      echo '
      ];';

      /** STEP 3 ***************************************************************
       * We create a default configuration to be used by all the editors.
       * If you wish to configure some of the editors differently this will be
       * done in step 5.
       *
       * If you want to modify the default config you might do something like this.
       *
       *   xinha_config = new HTMLArea.Config();
       *   xinha_config.width  = \'640px\';
       *   xinha_config.height = \'420px\';
       *
       *************************************************************************/
      /* We only need the configuration output for the first htmlarea on a given page */
      if($outputIndex == 0)
      {
       echo '
       xinha_config_'.$outputIndex.' = new HTMLArea.Config();

       xinha_config_'.$outputIndex.'.toolbar = [
        ["popupeditor"],
        ["separator","fontsize","bold","italic","underline","strikethrough"],
        ["separator","forecolor","hilitecolor","textindicator"],
        ["separator","subscript","superscript"],
        ["linebreak","separator","justifyleft","justifycenter","justifyright","justifyfull"],
        ["separator","insertorderedlist","insertunorderedlist","outdent","indent"],
        ["separator","inserthorizontalrule","createlink","inserttable"],
        ["separator","undo","redo","selectall"], (HTMLArea.is_gecko ? [] : ["cut","copy","paste","overwrite","saveas"]),
        ["separator","killword","removeformat","toggleborders","lefttoright", "righttoleft","separator","htmlmode","about"]
        ];
    
       xinha_config_'.$outputIndex.'.pageStyle = "@import url('.BASE."application.css".');";
       ';
      }

      /** STEP 4 ***************************************************************
       * We first create editors for the textareas.
       *
       * You can do this in two ways, either
       *
       *   xinha_editors   = HTMLArea.makeEditors(xinha_editors, xinha_config, xinha_plugins);
       *
       * if you want all the editor objects to use the same set of plugins, OR;
       *
       *   xinha_editors = HTMLArea.makeEditors(xinha_editors, xinha_config);
       *   xinha_editors['myTextArea'].registerPlugins(['Stylist','FullScreen']);
       *   xinha_editors['anotherOne'].registerPlugins(['CSS','SuperClean']);
       *
       * if you want to use a different set of plugins for one or more of the
       * editors.
       ************************************************************************/

       echo '
       xinha_editors_'.$outputIndex.'   = HTMLArea.makeEditors(xinha_editors_'.$outputIndex.',
          xinha_config_0, xinha_plugins_0);';

      /** STEP 5 ***************************************************************
       * If you want to change the configuration variables of any of the
       * editors,  this is the place to do that, for example you might want to
       * change the width and height of one of the editors, like this...
       *
       *   xinha_editors.myTextArea.config.width  = '640px';
       *   xinha_editors.myTextArea.config.height = '480px';
       *
       ************************************************************************/


      /** STEP 6 ***************************************************************
       * Finally we "start" the editors, this turns the textareas into
       * Xinha editors.
       ************************************************************************/
       echo '
      HTMLArea.startEditors(xinha_editors_'.$outputIndex.');
    }';

    if($outputIndex != 0)
    {
      echo '
      var old_on_load_'.$outputIndex.' = window.onload;
      window.onload = function() {
      if (typeof old_on_load_'.$outputIndex.' == "function") old_on_load_'.$outputIndex.'();
        xinha_init_'.$outputIndex.'();
      }';
    } else
    {
        echo '
    window.onload = xinha_init_'.$outputIndex.';';
    }

    echo '    
    </SCRIPT>
      ';

    $outputIndex++; /* increment the output index */
}

/**
 * Remove html formatting from description and extract the first part of the description only.
 * This is to be used for search results, application summary tables, etc.
 */ 
function util_trim_description($sDescription)
{
    // 1) let's take the first line of the description:
    $aDesc = explode("\n",trim($sDescription),2);
    // 2) maybe it's an html description and lines are separated with <br> or </p><p>
    $aDesc = explode("<br>",$aDesc[0],2);
    $aDesc = explode("<br />",$aDesc[0],2);
    $aDesc = explode("</p><p>",$aDesc[0],2);
    $aDesc = explode("</p><p /><p>",$aDesc[0],2);
    return trim(strip_tags($aDesc[0]));
}

?>
