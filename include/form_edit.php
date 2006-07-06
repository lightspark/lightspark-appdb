<?php
require_once(BASE."include/util.php");

$aClean = array(); //array of filtered user input

$aClean['userId'] = makeSafe($_REQUEST['userId']);
/*********************/
/* Edit Account Form */
/*********************/
?>

<!-- start of edit account form -->
<tr>
    <td> &nbsp; Email Address </td>
    <td> <input type="text" name="sUserEmail" value="<?php echo $sUserEmail; ?>"> </td>
</tr>
<tr>
    <td> &nbsp; Password </td>
    <td> <input type="password" name="sUserPassword"> </td>
</tr>
<tr>
    <td> &nbsp; Password (again) </td>
    <td> <input type="password" name="sUserPassword2"> </td>
</tr>
<tr>
    <td> &nbsp; Real Name </td>
    <td> <input type="text" name="sUserRealname" value="<?php echo $sUserRealname; ?>"> </td>
</tr>
<?php
// if we manage another user we can give him administrator rights
if($oUser->iUserId == $aClean['userId'])
{
?>
<tr>
    <td> &nbsp; Administrator </td>
    <td> <input type="checkbox" name="sHasAdmin" "<?php echo $sHasAdmin; ?>" value="on"> </td>
</tr>
<?php
}
?>
<tr>
    <td colspan=2>&nbsp;</td>
</tr>

<!-- end of edit account form -->
