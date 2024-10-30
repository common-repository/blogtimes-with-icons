<?php
/*
Plugin Name: Blogtimes with Icons
Plugin URI: http://www.mutube.com/projects/wordpress/blogtimes-with-icons?utm_source=plugin&utm_medium=admin
Description: This plugin generates a iconic diagram showing when posts are made during a period of time. For this to work <code>wp-images/blogtimes.png</code> must be writable by the web server.
Author: Matt Mullenweg & Martin Fitzpatrick
Author URI: http://www.mutube.com?utm_source=plugin&utm_medium=admin
Version: 0.9
Release: 5th Feb 2005
*/

/*
Original Plugin Name: Blogtimes
Original Plugin URI: http://dev.wp-plugins.org/wiki/BlogTimes
Original Description: This plugin generates a bar graph image showing when posts are made during a period of time. For this to work <code>wp-images/blogtimes.png</code> must be writable by the web server. Original code by Sanjay Sheth of sastools.com.
Original Author: Matt Mullenweg
Original Author URI: http://photomatt.net/
Original Version: 0.2.1
*/ 

/*  Copyright 2006  MARTIN FITZPATRICK  (email : martin.fitzpatrick@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Change the defaults to modify anything
function updateBlogTimePNG($dummy = 'placeholder', $saveFile = '', $last_x_days = 30,
	$width = 480, $height = 65, $horzpadding = 5, $vertpadding = 5,
	$show_ticks = 1, $title = "Blog Activity Times",
	/* Begin */ $postim_file = 'wp-images/blogtimes_icon.png' /* End */) {

	if (!$saveFile) $saveFile = ABSPATH . 'wp-images/blogtimes.png';
    // constants defining image
    $fontheight = ImageFontHeight(2);
    $fontwidth  = ImageFontWidth(2);
    $monthtext = "Last $last_x_days days";
    $unitname = "hour of day";
    $show_units = 1;

    // create the basic image
    $im = @ImageCreate ($width, $height)
       or die ('Cannot create a new GD image.');
  
    // generate some colors, format: RED, GREEN, BLUE
    $white      = ImageColorAllocate ($im, 255,255,255);
    $black      = ImageColorAllocate ($im, 0,0,0);
    $beige      = ImageColorAllocate ($im, 238,238,238);
    $blue       = ImageColorAllocate ($im, 102,102,102);
    $silver     = ImageColorAllocate ($im, 0xE0,0xE0,0xE0);

    // define what color to use where
    $back_color = $white;    # this is background of entire image (text & all)
    $box_color  = $beige;    # this is background of just the posts box
    $text_color = $black;
    $line_color = $blue;     # this is color of lines for each post
    $border_color = $black;
/* Begin */
	$tick_color = $black; #definition of tick colours missing from original version.
/* End */
	
    # query the db and build the list
    $posttimes = getPostTimes($last_x_days);

    # calculate how many intervals to show
    $intervals = floor( ($width / 40) );
    if ($intervals >= 24) $i_mod = 1;
    else if ($intervals >= 12) $i_mod = 2;
    else if ($intervals >= 8) $i_mod = 3;
    else if ($intervals >= 6) $i_mod = 4;
    else if ($intervals >= 4) $i_mod = 6;
    else if ($intervals >= 3) $i_mod = 8;
    else if ($intervals >= 2) $i_mod = 16;
    else $i_mod = 24;

    # fill the image with the background color
    ImageFill($im, 0, 0, $back_color);

    # create a filled  rectangle with a solid border
    $left = $horzpadding; $right = $width - $horzpadding;
    $top = $fontheight + $vertpadding;
    $bottom = $height - $vertpadding - $fontheight;

    if ($show_units)
        $bottom -= $fontheight;

    ImageFilledRectangle($im, $left,$top,$right,$bottom, $box_color);
    ImageRectangle($im, $left,$top,$right,$bottom, $border_color);

    # write title and monthtext
    ImageString($im, 2, $left, 0, $title,$text_color);
    $txtwidth = strlen($monthtext) * $fontwidth;
    ImageString($im, 2, $right - $txtwidth, 0,$monthtext,$text_color);

    # add the legend on the bottom
    for ($i = 0; $i <= 23; $i=$i+1)
    {
        if ($i % $i_mod == 0) {
            $curX = $left + ($right - $left)/24 * $i;

            if ($i > 9) {$strX = $curX - 5;}
            else        {$strX = $curX - 2;}

            ImageString($im, 2, $strX , $bottom, $i, $text_color);
            if ($show_ticks)
                ImageLine($im, $curX, $bottom, $curX, $bottom - 5, $tick_color);
        }
    }
    ImageString($im, 2, $right - 5, $bottom,  0, $text_color);
    if ($show_units) {
        $curX = ($right + $left) / 2 - ($fontwidth * strlen($unitname)/2);
        $curY = $bottom + $fontheight + 2;
        ImageString($im, 2, $curX, $curY, $unitname, $text_color);
    }

/* Begin */
	
	# If an image file has been specified, then create an image object from it
	# to show on the graph.
	if($postim_file!='')
	{
		# Load image to write for each post
	    $postim = @ImageCreateFromPNG ( ABSPATH . $postim_file)
	       or die ('Cannot create a new GD image.');
		# Get size information & calculate offsets.  Yoffset is always 0
		# so overlay image should be designed to align icon on graph correctly.
		$postimW=ImagesX($postim);
		$postimH=ImagesY($postim);
		$postimXoffset=floor($postimW/2);
	}
/* End */
	
    # now we draw the lines/icons for each post
    # the post times should be in terms of # of minutes since midnight
    $arrcount = count($posttimes);
    for ($i = 0; $i < $arrcount; $i++)
    {
        # make sure postTime is between 0 and 1439
        $curPostTime = abs($posttimes[$i]) % 1440; 
        
        # calculate the horz pos inside box              
        $curX = $left + ($right - $left)/1440 * $curPostTime;    # 1440 minutes per day

/* Begin */		
		if($postim) # If an image has been defined for this
		{
			# draw the post image
			ImageCopy ( $im,$postim, $curX-$postimXoffset, $postimYoffset, 0, 0, $postimW, $postimH );
		}
		else # No image, draw the line
		{
        	# draw the post line
	        ImageLine($im, $curX, $bottom, $curX, $top, $line_color);
		}
/* End */	 	
    }

    # save the file to disk in PNG format 
    ImagePNG ($im,$saveFile);
}

# This function will query the db for all the posts in last x days
# and build an array of # of minutes since midnight for each post
function getPostTimes( $last_x_days = 30 ) {
	global $wpdb, $tableposts;

    $result = $wpdb->get_results("
		SELECT HOUR(post_date)*60+MINUTE(post_date) AS totmins
		FROM $tableposts 
		WHERE (TO_DAYS(CURRENT_DATE) - TO_DAYS(post_date)) <= $last_x_days 
		AND post_status = 'publish'
		ORDER BY totmins ASC
		");

    foreach ($result as $row) {
      $postTimes[] = $row->totmins;
    }
    
    return $postTimes;
}


add_action('publish_post', 'updateBlogTimePNG');

/* Begin */
# Uncomment following line to refresh on page reload, useful for debuggin - do NOT turn on normally!!
# add_action('wp_head', 'updateBlogTimePNG');
/* End */
?>