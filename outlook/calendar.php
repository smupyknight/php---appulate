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
     <title>PHP Calendar API</title>
     <script type="text/javascript" src="./Datepicker/jquery/dist/jquery.min.js"></script>
     <script type="text/javascript" src="./Datepicker/moment/min/moment.min.js"></script>
     <script type="text/javascript" src="./Datepicker/bootstrap/dist/js/bootstrap.min.js"></script>
     <script type="text/javascript" src="./Datepicker/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
     <link rel="stylesheet" href="./Datepicker/bootstrap/dist/css/bootstrap.min.css" />
     <link rel="stylesheet" href="./Datepicker/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css" />
     <style>
       table, th, td {
         border: 1px solid black;
         border-collapse: collapse;
       }
       th, td {
         padding: 5px;
       }
     </style>
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
         $calendars = OutlookCalendarService::getCalendars($_SESSION['access_token'], $_SESSION['user_email']);
         if (SessionManager::checkResponseAndRefreshToken($calendars, $redirectUri)) {
           // Pick up new access token
           $accessToken = $_SESSION['accessToken'];

           error_log("Retrying get events request");
           $calendars = OutlookCalendarService::getCalendars($_SESSION['access_token'], $_SESSION['user_email']);
         }
      ?>
        <div class="container">
          <div class="row">
               <h2>Your calendars</h2>
               <table>
                 <tr>
                   <th>Name</th>
                   <th>Id</th>
                 </tr>

                 <?php foreach($calendars['value'] as $calendar) { ?>
                   <tr>
                     <td><?php echo $calendar['Name'] ?></td>
                     <td><?php echo $calendar['Id'] ?></td>
                   </tr>
                 <?php
                   }
                  ?>
               </table>
             <?php
               $events = OutlookCalendarService::getEvents($_SESSION['access_token'], $_SESSION['user_email']);
              ?>
               <h2>Your events</h2>

               <table>
                 <tr>
                   <th>Subject</th>
                   <th>Start</th>
                   <th>End</th>
                   <th></th>
                   <th></th>
                 </tr>

                 <?php foreach($events['value'] as $event) { ?>
                   <tr>
                     <td><?php echo $event['Subject'] ?></td>
                     <td><?php echo date_format(date_create($event['Start']['DateTime']), "m/d/Y H:i") ?></td>
                     <td><?php echo date_format(date_create($event['End']['DateTime']), "m/d/Y H:i") ?></td>
                     <td><input type="button" onclick="location.href='./updateEvent.php?eventId=<?php echo $event['Id'] ?>';" value="Update" /></td>
                     <td><input type="button" onclick="location.href='./deleteEvent.php?eventId=<?php echo $event['Id'] ?>';" value="Delete" /></td>
                   </tr>
                 <?php
                 } ?>
               </table>
               <hr/>
               <h4>Create your new event.</h4>
               <div class='col-sm-6'>
                 <form action="createEvent.php" method="post">
                   <label for="subject" class="control-label">Subject</label>
                   <input type="text" id="subject" name="subject"><br>

                   <label for="location" class="control-label">Location</label>
                   <input type="text" id="location" name="location"><br>

                   <label for="attendee" class="control-label">Attendee</label>
                   <input type="text" id="attendee" name="attendee"><br>

                   <div class="form-group">
                       <label for="datetimepicker_start" class="control-label">Start Time</label>
                       <div class='input-group date' id='datetimepicker_start'>
                           <input type='text' name="start_time" class="form-control" />
                           <span class="input-group-addon">
                               <span class="glyphicon glyphicon-calendar"></span>
                           </span>
                       </div>

                       <label for="datetimepicker_end" class="control-label">End Time</label>
                       <div class='input-group date' id='datetimepicker_end'>
                           <input type='text' name="end_time" class="form-control" />
                           <span class="input-group-addon">
                               <span class="glyphicon glyphicon-calendar"></span>
                           </span>
                       </div>
                   </div>
                   <script type="text/javascript">
                       $(function () {
                           $('#datetimepicker_start').datetimepicker();
                           $('#datetimepicker_end').datetimepicker();
                       });
                   </script>

                   <hr/>
                   <input type="submit" value="Create Event">
                 </form>
               </div>
           </div>
         </div>
     <?php
       }
     ?>
   </body>
 </html>
