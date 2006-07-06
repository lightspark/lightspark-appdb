<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/distributions.php");

$aClean = array(); //array of filtered user input

$aClean['iDistributionId'] = makeSafe($_REQUEST['iDistributionId']);
$aClean['sSubmit'] = makeSafe($_REQUEST['sSubmit']);

if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient privileges.");


$oDistribution = new distribution($aClean['iDistributionId']);
if($aClean['sSubmit'])
{
    $oDistribution->GetOutputEditorValues();

    if($oDistribution->iDistributionId)
        $oDistribution->update();
    else
    {
       $oDistribution->create();
    } 
  
    util_redirect_and_exit(apidb_fullurl("distributionView.php"));
}
else
{
    if ($oDistribution->iDistributionId)
        apidb_header("Edit Distribution");
    else
        apidb_header("Add Distribution");

    echo '<form name="sQform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";

    $oDistribution->OutputEditor();

    echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
    echo '<input name="sSubmit" type="submit" value="Submit" class="button" >&nbsp',"\n";
    echo '</td></tr>',"\n";

    echo "</form>";
    echo html_frame_end("&nbsp;");
    apidb_footer();
}
?>
