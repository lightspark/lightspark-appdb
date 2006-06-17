<?php
require_once(BASE."include/util.php");

$aClean = array(); //array of filtered user input

$aClean['ext_email'] = makeSafe($_POST['ext_email']);
$aClean['ext_realname'] = makeSafe($_POST['realname']);


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
        <td class=color0> <input type="text" name="ext_email" value='<?php if(!empty($aClean['ext_email'])) echo $aClean['ext_email']?>'> </td>
    </tr>
    <tr>
        <td class=color1> Password </td>
        <td class=color0> <input type="password" name="ext_password"> </td>
    </tr>
    <tr>
        <td class=color1> Password (again) </td>
        <td class=color0> <input type="password" name="ext_password2"> </td>
    </tr>
    <tr>
        <td class=color1> Real Name </td>
        <td class=color0> <input type="text" name="ext_realname" value='<?php if(!empty($aClean['ext_realname'])) echo $aClean['ext_realname']?>'> </td>
    </tr>
<?php

        echo "<tr><td class=color1>&nbsp; Wine version </td><td class=color0>";
        make_bugzilla_version_list("CVSrelease", $CVSrelease);
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
echo '<input type="hidden" name="cmd" value="do_new">',"\n";
echo '</form>',"\n";

echo p(),p(),p();

?>
