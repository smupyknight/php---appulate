<?php

  session_start();
  require('oauth.php');
  require('outlook_calendar.php');
  require_once('sessionManager.php');

  $loggedIn = !is_null($_SESSION['access_token']);

  $redirectUri = 'https://my.bizassure.com/appulate/outlook/authorize.php';
?>

<html>
	<head>
		<title>PHP Mail API Tutorial</title>
	</head>
  <body>
    <?php
      if (!$loggedIn) {
    ?>
      <!-- User not logged in, prompt for login -->
      <p>Please <a href="<?php echo oAuthService::getLoginUrl($redirectUri)?>">sign in</a> with your Office 365 or Outlook.com account.</p>
    <?php
      }
      else {
        $events = OutlookService::getEvents(oAuthService::getAccessToken($redirectUri), $_SESSION['user_email']);
        if (SessionManager::checkResponseAndRefreshToken($events, $redirectUri)) {
          // Pick up new access token
          $accessToken = $_SESSION['accessToken'];

          error_log("Retrying get events request");
          $events = OutlookService::getEvents(oAuthService::getAccessToken($redirectUri), $_SESSION['user_email']);
        }
    ?>
      <!-- User is logged in, do something here -->
      <h2>My Events</h2>

      <table>
        <tr>
          <th>Subject</th>
          <th>Start</th>
          <th>End</th>
        </tr>

        <?php foreach($events['value'] as $event) { ?>
          <tr>
            <td><?php echo $event['Subject'] ?></td>
            <td><?php echo $event['Start']['DateTime'] ?></td>
            <td><?php echo $event['End']['DateTime'] ?></td>
          </tr>
        <?php } ?>
      </table>
    <?php
      }
    ?>
  </body>
</html>
