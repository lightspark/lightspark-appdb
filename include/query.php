<?php
$hAppdbLink = null;
$hBugzillaLink = null;

define("MYSQL_DEADLOCK_ERRNO", 1213);

function query_appdb($sQuery,$sComment="")
{
    global $hAppdbLink;

    if(!is_resource($hAppdbLink))
    {
        // The last argument makes sure we are really opening a new connection
        $hAppdbLink = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS,true);
        mysql_select_db(APPS_DB, $hAppdbLink);
    }

    $iRetries = 2;

    /* we need to retry queries that hit transaction deadlocks */
    /* as a deadlock isn't really a failure */
    while($iRetries)
    {
        $hResult = mysql_query($sQuery, $hAppdbLink);
        if(!$hResult)
        {
            /* if this error isn't a deadlock OR if it is a deadlock and we've */
            /* run out of retries, report the error */
            $iErrno = mysql_errno();
            if(($iErrno != MYSQL_DEADLOCK_ERRNO) || (($iErrno == MYSQL_DEADLOCK_ERRNO) && ($iRetries <= 0)))
            {
                query_error($sQuery, $sComment);
                return $hResult;
            }

            $iRetries--;
        } else
        {
            return $hResult;
        }
    }

    return NULL;
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
 * Usage:
 *  $hResult = query_parameters("Select * from mytable where userid = '?'",
 *                            $iUserId);
 *
 * Note:
 *   Ensure that all variables are passed as parameters to query_parameters()
 *   to ensure that sql injection attacks are prevented against
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

    $aData = func_get_args();
    $sQuery = $aData[0];
    $aTokens = split("[&?~]", $sQuery); /* NOTE: no need to escape characters inside of [] in regex */
    $sPreparedquery = $aTokens[0];
    $iCount = strlen($aTokens[0]);

    /* do we have the correct number of tokens to the number of parameters provided? */
    if(count($aTokens) != count($aData))
        return NULL; /* count mismatch, return NULL */

    for ($i=1; $i < count($aTokens); $i++)
    {
        $char = substr($sQuery, $iCount, 1);
        $iCount += (strlen($aTokens[$i])+1);
        if ($char == "&")
        {
            $fp = @fopen($aData[$i], 'r');
            $pdata = "";
            if ($fp)
            {
                while (($sBuf = fread($fp, 4096)) != false)
                {
                    $pdata .= $sBuf;
                }
                fclose($fp);
            }
        } else
        {
            $pdata = &$aData[$i];
        }
        $sPreparedquery .= ($char != "~" ? mysql_real_escape_string($pdata) : $pdata);
        $sPreparedquery .= $aTokens[$i];
    }

    return query_appdb($sPreparedquery);
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
    static $bInQueryError = false;

    // if we are already reporting an error we can't report it again
    // as that indicates that error reporting itself produced an error
    if($bInQueryError)
        return;

    // record that we are inside of this function, we don't want to recurse
    $bInQueryError = true;

    error_log::log_error(ERROR_SQL, "Query: '".$sQuery."' ".
                         "mysql_errno(): '".mysql_errno()."' ".
                         "mysql_error(): '".mysql_error()."' ".
                         "comment: '".$sComment."'");

    $sStatusMessage = "<p><b>An internal error has occurred and has been logged and reported to appdb admins</b></p>";
    addmsg($sStatusMessage);

    $bInQueryError = false; // clear variable upon exit
}

?>
