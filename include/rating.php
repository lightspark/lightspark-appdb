<?

    /* 
     *
     * App Compatibility Rating
     *
     */



/*=========================================================================
 *
 *
 *
 */
function rating_current_for_user($versionId, $system)
{
    global $current;

    if(!loggedin())
	return 0;

    $userId = $current->userid;

    $result = mysql_query("SELECT score FROM appRating WHERE versionId = $versionId AND system = '$system' AND userId = $userId");
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
    global $versionId;
    global $apidb_root;

    $s = '<img src="'.$apidb_root.'images/s1.gif" border=0 alt="s1">';
    $n = '<img src="'.$apidb_root.'images/s0.gif" border=0 alt="s0">';
    
    $j = new htmlmenu("Compatibility Rating","updaterating.php");

    $r_win = rating_current_for_user($versionId, "windows");
    $r_fake = rating_current_for_user($versionId, "fake");

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
    $j->addmisc("<input type=hidden name=versionId value=$versionId>");
    
    $j->add("Rating Help", $apidb_root."help/?topic=ratings");
    
    $j->done(1);
}


/*=========================================================================
 *
 * returns the avg rating for versionId
 *
 */
function rating_for_version($versionId, $system)
{
    $result = mysql_query("SELECT avg(score) as rating, count(id) as hits FROM appRating ".
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
    global $apidb_root;

    $r = rating_for_version($versionId, $system);

    $s = '<img src="'.$apidb_root.'images/s1.gif" border=0 alt="s1">';
    $n = '<img src="'.$apidb_root.'images/s0.gif" border=0 alt="s0">';
    $h = '<img src="'.$apidb_root.'images/s2.gif" border=0 alt="s2">';

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
    global $current;

    if(!loggedin())
	{
	    // do something, must be logged in
	    return;
	}

    $userId = $current->userid;
    $versionId = $vars["versionId"];
    $score_w = $vars["score_w"];
    $score_f = $vars["score_f"];

    if($score_w)
	{
	    $result = mysql_query("SELECT * FROM appRating WHERE versionId = $versionId AND ".
				  "userId = $userId AND system = 'windows'");
	    if($result && mysql_num_rows($result))
		{
		    $ob = mysql_fetch_object($result);
		    mysql_query("UPDATE appRating SET score = $score_w WHERE id = $ob->id");
		}
	    else
		mysql_query("INSERT INTO appRating VALUES (null, null, $versionId, $userId, 'windows', $score_w)");

	    $r = rating_for_version($versionId, "windows");
	    mysql_query("UPDATE appVersion SET rating_windows = $r->rating WHERE versionId = $versionId");
	}

    if($score_f)
        {
            $result = mysql_query("SELECT * FROM appRating WHERE versionId = $versionId AND ".
                                  "userId = $userId AND system = 'fake'");
            if($result && mysql_num_rows($result))
                {
                    $ob = mysql_fetch_object($result);
                    mysql_query("UPDATE appRating SET score = $score_f WHERE id = $ob->id");
                }
            else
                mysql_query("INSERT INTO appRating VALUES (null, null, $versionId, $userId, 'fake', $score_f)");
        
	    $r = rating_for_version($versionId, "fake");
            mysql_query("UPDATE appVersion SET rating_fake = $r->rating WHERE versionId = $versionId");
	}
}

?>
