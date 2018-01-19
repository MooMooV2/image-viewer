<?php
function is_img($string) {
	return preg_match('/\.(gif|jpe?g|png)$/i', $string);
}

function get_images($path,$order) {
	$err1 = 'Error reading images, propably invalid path?';
	if($order == "DESC") {
		$file_list = scandir($path, SCANDIR_SORT_DESCENDING) or die($err1);
	}
	else {
		$file_list = scandir($path, SCANDIR_SORT_ASCENDING) or die($err1);
	}
	return $file_list;
}

function xhr_response($file_list,$request) {
	$err1 = '{"error":"Ivalid request"}';

	//Total count of images
	$file_qty = count($file_list);

	//Requested count of images
	$req_qty = (int)$request["getImgs"];
	if($req_qty <= 0) return $err1;

	//Offset to skip already requested images from beginning
	$offset = isset($request["index"]) ? (int)$request["index"] : -1;
	if($offset < 0) return $err1;

	$response = [];

	//Loop through the files, and add requested count of images to the response
	for(; $offset < $file_qty && $req_qty > 0; $offset++) {
		$file = $file_list[$offset];

		//Check that the file is an image
		if(is_img($file)) {
			$response[] = $file;
			$req_qty--;
		}

	}

	if(!$response) {
		return null;
	}
	return array(
		"thumbs" => $response,
		"index" => $offset
	);

}

?>
