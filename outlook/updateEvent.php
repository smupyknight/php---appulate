<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');

  $subject = "updatedSubject";
  $eventId = $_GET['eventId'];

  OutlookCalendarService::updateEvent($_SESSION['access_token'], $_SESSION['user_email'], $eventId, $subject);

  header("Location: ./calendar.php");
 ?>
