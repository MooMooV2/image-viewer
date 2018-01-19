<?php
$settings = array(
	"title" => "",               //Page title, name of current working directory is used if empty
	"image_path" => "./",        //Path to the directory that contains full size images
	"thumb_path" => "./thumbs/", //Path to the directory that contains thumbnails
	"thumb_height" => 280,       //Thumbnail height in pixels
	"thumb_width" => "auto",     //Thumbnail width in pixels or "auto" to keep aspect ratio
	"image_order" => "ASC",      //Display order: ASC = a to z, DESC = z to a
	"page_base" => 30,           //Count of images shown per one page, for the fallback (noscript) version
	"batch_size" => 15,          //How many thumbnails are requested at a time
	"load_offset" => 500         //How many pixels prior the page bottom are new thumbnails loaded (must be greater than zero
);
?>
