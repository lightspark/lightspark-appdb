<?php
require_once(BASE."include/util.php");

$aClean = array(); //array of filtered user input

$aClean['sUserEmail'] = makeSafe($_POST['sUserEmail']);
$aClean['sUserRealname'] = makeSafe($_POST['realname']);


/********************/
/* New Account Form */
/********************/

echo '<form method="post" action="account.php">',"\n";
echo html_frame_start("Create New Application DB Account","400","",0)
?>

<!-- start of new account form -->
<table border=0 width="100%" cellspacing=0 cellpadding=20>
    <tr>
        <td class=color1> E-mail </td>
        <td class=color0> <input type="text" name="sUserEmail" value='<?php if(!empty($aClean['sUserEmail'])) echo $aClean['sUserEmail']?>'> </td>
    </tr>
    <tr>
        <td class=color1> Password </td>
        <td class=color0> <input type="password" name="sUserPassword"> </td>
    </tr>
    <tr>
        <td class=color1> Password (again) </td>
        <td class=color0> <input type="password" name="sUserPassword2"> </td>
    </tr>
    <tr>
        <td class=color1> Real Name </td>
        <td class=color0> <input type="text" name="sUserRealname" value='<?php if(!empty($aClean['sUserRealname'])) echo $aClean['sUserRealname']?>'> </td>
    </tr>
<?php

        echo "<tr><td class=color1>&nbsp; Wine version </td><td class=color0>";
        make_bugzilla_version_list("sWineRelease", $aClean['sWineRelease']);
        echo "</td></tr>";

?>
      
    <tr>
        <td colspan=2 align=center class=color3>
        <input type="submit" name="create" value=" Create Account " class=button>
        </td>
    </tr>    
</table>
<!-- end of new account form -->

<?php

echo html_frame_end("&nbsp;");
echo '<input type="hidden" name="sCmd" value="do_new">',"\n";
echo '</form>',"\n";

echo p(),p(),p();

?>
