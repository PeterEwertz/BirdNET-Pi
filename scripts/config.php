<?php
error_reporting(E_ERROR);
ini_set('display_errors',1);

function syslog_shell_exec($cmd, $sudo_user = null) {
  if ($sudo_user) {
    $cmd = "sudo -u $sudo_user $cmd";
  }
  $output = shell_exec($cmd);

  if (strlen($output) > 0) {
    syslog(LOG_INFO, $output);
  }
}

# Basic Settings
if(isset($_GET["latitude"])){
  $latitude = $_GET["latitude"];
  $longitude = $_GET["longitude"];
  $birdweather_id = $_GET["birdweather_id"];
  $apprise_input = $_GET['apprise_input'];
  $apprise_notification_title = $_GET['apprise_notification_title'];
  $apprise_notification_body = $_GET['apprise_notification_body'];
  $flickr_api_key = $_GET['flickr_api_key'];
  $language = $_GET["language"];

  if(isset($_GET['apprise_notify_each_detection'])) {
    $apprise_notify_each_detection = 1;
  } else {
    $apprise_notify_each_detection = 0;
  }
  if(isset($_GET['apprise_notify_new_species'])) {
    $apprise_notify_new_species = 1;
  } else {
    $apprise_notify_new_species = 0;
  }

  // logic for setting the date and time based on user inputs from the form below
  if(isset($_GET['date']) && isset($_GET['time'])) {
    // can't set the date manually if it's getting it from the internet, disable ntp
    exec("sudo timedatectl set-ntp false");

    exec("sudo date -s '".$_GET['date']." ".$_GET['time']."'");
  } else {
    // user checked 'use time from internet if available,' so make sure that's on
    if(strlen(trim(exec("sudo timedatectl | grep \"NTP service: active\""))) == 0){
      exec("sudo timedatectl set-ntp true");
      sleep(3);
    }
  }

  // Update Language settings only if a change is requested
  if (file_exists('./scripts/thisrun.txt')) {
    $lang_config = parse_ini_file('./scripts/thisrun.txt');
  } elseif (file_exists('./scripts/firstrun.ini')) {
    $lang_config = parse_ini_file('./scripts/firstrun.ini');
  }
  if ($language != $lang_config['DATABASE_LANG']){
    $user = trim(shell_exec("awk -F: '/1000/{print $1}' /etc/passwd"));
    $home = trim(shell_exec("awk -F: '/1000/{print $6}' /etc/passwd"));

    // Archive old language file
    syslog_shell_exec("cp -f $home/BirdNET-Pi/model/labels.txt $home/BirdNET-Pi/model/labels.txt.old", $user);

    // Install new language label file
    syslog_shell_exec("$home/BirdNET-Pi/scripts/install_language_label.sh -l $language", $user);

    syslog(LOG_INFO, "Successfully changed language to '$language'");
  }


  $contents = file_get_contents("/etc/birdnet/birdnet.conf");
  $contents = preg_replace("/LATITUDE=.*/", "LATITUDE=$latitude", $contents);
  $contents = preg_replace("/LONGITUDE=.*/", "LONGITUDE=$longitude", $contents);
  $contents = preg_replace("/BIRDWEATHER_ID=.*/", "BIRDWEATHER_ID=$birdweather_id", $contents);
  $contents = preg_replace("/APPRISE_NOTIFICATION_TITLE=.*/", "APPRISE_NOTIFICATION_TITLE=\"$apprise_notification_title\"", $contents);
  $contents = preg_replace("/APPRISE_NOTIFICATION_BODY=.*/", "APPRISE_NOTIFICATION_BODY=\"$apprise_notification_body\"", $contents);
  $contents = preg_replace("/APPRISE_NOTIFY_EACH_DETECTION=.*/", "APPRISE_NOTIFY_EACH_DETECTION=$apprise_notify_each_detection", $contents);
  $contents = preg_replace("/APPRISE_NOTIFY_NEW_SPECIES=.*/", "APPRISE_NOTIFY_NEW_SPECIES=$apprise_notify_new_species", $contents);
  $contents = preg_replace("/FLICKR_API_KEY=.*/", "FLICKR_API_KEY=$flickr_api_key", $contents);
  $contents = preg_replace("/DATABASE_LANG=.*/", "DATABASE_LANG=$language", $contents);

  $contents2 = file_get_contents("./scripts/thisrun.txt");
  $contents2 = preg_replace("/LATITUDE=.*/", "LATITUDE=$latitude", $contents2);
  $contents2 = preg_replace("/LONGITUDE=.*/", "LONGITUDE=$longitude", $contents2);
  $contents2 = preg_replace("/BIRDWEATHER_ID=.*/", "BIRDWEATHER_ID=$birdweather_id", $contents2);
  $contents2 = preg_replace("/APPRISE_NOTIFICATION_TITLE=.*/", "APPRISE_NOTIFICATION_TITLE=\"$apprise_notification_title\"", $contents2);
  $contents2 = preg_replace("/APPRISE_NOTIFICATION_BODY=.*/", "APPRISE_NOTIFICATION_BODY=\"$apprise_notification_body\"", $contents2);
  $contents2 = preg_replace("/APPRISE_NOTIFY_EACH_DETECTION=.*/", "APPRISE_NOTIFY_EACH_DETECTION=$apprise_notify_each_detection", $contents2);
  $contents2 = preg_replace("/APPRISE_NOTIFY_NEW_SPECIES=.*/", "APPRISE_NOTIFY_NEW_SPECIES=$apprise_notify_new_species", $contents2);
  $contents2 = preg_replace("/FLICKR_API_KEY=.*/", "FLICKR_API_KEY=$flickr_api_key", $contents2);
  $contents2 = preg_replace("/DATABASE_LANG=.*/", "DATABASE_LANG=$language", $contents2);


  $fh = fopen("/etc/birdnet/birdnet.conf", "w");
  $fh2 = fopen("./scripts/thisrun.txt", "w");
  fwrite($fh, $contents);
  fwrite($fh2, $contents2);

  if(isset($apprise_input)){
    $user = shell_exec("awk -F: '/1000/{print $1}' /etc/passwd");
    $home = shell_exec("awk -F: '/1000/{print $6}' /etc/passwd");
    $home = trim($home);

    $appriseconfig = fopen($home."/BirdNET-Pi/apprise.txt", "w");
    fwrite($appriseconfig, $apprise_input);
  }

  syslog(LOG_INFO, "Restarting Services");
  shell_exec("sudo restart_services.sh");
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  </style>
  </head>
<div class="settings">
      <h2>Basic Settings</h2>
    <form action="" method="GET">
<?php
if (file_exists('./scripts/thisrun.txt')) {
  $config = parse_ini_file('./scripts/thisrun.txt');
} elseif (file_exists('./scripts/firstrun.ini')) {
  $config = parse_ini_file('./scripts/firstrun.ini');
}
$user = shell_exec("awk -F: '/1000/{print $1}' /etc/passwd");
$home = shell_exec("awk -F: '/1000/{print $6}' /etc/passwd");
$home = trim($home);
if (file_exists($home."/BirdNET-Pi/apprise.txt")) {
  $apprise_config = file_get_contents($home."/BirdNET-Pi/apprise.txt");
} else {
  $apprise_config = "";
}
$caddypwd = $config['CADDY_PWD'];
if (!isset($_SERVER['PHP_AUTH_USER'])) {
  header('WWW-Authenticate: Basic realm="My Realm"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'You cannot edit the settings for this installation';
  exit;
} else {
  $submittedpwd = $_SERVER['PHP_AUTH_PW'];
  $submitteduser = $_SERVER['PHP_AUTH_USER'];
  if($submittedpwd !== $caddypwd || $submitteduser !== 'birdnet'){
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You cannot edit the settings for this installation';
    exit;
  }
}
?>
      <label for="latitude">Latitude: </label>
      <input name="latitude" type="number" max="90" min="-90" step="0.0001" value="<?php print($config['LATITUDE']);?>" required/><br>
      <label for="longitude">Longitude: </label>
      <input name="longitude" type="number" max="180" min="-180" step="0.0001" value="<?php print($config['LONGITUDE']);?>" required/><br>
      <p>Set your Latitude and Longitude to 4 decimal places. Get your coordinates <a href="https://latlong.net" target="_blank">here</a>.</p>
      <label for="birdweather_id">BirdWeather ID: </label>
      <input name="birdweather_id" type="text" value="<?php print($config['BIRDWEATHER_ID']);?>" /><br>
      <p><a href="https://app.birdweather.com" target="_blank">BirdWeather.com</a> is a weather map for bird sounds. Stations around the world supply audio and video streams to BirdWeather where they are then analyzed by BirdNET and compared to eBird Grid data. BirdWeather catalogues the bird audio and spectrogram visualizations so that you can listen to, view, and read about birds throughout the world. <a href="mailto:tim@birdweather.com?subject=Request%20BirdWeather%20ID&body=<?php include('./scripts/birdweather_request.php'); ?>" target="_blank">Email Tim</a> to request a BirdWeather ID</p><br>
      <h3>Notifications</h3>
      <p><a target="_blank" href="https://github.com/caronc/apprise/wiki">Apprise Notifications</a> can be setup and enabled for 70+ notification services. Each service should be on its own line.</p>
      <label for="apprise_input">Apprise Notifications Configuration: </label>
      <textarea placeholder="mailto://{user}:{password}@gmail.com
tgram://{bot_token}/{chat_id}
twitter://{ConsumerKey}/{ConsumerSecret}/{AccessToken}/{AccessSecret}
https://discordapp.com/api/webhooks/{WebhookID}/{WebhookToken}
..." style="vertical-align: top" name="apprise_input" cols="140" rows="5" type="text" ><?php print($apprise_config);?></textarea><br><br>
      <label for="apprise_notification_title">Notification Title: </label>
      <input name="apprise_notification_title" type="text" value="<?php print($config['APPRISE_NOTIFICATION_TITLE']);?>" /><br>
      <label for="apprise_notification_body">Notification Body (use variables $sciname, $comname, or $confidence): </label>
      <input name="apprise_notification_body" type="text" value="<?php print($config['APPRISE_NOTIFICATION_BODY']);?>" /><br>
      <input type="checkbox" name="apprise_notify_new_species" <?php if($config['APPRISE_NOTIFY_NEW_SPECIES'] == 1 && filesize($home."/BirdNET-Pi/apprise.txt") != 0) { echo "checked"; };?> >
      <label for="apprise_notify_new_species">Notify each new species</label><br>
      <input type="checkbox" name="apprise_notify_each_detection" <?php if($config['APPRISE_NOTIFY_EACH_DETECTION'] == 1 && filesize($home."/BirdNET-Pi/apprise.txt") != 0) { echo "checked"; };?> >
      <label for="apprise_notify_each_detection">Notify each new detection</label><br><br>
      <h3>Bird Photos from Flickr</h3>
      <label for="flickr_api_key">Flickr API Key: </label>
      <input name="flickr_api_key" type="text" value="<?php print($config['FLICKR_API_KEY']);?>"/><br>
      <p>Set your Flickr API key to enable the display of bird images next to detections. <a target="_blank" href="https://www.flickr.com/services/api/misc.api_keys.html">Get your free key here.</a></p>
      <h3>Localization</h3>
      <label for="language">Database Language: </label>
      <select name="language">
      <?php
        $langs = array(
          'not-selected' => 'Not Selected',
          "af" => "Afrikaans",
          "ca" => "Catalan",
          "cs" => "Czech",
          "zh" => "Chinese",
          "hr" => "Croatian",
          "da" => "Danish",
          "nl" => "Dutch",
          "en" => "English",
          "et" => "Estonian",
          "fi" => "Finnish",
          "fr" => "French",
          "de" => "German",
          "hu" => "Hungarian",
          "is" => "Icelandic",
          "id" => "Indonesia",
          "it" => "Italian",
          "ja" => "Japanese",
          "lv" => "Latvian",
          "lt" => "Lithuania",
          "no" => "Norwegian",
          "pl" => "Polish",
          "pt" => "Portugues",
          "ru" => "Russian",
          "sk" => "Slovak",
          "sl" => "Slovenian",
          "es" => "Spanish",
          "sv" => "Swedish",
          "th" => "Thai",
          "uk" => "Ukrainian"
        );

        // Create options for each language
        foreach($langs as $langTag => $langName){
          $isSelected = "";
          if($config['DATABASE_LANG'] == $langTag){
            $isSelected = 'selected="selected"';
          }

          echo "<option value='{$langTag}' $isSelected>$langName</option>";
        }
      ?>

      </select>
      <br><br>
      <script>
        function handleChange(checkbox) {
          // this disables the input of manual date and time if the user wants to use the internet time
          var date=document.getElementById("date");
          var time=document.getElementById("time");
          if(checkbox.checked) {
            date.setAttribute("disabled", "disabled");
            time.setAttribute("disabled", "disabled");
          } else {
            date.removeAttribute("disabled");
            time.removeAttribute("disabled");
          }
        }
      </script>
      <?php
      // if NTP service is active, show the checkboxes as checked, and disable the manual input
      $tdc = trim(exec("sudo timedatectl | grep \"NTP service: active\""));
      if (strlen($tdc) > 0) {
        $checkedvalue = "checked";
        $disabledvalue = "disabled";
      } else {
        $checkedvalue = "";
        $disabledvalue = "";
      }
      ?>
      <label for="appt">Select a Date and Time:</label><br>
      <span>If connected to the internet, retrieve time automatically?</span>
      <input type="checkbox" onchange='handleChange(this)' <?php echo $checkedvalue; ?> ><br>
      <input onclick="this.showPicker()" type="date" id="date" name="date" value="<?php echo date('Y-m-d') ?>" <?php echo $disabledvalue; ?>>
      <input onclick="this.showPicker()" type="time" id="time" name="time" value="<?php echo date('H:i') ?>" <?php echo $disabledvalue; ?>>
      <br><br><br>

      <input type="hidden" name="status" value="success">
      <input type="hidden" name="submit" value="settings">
      <button type="submit" name="view" value="Settings">
<?php
if(isset($_GET['status'])){
  echo "Success!";
} else {
  echo "Update Settings";
}
?>
      </button>
      </form>
      <form action="" method="GET">
        <button type="submit" name="view" value="Advanced">Advanced Settings</button>
      </form>
</div>
