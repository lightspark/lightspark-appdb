<?php
class htmlmenu {

    function htmlmenu($name, $form = null)
    {

        if ($form)
            echo "<form action=\"$form\" method=\"post\">\n";

        echo '
        <li class="top"><p>'.$name.'</p></li>
';

    }

    /* add a table row */
    function add($sName, $shUrl = null, $sAlign = "left")
    {
      $oTableRow = new TableRow();

        if($shUrl)
        {
            echo "      <li><p><a href=\"{$shUrl}\">{$sName}</a></p></li>\n";
        }
        else
        {
         echo "      <li><p>{$sName}</a></li>\n";
        }
    }

    function addmisc($sStuff, $sAlign = "left")
    {
        echo "<div align=\"$sAlign\">$sStuff</div>\n";
    }

    function done($form = null)
    {
        echo '
        <li class="bot"></li>
';

        if ($form)
            echo "</form>\n";
    }
}
?>
