<?php
require_once(BASE."include/util.php");

/********************/
/* New Account Form */
/********************/

echo "<div class='default_container'>\n";
echo '<form method="post" action="account.php">',"\n";
echo html_frame_start("Create New Application DB Account","400","",0)
?>

<!-- start of new account form -->
The password will be sent to your e-mail
<table border=0 width="100%" cellspacing=0 cellpadding=20>
    <tr>
        <td class=color1> E-mail </td>
        <td class=color0> <input type="text" name="sUserEmail" value='<?php if(!empty($aClean['sUserEmail'])) echo $aClean['sUserEmail']?>'> </td>
    </tr>
    <tr>
        <td class=color1> Password </td>
        <td class=color0> <input type="password" name="sPassword" value='<?php if(!empty($aClean['sPassword'])) echo $aClean['sPassword']?>'> </td>
    </tr>
    <tr>
        <td class=color1> Repeat Password </td>
        <td class=color0> <input type="password" name="sPasswordRepeat" value='<?php if(!empty($aClean['sPasswordRepeat'])) echo $aClean['sPasswordRepeat']?>'> </td>
    </tr>
    <tr>
        <td class=color1> Real Name </td>
        <td class=color0> <input type="text" name="sUserRealname" value='<?php if(!empty($aClean['sUserRealname'])) echo $aClean['sUserRealname']?>'> </td>
    </tr>
<?php

        echo "<tr><td class=color1>&nbsp; Lightspark version </td><td class=color0>";
        echo make_bugzilla_version_list("sLightsparkRelease", isset($aClean['sLightsparkRelease']) ? $aClean['sLightsparkRelease'] : '');
        echo "</td></tr>";

?>
      
    <tr>
        <td colspan=2 align=center class=color3>
        <input type="submit" name="sCreate" value=" Create Account " class=button>
        </td>
    </tr>    
</table>
<!-- end of new account form -->

<?php

echo html_frame_end("&nbsp;");
echo '<input type="hidden" name="sCmd" value="do_new">',"\n";
echo '</form>',"\n";
echo "</div>\n";

echo p(),p(),p();

?>
