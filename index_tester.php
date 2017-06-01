<?php

    ini_set('display_errors',1);
    error_reporting(E_ALL);

    $url = "https://my.bizassure.com/appulate/blah.php";

    $data = array("xml" => "<?xml version='1.0'?><stuff><child>foo</child><child>bar</child></stuff>",
        "name" => "Ross",
        'php_master' => true
    );

    # You can POST a file by prefixing with an @ (for <input type="file"> fields)
    # $data['file'] = '@/home/user/world.jpg';

    $ch_options = array(
        CURLOPT_HEADER => TRUE,
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => TRUE
    );

    # set CURLOPT_URL
    $ch = curl_init($url);

    # apply curl options
    curl_setopt_array($ch, $ch_options);

    # execute
    # curl_exec($ch);
    $result = curl_exec($ch);

    # watch for 404 ...
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpCode == 404) {

        echo "Oops! 404 Error" . "<br/><br/>";

    } else {

        echo $result . "<br/><br/>";

    }

    # close curl
    curl_close($ch);

    # -------------------------------------------------------------------------------- #

    # Note(s):

    #   TEST LINK: https://my.bizassure.com/appulate/index_tester.php

?>