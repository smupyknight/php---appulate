<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');

  $subject = $_POST['subject'];
  $location = $_POST['location'];
  $startTime = date_create_from_format("m/d/Y g:i A", $_POST['start_time']);
  $endTime = date_create_from_format("m/d/Y g:i A", $_POST['end_time']);
  $attendee = $_POST['attendee'];

  $eventId = OutlookCalendarService::createEvent($_SESSION['access_token'], $_SESSION['user_email'], $subject, $location, $startTime, $endTime, $attendee);

  header("Location: ./calendar.php");
 ?>
