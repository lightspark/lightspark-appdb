<?php
require_once(BASE."include/util.php");

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
<tr>
    <td colspan=2>&nbsp;</td>
</tr>

<!-- end of edit account form -->
