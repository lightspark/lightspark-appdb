<?

    /*
     * add previous/next buttons
     */
function add_pn_buttons($vars, $endpos)
{
	extract($vars);

	if($linesPerPage == "ALL")
	{
		return;
	}

	$curPage = $curPos / $linesPerPage;
        $numRows = $endpos - $curPos;
	$numButtons = $totalCount / $linesPerPage;
	$buttonCount = 1;

	$prev_url = 0;
	$next_url = 0;

	// define previous/next buttons
	if($curPos > 0)
	{
		$vars["curPos"] = $curPos - $linesPerPage;
		$prev_url = "stdquery.php?".build_urlarg($vars);
	}

	if($endpos < $totalCount)
	{
		$vars["curPos"] = $curPos + $linesPerPage;
		$next_url = "stdquery.php?".build_urlarg($vars);
	}
	
	// show prev button if nessessary
	if($prev_url)
	{
		echo html_b(html_ahref("&lt;&lt; Prev", $prev_url));
	}

	// show numbered links
	if(!$useNextOnly && $endpos <= $totalCount)
	{
		while($buttonCount <= $numButtons + 1)
		{
			if($curPage == ($buttonCount - 1))
			{
				echo html_b("$buttonCount");
                        }
			else
			{
				$vars["curPos"] = ($buttonCount - 1) * $linesPerPage;
				$url = "stdquery.php?".build_urlarg($vars);
				echo " ".html_ahref("$buttonCount", $url)." ";
			}

			if(!($buttonCount % 40))
			{
				echo html_p();
			}
			$buttonCount++;
		}
	    
        }
	
	// show next button if nessessary
	if($next_url)
	{
		echo html_b(html_ahref("Next &gt;&gt;", $next_url));
	}
	
	echo "<br>".html_small("listing $numRows record".($numRows == 1 ? "" : "s")." ".($curPos+1)." to $endpos of $totalCount total");
}

?>
