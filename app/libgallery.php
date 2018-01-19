<?php
function print_gallery($s) {
	global $app_dir;
	$title = $s["title"];
	if(!$title) {
		$title = ucfirst(preg_replace('/^.*\//', "", getcwd()));
	}
	$o = '<!DOCTYPE html>';
	$o .= '<html>';
	$o .= '<head>';
	$o .=   '<meta charset="UTF-8">';
	$o .=   "<title>{$title}</title>";
	$o .=   '<meta name="theme-color" content="#222222">';
	$o .=   "<link rel=\"stylesheet\" href=\"{$app_dir}style.css\">";
	$o .= '</head>';

	$o .= '<body onload="init();">';
	$o .= '<header id="header">';
	$o .=   "<span>{$title}</span>";
	$o .=   '<a id="copyright" href="https://moomoo.fi/image_viewer/">MooMoo\'s image viewer (GNU GPL v2.0)</a>';
	$o .= '</header>';

	$o .= '<main id="imgContainer">';
	$o .= print_gallery_fallback($s);
	$o .= '</main>';

	$o .= '<div id="fullContainer" unselectable="on">';
	$o .=   '<img id="fullImg" title="Click to close" src="" alt=" ">';
	$o .=   '<div id="buttonContainer">';
	$o .=     '<div class="button" id="back" title="Previous">';
	$o .=       "<img class=\"buttonIcon\" src=\"{$app_dir}back.png\">";
	$o .=     '</div>';
	$o .=     '<div class="button" id="close" title="Close">';
	$o .=       "<img class=\"buttonIcon\" src=\"{$app_dir}close.png\">";
	$o .=     '</div>';
	$o .=     '<div class="button" id="next" title="Next">';
	$o .=       "<img class=\"buttonIcon\" src=\"{$app_dir}next.png\">";
	$o .=     '</div>';
	$o .=   '</div>';
	$o .=   '<div id="loadingContainer">Loading...</div>';
	$o .= '</div>';

	$o .= '<script>';
	$o .=   "var serverSettings = '{\"imagePath\":\"{$s["image_path"]}\",\"thumbPath\":\"{$s["thumb_path"]}\",\"rowHeight\":{$s["thumb_height"]},\"loadOffset\":{$s["load_offset"]},\"batchSize\":{$s["batch_size"]}}';";
	$o .= '</script>';
	$o .= "<script src=\"{$app_dir}script.js\"></script>";
	$o .= '</body>';
	$o .= '</html>';
	return $o;
}

function print_gallery_fallback($s) {

	global $file_list;
	$page_base = $s["page_base"];
	$file_qty = count($file_list);

	//Get page number
	$page_number = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
	if($page_number < 1) $page_number = 1;

	//Calculate values for page buttons
	$page_start = $page_base * ($page_number - 1);
	$page_end = $page_start + $page_base;
	$images_found = 0;

	$o = '<div id="fallback">';

	//Loop through the image files in working directory
	for($i = 0; $i < $file_qty && $images_found < $page_end; $i++) {
		$file = $file_list[$i];

		//The file is an image
		if(is_img($file)) {

			//Exclude only images before the current page, there can also files which are not images!
			if($images_found >= $page_start) {
				$o .= "<a href=\"{$file}\">{$file}</a>";
			}

			$images_found++;
		}
	}

	//Check if there is still more images
	$next_exist = is_img($file_list[$i + 1]);
	$o .= '<div id="pageBackNext">';
	$o .= '<a '.($page_number > 1 ? 'href="./?page='.($page_number - 1) : 'class="dummy').'">Back</a>';
	$o .= "<span>Page: {$page_number}</span>";
	$o .= '<a '.($next_exist ? 'href="./?page='.($page_number + 1) : 'class="dummy').'">Next</a>';
	$o .= '</div>';

	$o .= '</div>';
	return $o;
}

//Check if there are any difference between fingerprint file and current images
//If there is, call generate_thumbs() and create a new fingerprint file
function check_for_new_images($file_list, $sets) {

	$thumb_path = isset($sets['thumb_path']) ? $sets['thumb_path'] : './thumbs/';
	$width = isset($sets['thumb_width']) ? $sets['thumb_width'] : 'auto';
	$height = isset($sets['thumb_height']) ? $sets['thumb_height'] : 270;
	$err1 = 'Error writing the fingerprint file, propably missing permissions?';

	$file_string = $width.$height.implode('', $file_list);
	$fingp_path = $thumb_path.'fingerprint.txt';
	$fingerprint = file_exists($fingp_path) ? file_get_contents($fingp_path) : "";
	$len = strlen($width.$height);
	if(substr($fingerprint, 0, $len) != $width.$height) {
		generate_thumbs($file_list, $sets, true);
		file_put_contents($thumb_path.'fingerprint.txt', $file_string) or exit($err1);
	}
	elseif($fingerprint != $file_string) {
		generate_thumbs($file_list, $sets, false);
		file_put_contents($thumb_path.'fingerprint.txt', $file_string) or exit($err1);
	}
}

//Go through images and generate missing thumbnails or generate all if $force=true
function generate_thumbs($file_list, $sets, $force = false) {

	$image_path = isset($sets['image_path']) ? $sets['image_path'] : './';
	$thumb_path = isset($sets['thumb_path']) ? $sets['thumb_path'] : './thumbs/';
	$width = isset($sets['thumb_width']) ? $sets['thumb_width'] : 'auto';
	$height = isset($sets['thumb_height']) ? $sets['thumb_height'] : 270;
	$err1 = 'Error creating thumbnail directory, propably missing permissions?';
	$err2 = 'Error: No writing permissions in thumbnail directory';
	$err3 = 'Error reading thumbnails, propably missing permissions or invalid path?';

	//Create the thumbs directory if it doesn't exist yet
	if(!file_exists($thumb_path)) {
		mkdir($thumb_path) or exit($err1);
	}

	//Check thumbnails directory writing permissions
	if(!is_writeable($thumb_path)) exit($err2);

	//Get names of existing thumbnails into an array
	$thumb_list = scandir($thumb_path, SCANDIR_SORT_ASCENDING) or exit($err3);


	//Loop through images and create missing thumbnails
	foreach($file_list as $img) {

		//Check that the file is an image
		if(is_img($img)) {

			//Create name for a thumbnail and create it if it doesn't exist yet
			$thumb = str_replace(  //Replace some special characters
				array("\"","%","&","'"),
				array("-22-","-25-","-26-","-27-"),
				preg_replace('/\.[a-z]{3,4}$/i', ".jpg", $img)
			);
			if(!in_array($thumb, $thumb_list) || $force) {
				imagejpeg(create_thumb($image_path.$img, $width, $height), $thumb_path.$thumb) or exit($err2);
			}

		}
	}
}

//Create a thumbnail of the given image
function create_thumb($src, $dst_w = "auto", $dst_h = 300) {

	//Get image data
	$src_info = getimagesize($src);

	//Check image type (gif,jpg,png)
	$rotation = 0;
	switch($src_info[2]) {
		case 1:
			$image_create = "imagecreatefromgif";
			break;
		case 2:
			$image_create = "imagecreatefromjpeg";
			$exif = exif_read_data($src,"IFD0"); //Read image rotation from EXIF data
			$orientation = isset($exif["Orientation"]) ? $exif["Orientation"] : 0;
			switch($orientation) {
				case 6:
					$rotation = -90;
					break;
				case 8:
					$rotation = 90;
					break;
				case 3:
					$rotation = 180;
					break;
			}
			break;
		case 3:
			$image_create = "imagecreatefrompng";
			break;
	}

	//Load full size image
	$img_src = $image_create($src);

	//Calculate thumb width and resize image
	if($rotation == 90 || $rotation == -90) {
		$src_width = $src_info[1];
		$src_height = $src_info[0];
	}
	else {
		$src_width = $src_info[0];
		$src_height = $src_info[1];
	}

	//Keep current aspect ratio
	if($dst_w == "auto") {
		$dst_w = ($dst_h / $src_height) * $src_width;
		$img_dst = imagecreatetruecolor($dst_w, $dst_h);
		imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $dst_w, $dst_h, $src_width, $src_height);
		$img_dst = imagerotate($img_dst, $rotation, 0);
	}

	//Crop image to fit new aspect ratio
	else {
		$thumb_ratio = $dst_w / $dst_h;
		$src_ratio = $src_width / $src_height;

		//Full size image aspect ratio wider than thumb
		if($src_ratio >= $thumb_ratio) {
			$scl_w = ($dst_h / $src_height) * $src_width;
			$scl_h = $dst_h;
			$img_dst = imagecreatetruecolor($scl_w, $scl_h);
			imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $scl_w, $scl_h, $src_width, $src_height);
			$img_dst = imagerotate($img_dst, $rotation, 0);
			$offset = ($scl_w - $dst_w) / 2;
			$crop = array("x" => $offset, "y" => 0, "width" => $dst_w, "height" => $dst_h);
			$img_dst = imagecrop($img_dst, $crop);
		}

		//Full size image aspect ratio narrower than thumb
		else {
			$scl_w = $dst_w;
			$scl_h = ($dst_w / $src_width) * $src_height;
			$img_dst = imagecreatetruecolor($scl_w, $scl_h);
			imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $scl_w, $scl_h, $src_width, $src_height);
			$img_dst = imagerotate($img_dst, $rotation, 0);
			$offset = ($scl_h - $dst_h) / 4;
			$crop = array("x" => 0, "y" => $offset, "width" => $dst_w, "height" => $dst_h);
			$img_dst = imagecrop($img_dst, $crop);
		}
	}

	//Return image resource of the thumbnailed image
	return $img_dst;
}
?>
