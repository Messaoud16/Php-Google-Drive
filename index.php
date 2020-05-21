<?php
include('ScreenshotMachine.php');
ini_set('max_execution_time', 0);

require __DIR__ . '/vendor/autoload.php';

 
 function getClient(){


  $REDIRECT_URI = 'http://localhost:8080';  // You should put your redirect_URI !important
  $KEY_LOCATION = __DIR__ . '/credentials.json';   // You should put your credentials !important
  $TOKEN_FILE   = 'token.txt';
  $SCOPES = 'https://www.googleapis.com/auth/drive';

  $client = new Google_Client();
  $client->setApplicationName("Your_APPLICATION_NAME");  // // You should put your application name !important
  $client->setAuthConfig($KEY_LOCATION);

  // Allow access to Google API when the user is not present.
  $client->setAccessType('offline');
  $client->setRedirectUri($REDIRECT_URI);
  $client->setScopes($SCOPES);

  if (isset($_GET['code']) && !empty($_GET['code'])) 
  {
      try {
          // Exchange the one-time authorization code for an access token
          $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);

          // Save the access token and refresh token in local filesystem
          file_put_contents($TOKEN_FILE, json_encode($accessToken));

          $_SESSION['accessToken'] = $accessToken;
          header('Location: ' . filter_var($REDIRECT_URI, FILTER_SANITIZE_URL));
          exit();
      }
      catch (\Google_Service_Exception $e) {
          print_r($e);
      }
  } 

  if (!isset($_SESSION['accessToken'])) {

      $token = @file_get_contents($TOKEN_FILE);

      if ($token == null) {

          // Generate a URL to request access from Google's OAuth 2.0 server:
          $authUrl = $client->createAuthUrl();

          // Redirect the user to Google's OAuth server
          header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
          exit();

      } else {

          $_SESSION['accessToken'] = json_decode($token, true);

      }
  }

  $client->setAccessToken($_SESSION['accessToken']);

  /* Refresh token when expired */
  if ($client->isAccessTokenExpired()) {
      // the new access token comes with a refresh token as well
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      file_put_contents($TOKEN_FILE, json_encode($client->getAccessToken()));
  }

  return $client;
}

  $client = getClient(); 
  $service = new Google_Service_Drive($client);

$customer_key = "PUT_YOUR_CUSTOMER_KEY_HERE";  // Get your customer key from  https://www.screenshotmachine.com/ !important
$secret_phrase = ""; //leave secret phrase empty, if not needed

$machine = new ScreenshotMachine($customer_key, $secret_phrase);

$web_sites = array('https://ifunded.de/en/', 
    'https://www.propertypartner.co',
    'https://propertymoose.co.uk',
    'https://www.homegrown.co.uk',
    'https://www.realtymogul.com' );

$web_name = array('iFunded','Property Partner','Property Moose','Homegrown',' Realty Mogul' );



for($i= 0 ; $i < 5; $i++)
{
    $id = $i +1;
//mandatory parameter
$options['url'] = $web_sites[$i];

// all next parameters are optional, see our website screenshot API guide for more details
$options['dimension'] = "1920x1080";  //  "widthxheight 
$options['device'] = "desktop";
$options['format'] = "jpg";
$options['cacheLimit'] = "0";  // never use cache, always download fresh screenshot
$options['zoom'] = "100";  // default zoom factor, original website size

$api_url = $machine->generate_screenshot_api_url($options);



//Insert a file
$file = new Google_Service_Drive_DriveFile();
$file->setName($id.'_'.$web_name[$i]);
$file->setDescription('Screenshot');
$file->setMimeType('image/jpg');

$data = file_get_contents($api_url);

$createdFile = $service->files->create($file, array(
      'data' => $data,
      'mimeType' => 'image/jpg',
    ));

}


echo "Screenshots have been saved to Google Drive";

