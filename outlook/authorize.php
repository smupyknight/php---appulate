<?php
  session_start();
  require_once('oauth.php');
  require_once('outlook.php');
  require_once('outlook_calendar.php');

  $auth_code = $_GET['code'];
  $redirectUri = 'https://my.bizassure.com/appulate/outlook/authorize.php';

  $tokens = oAuthService::getTokenFromAuthCode($auth_code, $redirectUri);

  if ($tokens['access_token']) {
    $_SESSION['access_token'] = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];

    // expires_in is in seconds
    // Get current timestamp (seconds since Unix Epoch) and
    // add expires_in to get expiration time
    // Subtract 5 minutes to allow for clock differences
    $expiration = time() + $tokens['expires_in'] - 300;
    $_SESSION['token_expires'] = $expiration;

    // Get the user's email
    $user = OutlookService::getUser($tokens['access_token']);
    $_SESSION['user_email'] = $user['EmailAddress'];

    // Redirect back to home page

    header("Location: ./calendar.php");
  }
  else
  {
    echo "<p>ERROR: ".$tokens['error']."</p>";
  }
?>
