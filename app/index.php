<?php

//Relative path to application files
$app_dir = "./app/";

//Import stuff
include $app_dir."settings.php";
include $app_dir."libgalleryxhr.php";

//Get names of all files in the full size image directory
$file_list = get_images($settings["image_path"],$settings["image_order"]);

//Response to AJAX request to load more images
if(isset($_GET["getImgs"])) {
	$response = xhr_response($file_list,$_GET);
	if(!$response) {
		http_response_code(204);
		exit();
	}
	exit(json_encode($response));
}

//If there is no paramameters in the request, send an empty page
//Check that thumbnails are up to date
else {
	include $app_dir."libgallery.php";
	check_for_new_images($file_list, $settings);
	exit(print_gallery($settings));
}
?>
