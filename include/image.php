<?php
/*************************************/
/* image and image_resource classes  */
/*************************************/

/**
 * Image class for handling screenshot and thumbnail image files.
 */
class Image {
    var $file; // absolute path from the docroot
    var $debug_log;
    var $image;
    var $width;
    var $height;
    var $type;
    
    /**
     * Constructor:
     * $file is the full path to the image. $this->is_loaded()
     * should really be checked after making a new object.
     */
    function Image($sRelativePath)
    {
        $this->file = $_SERVER['DOCUMENT_ROOT'].$sRelativePath;
       
        $info = @getimagesize($this->file);       
        
        if( empty($info) )
        {
            $this->set_debuglog("Failed to load file ".$this->file);
            return;
        }
        
        switch( $info[2] )
        {
            case 2:
                $data = imagecreatefromjpeg($this->file);
            break;
        
            case 3:
                $data = imagecreatefrompng($this->file);
            break;
        
            default;
                $this->set_debuglog("Image type ({$info[2]}) unkown");
                return;
            return;
        }
        
        $this->image = $data;
        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info[2];
        
        $this->set_debuglog("New image class created with as $file as"
                          ." file and {$info[2]} as type. Dimensions"
                          ." {$info[0]}x{$info[1]}");
    }
    
    /**
     * is_loaded()
     * This function should always be checked after loading a file
     * with the constructor. Rteturns true if the image has been
     * succesfully loaded.
     */ 
    function is_loaded()
    {
        if($this->width > 0 AND $this->height > 0)
            return true;
        else
            return false;
    }
    
    /**
     * Returns the latest debug log made for the last function. If $full is
     * set it will return the full log as array of the object.
     */
    function get_debuglog($full = 0)
    {
        if($full)
            return $this->debug_log;
        else
            return end($this->debug_log);
    }
    
    function get_width()
    {
        return $this->width;
    }
    
    function get_height()
    {
        return $this->height;
    }
    
    /**
     * Returns the image resource identifier.
     */
    function get_image_resource()
    {
        return $this->image;
    }
    
    /**
     * make_thumb()
     *
     * Calculates resize based on one parameter and calculates the other
     * with the right aspect ratio. If you want to use $new_height set
     * $new_width to 0.
     *
     * If none are set APPDB_THUMBNAIL_WIDTH is used. If both are set
     * $new_width is used.
     *
     * If you want to make a border, look at resize_image_border() comment
     * and set $border_width and $border_color as appropriate.
     *
     */
    function make_thumb($new_width, $new_height, $border_width = 0, $border_color = '')
    {
        
        if($new_width == 0 AND $new_height == 0)
        {
            $new_width = APPDB_THUMBNAIL_WIDTH;
            $new_height = $this->calculate_proportions($this->width, $this->height,$new_width);
        }
        else if($new_width > 0)
        {
            $new_height = $this->calculate_proportions($this->width, $this->height,$new_width);
        }
        else if($new_height > 0)
        {
            $new_width = $this->calculate_proportions($this->width, $this->height, 0, $new_height);
        }
                    
        $this->set_debuglog("Resizing image to $new_width x $new_height");
        
        if(!empty($border_color) and $border_width > 0)
            $this->resize_image_border($border_color,$border_width,$new_height,$new_width);
        else
            $this->resize_image($new_width,$new_height);
    }
    
    /**
     * make_full() 
     *
     * Function will make sure your image is as big or smaller than the sizes
     * set here with $max_width and $max_height. Aspect ratio will be mantained.
     *
     * If none are set APPDB_SCREENSHOT_MAXWIDTH and APPDB_SCREENSHOT_MAXHEIGHT
     * are used.
     */    
    function make_full($max_width = 0, $max_height = 0)
    {
        if(!$max_width > 0)
            $max_width = APPDB_SCREENSHOT_MAXWIDTH;
        
        if(!$max_height > 0)
            $max_height = APPDB_SCREENSHOT_MAXHEIGHT;
            
        if($this->width > $max_width)
        {
            /* The width is too much */
            $new_width = $max_width;
            $new_height = $this->calculate_proportions($this->width,$this->height,$new_width);
            
            /* Check if the height is also within the limits */
            if($new_height > $max_height )
            {
                $new_width = $this->calculate_proportions($new_width,$new_height,0,$max_height);
                $new_height = $max_height;
            }
        }
        else if($this->height > $max_height)
        {
            /* Width was ok, height not */
            $new_width = $this->calculate_proportions($this->width,$this->height,0,$max_height);
            $new_height = $max_height;
        }
        else
        {
            /* All ok */
            $new_width = $this->width;
            $new_height = $this->height;
        }
        
        $this->set_debuglog("Resizing image to $new_width x $new_height");
        
        $this->resize_image($new_width, $new_height);
    }
    
    /**
     * resize_image()
     *
     * Resizes the image with the width and height specified with
     * $new_height and $new_width.
     */
    function resize_image($new_width, $new_height)
    {
        // GD 2.x
        // $new = imagecreatetruecolor($new_width, $new_height);
        // GD 1.x
        $new = imagecreate($new_width, $new_height);
        
        // GD 2.x
        // imagecopyresampled($new,$this->image,0,0,0,0,$new_width,$new_height,$this->width,$this->height);
        // GD 1.x
        imagecopyresized($new,$this->image,0,0,0,0,$new_width,$new_height,$this->width,$this->height);
        
        $this->set_debuglog("imagecopyresized($new,$this->image,0,0,0,0,$new_width,$new_height,$this->width,$this->height);");     
        imagedestroy($this->image);
        $this->image = $new;
        $this->width = $new_witdh;
        $this->height= $new_height;
    }
    
    /**
     * resize_image_border()
     *
     * Resizes the image. With the $new_width + $border_width*2
     * and $new_height + $border_width*2 as size. $border_color is a
     * HTML hexadecimal color (like #0000FF)
     */
    function resize_image_border($border_color, $border_width, $new_height, $new_width)
    {
        
        $r = hexdec(substr($border_color, 1, 2));
        $g = hexdec(substr($border_color, 3, 2));
        $b = hexdec(substr($border_color, 5, 2));
        
        /* We multiply the border width by two because there are are borders
        at both sides */
        // GD 2.x
        // $new = imagecreatetruecolor($new_width + ($border_width*2), $new_height + ($border_width*2));
        // GD 1.x
        $new = imagecreate($new_width + ($border_width*2), $new_height + ($border_width*2));
        
        /* Make the border by filling it completely,
        later on we will overwrite everything except the border */
        $color = ImageColorAllocate( $new, $r, $g, $b );
        imagefill($new,0,0,$color);
        
        // GD 2.x
        // imagecopyresampled($new,$this->image,$border_width,$border_width,0,0, $new_width,$new_height,$this->width,$this->height);
        // GD 1.x
        imagecopyresized($new,$this->image,$border_width,$border_width,0,0,$new_width,$new_height,$this->width,$this->height);
        
        $this->set_debuglog("imagecopyresized($new,$this->image,$border_width,$border_width,0,0,"
                           ." $new_width,$new_height,$this->width,$this->height); with a $border_width px border"
                           ." in $border_color");
        imagedestroy($this->image);
        $this->image = $new;
        $this->width = $new_witdh;
        $this->height= $new_height;
    }
    
    /**
     * add_watermark()
     *
     * $watermark is a image resource identifier to any image resource.
     *
     * $min_mark_wwidth and $min_mark_height are the minimum sizes of the
     * destination image before the watermark is added. If none are set
     * both will be 0.
     *
     * A warning for transparency. If you resize an image down with make_thumb()
     * you loose the transparency on png images.
     */
    function add_watermark($watermark,$min_mark_width = 0,$min_mark_height = 0)
    {
        
        $watermark_width = imagesx($watermark);
        $watermark_height = imagesy($watermark);
          
        if($this->width > $min_mark_width AND $this->height > $min_mark_height)
        {
            $watermark_x = $this->width - $watermark_width;
            $watermark_y = $this->height - $watermark_height;
            
            imagecopy($this->image, $watermark, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height); 
            
            $this->set_debuglog("imagecopy($this->image, $watermark, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height);");
        }
    }
    
    /**
     * Output the image to a file set with $store.
     *
     * $type is optional and is set like the second index of getimagesize().
     * If none (or 0) is set the orginal type of the file is used.
     * If $store is not give, the current file name will be used.
     * $quality is the jpeg output quality (100 best, 0 worst). Default is 75
     */
    function output_to_file($store=null, $type = 0, $quality = 75)
    {
        if(!$store)
            $store = $this->file;
        if($type == 0)
            $type = $this->type;
        
        switch($type)
        {
            case 2:
            imagejpeg($this->image,$store,$quality);
            $this->set_debuglog("Outputed file as jpeg to $store");
            break;
            
            case 3:
            imagepng($this->image,$store);
            $this->set_debuglog("Outputed file as png to $store");
            break;
            
            default:
            $this->set_debuglog("Unkown output type");
            return;
        }
        
        return true;
    }
    
    /**
     * Output the files to the browser.
     *
     * If $header is true a Content-type header with the correct type
     * is set.
     * $type is optional and is set like the second index of getimagesize().
     * If none (or 0) is set the orginal type of the file is used.
     *
     * $quality is the jpeg output quality (100 best, 0 worst)
     */  
    function output_to_browser($header, $type = 0, $quality = 75)
    {
        if($type == 0 )
            $type = $this->type;
        
        switch($type)
        {
            case 2:
            if($header)
                header('Content-type: image/jpeg');
            imagejpeg($this->image,'',$quality);
            $this->set_debuglog("Outputed file as jpeg to browser");
            break;
            
            case 3:
            if($header)
                header('Content-type: image/png');
            imagepng($this->image);            
            $this->set_debuglog("Outputed file as png to browser");
            break;
            
            default:
            $this->set_debuglog("Unkown output type");
        }
    }
    
    /**
     * Destroys the image resource. Be sure to do this at the end of your
     * actions with this object.
     */
    function destroy()
    {
        if(is_resource($this->image))
            imagedestroy($this->image);
    }

    /**    
     * Deletes the screenshot from the file system.
     */
    function delete()
    {
        unlink($this->file);
    }

    
    /***********************
    * PRIVATE FUNCTIONS
    ************************/
    
    function set_debuglog( $log ) {
        $this->debug_log[] = $log;
    }
    
    function calculate_proportions($width, $height, $new_width, $new_height = '0')
    {
        if($new_width > 0)
            // we want to calculate the new height
            return ($height * $new_width) / $width;
        else if( $new_height > 0 )
            return ($width * $new_height) / $height;
        else
            return 0;
    }

}


class ImageResource extends Image {
    
    function ImageResource($data,$type){
        
        $this->image = $data;
        $this->width = imagesx($data);
        $this->height = imagesy($data);
        $this->type = $type;
        
        $this->set_debuglog("New image class created with as $data as"
                          ." image resource and $type as type. Dimensions"
                          ." {$this->width}x{$this->height}");
    }
}
?>
