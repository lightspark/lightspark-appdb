<?php
/**********************************************/
/* Banner Ad Library                          */
/* by Jeremy Newman <jnewman@codeweavers.com> */
/* last modified: 2001.10.08                  */
/**********************************************/

/**
 * Path for Banner Ads
 */
function banner_display ()
{
    // import banner paths from config
    global $apidb_root;
    $banner_path_468x60 = $apidb_root."banner/468x60/";
    $banner_path_xml = $apidb_root."banner/xml/";

    // opening html
    $banner = "";
    $banner .= "\n\n".'<!-- START BANNER AD -->'."\n";
    $banner .= "<div align=center class=\"black\">\n";

    // read dir and get list of banners
    $ads = array();
    $d = opendir($banner_path_468x60);
    while($entry = readdir($d))
    {
        if(!ereg("(.+)\\.gif$", $entry, $arr))
            continue; //"
            array_push($ads, $arr[1]);
    }
    closedir($d);

    // randomly select a banner and display it
    $img = $ads[(rand(1,count($ads))-1)];
    $url = get_xml_tag($banner_path_xml.$img.'.xml','url');
    $alt = get_xml_tag($banner_path_xml.$img.'.xml','alt');

    // da banner
    $banner .= '<a href="'.$url.'">';	
    $banner .= '<img src="'.$banner_path_468x60.$img.'.gif" width=468 height=60 alt="'.$alt.'">';
    $banner .= '</a>'."\n";
    
    // closing html
    $banner .= '</div>'."\n";
    $banner .= '<!-- END BANNER AD -->'."\n\n";

    return $banner;
}

?>
