<?php

function parsedate($datestr)
{
    $daynames = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
    $monthnames = array("jan" => 1, "feb" => 2, "mar" => 3, "apr" => 4, "may" => 5, "jun" => 6, 
			"jul" => 7, "aug" => 8, "sep" => 9, "oct" => 10, "nov" => 11, "dec" => 12);
    $ampm = array("am" => 00, "pm" => 12);

    if(!$datestr)
	return -1;

    $datestr = strtolower($datestr);
    $datestr = ereg_replace("[,]", "", $datestr);
    $dp = explode(' ', $datestr);
    while(list($idx, $part) = each($dp))
    {
        //echo "PART($part)<br />";

        /* 23:59:59 */
        if(ereg("^([0-9]+):([0-9]+):([0-9]+)$", $part, $arr))
        {
            $hour   = $arr[1];
            $minute = $arr[2];
            $second = $arr[3];
            continue;
        }

        /* 23:59 */
        if(ereg("^([0-9]+):([0-9]+)$", $part, $arr))
        {
            $hour   = $arr[1];
            $minute = $arr[2];
            $second = 0;
            continue;
        }

        /* 2000-12-31 (mysql date format) */
        if(ereg("^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])$", $part, $arr))
        {
            $year  = $arr[1];
            $month = $arr[2];
            $day   = $arr[3];
            continue;
        }
	    
        if(defined($ampm[$part]))
        {
            $hour += $ampm[$part];
            continue;
        }

        if($monthnames[substr($part, 0, 3)])
        {
            $month = $monthnames[substr($part, 0, 3)];
            continue;
        }

        if($part > 1900)
        {
            $year = $part;
            continue;
        }

        if($part > 31)
        {
            $year = 1900 + $part;
            continue;
        }

        if($part >= 1 && $part <= 31)
        {
            $day = $part;
            continue;
        }
	    
        //echo "Unparsed: '$part'<br />\n";

    }

    return mktime($hour, $minute, $second, $month, $day, $year);
}

?>
