<?php
$hAppdbLink = null;
$hBugzillaLink = null;

function query_appdb($sQuery,$sComment="")
{
    global $hAppdbLink;

    if(!is_resource($hAppdbLink))
    {
        // The last argument makes sure we are really opening a new connection
        $hAppdbLink = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS,true);
        mysql_select_db(APPS_DB, $hAppdbLink);
    }
    
    $hResult = mysql_query($sQuery, $hAppdbLink);
    if(!$hResult) query_error($sQuery, $sComment);
    return $hResult;
}

/*
 * Wildcard Rules
 * SCALAR  (?) => 'original string quoted'
 * OPAQUE  (&) => 'string from file quoted'
 * MISC    (~) => original string (left 'as-is')
 *
 * NOTE: These rules convienently match those for Pear DB
 *
 * MySQL Prepare Function
 * By: Kage (Alex)
 * KageKonjou@GMail.com
 * http://us3.php.net/manual/en/function.mysql-query.php#53400
 *
 * Modified by CMM 20060622
 *
 * Values are mysql_real_escape_string()'d to prevent against injection attacks
 * See http://php.net/mysql_real_escape_string for more information about why this is the case
 *
 */
function query_parameters()
{
    global $hAppdbLink;

    if(!is_resource($hAppdbLink))
    {
        // The last argument makes sure we are really opening a new connection
        $hAppdbLink = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS,true);
        mysql_select_db(APPS_DB, $hAppdbLink);
    }

    $data = func_get_args();
    $query = $data[0];
    $tokens = split("[&?~]", $query); /* NOTE: no need to escape characters inside of [] in regex */
    $preparedquery = $tokens[0];
    $count = strlen($tokens[0]);

    /* do we have the correct number of tokens to the number of parameters provided? */
    if(count($tokens) != count($data))
        return NULL; /* count mismatch, return NULL */

    for ($i=1; $i < count($tokens); $i++)
    {
        $char = substr($query, $count, 1);
        $count += (strlen($tokens[$i])+1);
        if ($char == "&")
        {
            $fp = @fopen($data[$i], 'r');
            $pdata = "";
            if ($fp)
            {
                while (($buf = fread($fp, 4096)) != false)
                {
                    $pdata .= $buf;
                }
                fclose($fp);
            }
        } else
        {
            $pdata = &$data[$i];
        }
        $preparedquery .= ($char != "~" ? mysql_real_escape_string($pdata) : $pdata);
        $preparedquery .= $tokens[$i];
    }

    return query_appdb($preparedquery);
}

function query_bugzilladb($sQuery,$sComment="")
{
    global $hBugzillaLink;

    if(!is_resource($hBugzillaLink))
    {
        // The last argument makes sure we are really opening a new connection
        $hBugzillaLink = mysql_connect(BUGZILLA_DBHOST, BUGZILLA_DBUSER, BUGZILLA_DBPASS,true);
        if(!$hBugzillaLink) return;
        mysql_select_db(BUGZILLA_DB, $hBugzillaLink);
    }
    
    $hResult = mysql_query($sQuery, $hBugzillaLink);
    if(!$hResult) query_error($sQuery, $sComment);
    return $hResult;
}


function query_error($sQuery, $sComment="")
{
    $sStatusMessage  = "<p><b>Database Error!</b><br />";
    $sStatusMessage .= "Query: ".$sQuery."<br />";
    $sStatusMessage .= $sComment ? $sComment."<br />" : "";
    $sStatusMessage .= mysql_error()."</p>\n";
    addmsg($sStatusMessage, "red");
}

/**
* Expects an array in this form:
* $aFoo['field'] = 'value';
* 
* Returns a string ready to be put in a query like this
* $sQuery = "UPDATE `foo` $sReturn";
* 
* Values are mysql_real_escape_string()'ed.
*/
function compile_update_string($aData)
{
    global $hAppdbLink;

    if(!is_resource($hAppdbLink))
    {
        // The last argument makes sure we are really opening a new connection
        $hAppdbLink = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS,true);
        mysql_select_db(APPS_DB, $hAppdbLink);
    }

    foreach ($aData as $k => $v) 
    {
        $return .= "`$k`='".mysql_real_escape_string($v)."',";
    }
    
    $return = preg_replace( "/,$/" , "" , $return );
    
    return $return;
}
?>
