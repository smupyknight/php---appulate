<?php
  class OutlookCalendarService {
    private static $outlookApiUrl = "https://outlook.office.com/api/v2.0";

    // Get the calendar collection
    public static function getCalendars($access_token, $user_email) {
      $createEventUrl = self::$outlookApiUrl."/me/calendars";

      $response = self::makeApiCall($access_token, $user_email, "GET", $createEventUrl);

      // If the call succeeded, the response should be a JSON representation of the
      // new event. Try getting the Id property and return it.
      if ($response && $response['Id']) {
        return $response['Id'];
      }
      else {
        error_log("ERROR: ".$response);
        return $response;
      }
    }

    // Uses the Calendar API's CalendarView to get all events
    public static function getEvents($access_token, $user_email, $calendar_id = NULL) {
      $getEventsParameters = array (
        // Only return Subject, Start, and End fields
        "\$select" => "Subject,Start,End",
        // Sort by Start, oldest first
        // "\$orderby" => "Start/DateTime",
        // Return at most 10 results
        "\$top" => "50"
      );

      if ($calendar_id)
        $getEventsUrl = self::$outlookApiUrl."/me/calendars/".$calendar_id."/events?".http_build_query($getEventsParameters);
      else
        $getEventsUrl = self::$outlookApiUrl."/me/events?".http_build_query($getEventsParameters);

      return self::makeApiCall($access_token, $user_email, "GET", $getEventsUrl);
    }


    // Use the Calendar API to add an event to the default calendar.
    public static function createEvent($access_token, $user_email, $subject, $location, $startTime, $endTime, $attendeeString, $calendar_id = NULL) {
      // Create a static body.
      $htmlBody = "<html><body>Added by php-calendar app.</body></html>";

      // Generate the JSON payload
      $event = array(
        "Subject" => $subject,
        "Location" => array("DisplayName" => $location),
        "Start" => array("DateTime" => self::encodeDateTime($startTime), "TimeZone" => "Pacific Standard Time"),
        "End" => array("DateTime" => self::encodeDateTime($endTime), "TimeZone" => "Pacific Standard Time"),
        "Body" => array("ContentType" => "HTML", "Content" => $htmlBody)
      );

      if (!is_null($attendeeString) && strlen($attendeeString) > 0) {
        error_log("Attendees included: ".$attendeeString);

        $attendeeAddresses = array_filter(explode(';', $attendeeString));

        $attendees = array();
        foreach($attendeeAddresses as $address) {
          error_log("Adding ".$address);

          $attendee = array(
            "EmailAddress" => array ("Address" => $address),
            "Type" => "Required"
          );

          $attendees[] = $attendee;
        }

        $event["Attendees"] = $attendees;
      }

      $eventPayload = json_encode($event);
      error_log("EVENT PAYLOAD: ".$eventPayload);

      if ($calendar_id)
        $createEventUrl = self::$outlookApiUrl."/me/calendars/".$calendar_id."/events";
      else
        $createEventUrl = self::$outlookApiUrl."/me/events";

      $response = self::makeApiCall($access_token, $user_email, "POST", $createEventUrl, $eventPayload);

      // If the call succeeded, the response should be a JSON representation of the
      // new event. Try getting the Id property and return it.
      if ($response && $response['Id']) {
        return $response['Id'];
      }
      else {
        error_log("ERROR: ".$response);
        return $response;
      }
    }

    // Use the Calendar API to update an event of the default calendar.
    public static function updateEvent($access_token, $user_email, $eventId, $subject) {
      // Create a static body.
      $htmlBody = "<html><body>Added by php-calendar app.</body></html>";

      // Generate the JSON payload
      $event = array(
        "Subject" => $subject,
        // "Body" => array("ContentType" => "HTML", "Content" => $htmlBody)
      );

      $eventPayload = json_encode($event);
      error_log("EVENT PAYLOAD: ".$eventPayload);

      $createEventUrl = self::$outlookApiUrl."/me/events/".$eventId;

      $response = self::makeApiCall($access_token, $user_email, "PATCH", $createEventUrl, $eventPayload);

      // If the call succeeded, the response should be a JSON representation of the
      // new event. Try getting the Id property and return it.
      if ($response && $response['Id']) {
        return $response['Id'];
      }
      else {
        error_log("ERROR: ".$response);
        return $response;
      }
    }

    // Use the Calendar API to delete an event from the default calendar.
    public static function deleteEvent($access_token, $user_email, $eventId) {

      $createEventUrl = self::$outlookApiUrl."/me/events/".$eventId;

      $response = self::makeApiCall($access_token, $user_email, "DELETE", $createEventUrl);

      // If the call succeeded, the response should be a JSON representation of the
      // new event. Try getting the Id property and return it.
      if ($response && $response['Id']) {
        return $response['Id'];
      }
      else {
        error_log("ERROR: ".$response);
        return $response;
      }
    }

    // This function convert a dateTime from local TZ to UTC, then
    // encodes it in the format expected by the Outlook APIs.
    public static function encodeDateTime($dateTime) {
      $utcDateTime = $dateTime->setTimeZone(new DateTimeZone("UTC"));

      $dateFormat = "Y-m-d\TH:i:s\Z";
      return date_format($utcDateTime, $dateFormat);
    }

    public static function makeApiCall($access_token, $user_email, $method, $url, $payload = NULL) {
      // Generate the list of headers to always send.
      $headers = array(
        "User-Agent: php-tutorial/1.0",         // Sending a User-Agent header is a best practice.
        "Authorization: Bearer ".$access_token, // Always need our auth token!
        "Accept: application/json",             // Always accept JSON response.
        "client-request-id: ".self::makeGuid(), // Stamp each new request with a new GUID.
        "return-client-request-id: true",       // Tell the server to include our request-id GUID in the response.
        "X-AnchorMailbox: ".$user_email         // Provider user's email to optimize routing of API call
      );

      $curl = curl_init($url);

      switch(strtoupper($method)) {
        case "GET":
          // Nothing to do, GET is the default and needs no
          // extra headers.
          error_log("Doing GET");
          break;
        case "POST":
          error_log("Doing POST");
          // Add a Content-Type header (IMPORTANT!)
          $headers[] = "Content-Type: application/json";
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
          break;
        case "PATCH":
          error_log("Doing PATCH");
          // Add a Content-Type header (IMPORTANT!)
          $headers[] = "Content-Type: application/json";
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
          curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
          break;
        case "DELETE":
          error_log("Doing DELETE");
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
          break;
        default:
          error_log("INVALID METHOD: ".$method);
          exit;
      }

      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($curl);
      error_log("curl_exec done.");

      $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      error_log("Request returned status ".$httpCode);

      if ($httpCode >= 400) {
        return array('errorNumber' => $httpCode,
                     'error' => 'Request returned HTTP error '.$httpCode);
      }

      $curl_errno = curl_errno($curl);
      $curl_err = curl_error($curl);

      if ($curl_errno) {
        $msg = $curl_errno.": ".$curl_err;
        error_log("CURL returned an error: ".$msg);
        curl_close($curl);
        return array('errorNumber' => $curl_errno,
                     'error' => $msg);
      }
      else {
        error_log("Response: ".$response);
        curl_close($curl);
        return json_decode($response, true);
      }
    }

    // This function generates a random GUID.
    public static function makeGuid(){
        if (function_exists('com_create_guid')) {
          error_log("Using 'com_create_guid'.");
          return strtolower(trim(com_create_guid(), '{}'));
        }
        else {
          error_log("Using custom GUID code.");
          $charid = strtolower(md5(uniqid(rand(), true)));
          $hyphen = chr(45);
          $uuid = substr($charid, 0, 8).$hyphen
                 .substr($charid, 8, 4).$hyphen
                 .substr($charid, 12, 4).$hyphen
                 .substr($charid, 16, 4).$hyphen
                 .substr($charid, 20, 12);

          return $uuid;
        }
    }
  }
?>
