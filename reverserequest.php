<?php
	// $headers = apache_request_headers();

	// foreach ($headers as $header => $value) {
	//     echo "$header: $value <br />\n";
	// }

	$data = file_get_contents('php://input');
    echo $data;
?>