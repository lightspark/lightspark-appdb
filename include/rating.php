<?php
/**
 * App Compatibility Rating
 */

function rating_current_for_user($versionId, $system)
{

    if(!loggedin())
	return 0;

    $userId = $_SESSION['current']->userid;

    $result = query_appdb("SELECT score FROM appRating WHERE versionId = $versionId AND system = '$system' AND userId = $userId");
    if(!$result)
        return 0;
    $ob = mysql_fetch_object($result);
    return $ob->score;
}



/*=========================================================================
 *
 * Display the app(-version) rating menu
 *
 */
function rating_menu()
{

    $s = '<img src="'.BASE.'images/s1.gif" alt="s1">';
    $n = '<img src="'.BASE.'images/s0.gif" alt="s0">';
    
    $j = new htmlmenu("Compatibility Rating","updaterating.php");

    $r_win = rating_current_for_user($_REQUEST['versionId'], "windows");
    $r_fake = rating_current_for_user($_REQUEST['versionId'], "fake");

    $wchk = array('checked',' ',' ',' ',' ',' ');
    $fchk = array('checked',' ',' ',' ',' ',' ');

    if($r_win)
    {
	$wchk[0] = ' ';
	$wchk[$r_win] = 'checked';
    }
    
    if($r_fake)
    {
	$fchk[0] = ' ';
	$fchk[$r_fake] = 'checked';
    }
     
    $j->addmisc("<table width='100%' border=0 cellpadding=2 cellspacing=0><tr align=center valign=top>".
                "<td width='50%'><small><img src='images/w1.gif' alt='With Windows'> With Windows</small></td>".
		"<td width='50%'><small><img src='images/w0.gif' alt='Without Windows'> Without Windows</small></td>".
		"</tr></table>");
		
    $j->addmisc("<input type=radio name=score_w value='0' ".$wchk[0].">".$n.$n.$n.$n.$n."<input type=radio name=score_f value='0' ".$fchk[0].">","center");
    $j->addmisc("<input type=radio name=score_w value='1' ".$wchk[1].">".$s.$n.$n.$n.$n."<input type=radio name=score_f value='1' ".$fchk[1].">","center");
    $j->addmisc("<input type=radio name=score_w value='2' ".$wchk[2].">".$s.$s.$n.$n.$n."<input type=radio name=score_f value='2' ".$fchk[2].">","center");
    $j->addmisc("<input type=radio name=score_w value='3' ".$wchk[3].">".$s.$s.$s.$n.$n."<input type=radio name=score_f value='3' ".$fchk[3].">","center");
    $j->addmisc("<input type=radio name=score_w value='4' ".$wchk[4].">".$s.$s.$s.$s.$n."<input type=radio name=score_f value='4' ".$fchk[4].">","center");
    $j->addmisc("<input type=radio name=score_w value='5' ".$wchk[5].">".$s.$s.$s.$s.$s."<input type=radio name=score_f value='5' ".$fchk[5].">","center");

    
    $j->addmisc("<input type=submit value='  Rate it!  ' class=ratebutton>","center");
    $j->addmisc("<input type=hidden name=versionId value=".$_REQUEST['versionId'].">");
    
    $j->add("Rating Help", BASE."help/?topic=ratings");
    
    $j->done(1);
}


/*=========================================================================
 *
 * returns the avg rating for versionId
 *
 */
function rating_for_version($versionId, $system)
{
    $result = query_appdb("SELECT avg(score) as rating, count(id) as hits FROM appRating ".
                          "WHERE versionId = $versionId and system = '$system'");
    if(!$result)
	   return 0;
    $ob = mysql_fetch_object($result);
    return $ob;
}


/*=========================================================================
 *
 * returns rating as star images
 *
 */
function rating_stars_for_version($versionId, $system)
{

    $r = rating_for_version($versionId, $system);

    $s = '<img src="'.BASE.'images/s1.gif" alt="s1">';
    $n = '<img src="'.BASE.'images/s0.gif" alt="s0">';
    $h = '<img src="'.BASE.'images/s2.gif" alt="s2">';

    if ($system == "fake")
    {
    	$win_gif = "w0.gif";
	$alt_desc = "Without Windows";
    }
    else
    {
        $win_gif = "w1.gif";
	$alt_desc = "With Windows";
    }

    if(!$r->rating)
        {
	    $str = "";
	    for($c = 0; $c < 5; $c++) { $str .= $n; }
	    $str = "<img src='images/$win_gif' alt='$alt_desc'> ".$str." <br><small class=rating>"."unrated"."</small>";
	    return $str;
	}

    $result = "";
    for($i = 0; $i < (int)floor($r->rating); $i++)
	$result .= $s;
    if(floor($r->rating) < round($r->rating))
	{
	    $i++;
	    $result .= $h;
	}
    for(; $i < 5; $i++)
	$result .= $n;

    $result = "<img src='images/$win_gif' alt='$alt_desc'> ".$result.
              " <br><small class=rating>".substr($r->rating,0,4).
	      " (".$r->hits." votes) "."</small>";

    return $result;
}

/*=========================================================================
 *
 * called by /updaterating.php to update the rating table
 *
 */
function rating_update($vars)
{

    if(!loggedin())
	{
	    // do something, must be logged in
	    return;
	}

    $userId = $_SESSION['current']->userid;
    
    if(is_numeric($vars['versionId']))
        $versionId = $vars["versionId"];
    else 
        return;
    
    if(is_numeric($vars['score_w']))
        $score_w = $vars["score_w"];
    else 
        return;
    
    if(is_numeric($vars['score_f']))
        $score_f = $vars["score_f"];
    else 
        return;

    if($score_w)
    {
        $result = query_appdb("SELECT * FROM appRating WHERE versionId = $versionId AND ".
                              "userId = $userId AND system = 'windows'");
        
        if($result && mysql_num_rows($result))
        {
            $ob = mysql_fetch_object($result);
            query_appdb("UPDATE appRating SET score = $score_w WHERE id = $ob->id");
        }
        else
        {
            $aInsert = compile_insert_string( array( 'versionId' => $versionId,
                                                     'userId' => $userId,
                                                     'system' => 'windows',
                                                     'score' => $score_w));
            
            query_appdb("INSERT INTO appRating ({$aInsert['FIELDS']}) VALUES ({$aInsert['VALUES']})");
        }

        $r = rating_for_version($versionId, "windows");
        query_appdb("UPDATE appVersion SET rating_windows = $r->rating WHERE versionId = $versionId");
    }

    if($score_f)
    {
        $result = query_appdb("SELECT * FROM appRating WHERE versionId = $versionId AND ".
                              "userId = $userId AND system = 'fake'");
        if($result && mysql_num_rows($result))
        {
            $ob = mysql_fetch_object($result);
            query_appdb("UPDATE appRating SET score = $score_f WHERE id = $ob->id");
        }
        else
        {
            $aInsert = compile_insert_string( array( 'versionId' => $versionId,
                                                     'userId' => $userId,
                                                     'system' => 'fake',
                                                     'score' => $score_f));
            query_appdb("INSERT INTO appRating ({$aInsert['FIELDS']}) VALUES ({$aInsert['VALUES']})");
        }

        $r = rating_for_version($versionId, "fake");
        query_appdb("UPDATE appVersion SET rating_fake = $r->rating WHERE versionId = $versionId");
    }
}

?>
