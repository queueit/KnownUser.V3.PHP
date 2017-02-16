# KnownUser.V3.PHP_beta
How to use:

If we have the integrationconfig.json copied  in knownuserv3 folder beside other knownuser files inside web application folder then: 

```php

require_once( __DIR__ ."/knownuserv3/Models.php");
require_once( __DIR__ ."/knownuserv3/KnownUser.php");
$configText = file_get_contents('/knownuserv3/integrationconfig.json');
$queueittoken="";
$currentUrlWithoutQueueITToken;
if(isset($_GET["queueittoken"]))
{
     $queueittoken=$_GET["queueittoken"];
     $currentUrlWithoutQueueITToken =  str_replace("queueittoken=".$queueittoken,"",  getFullRequestUri());
}
else
{
    $currentUrlWithoutQueueITToken = getFullRequestUri();
}

try
{
    $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig($currentUrlWithoutQueueITToken,
                $queueittoken ,$configText,"customerid","secretkey");

    if($result->doRedirect())
    {
      //user has not passed queue so redirect it to queue
        header("Location: ".$result->redirectUrl);
        die();
    }
    if(!empty($queueittoken))
    {
    //user has passed the queue redirect to current URL without queueittoken to remove token from address bar
      header("Location: ".$currentUrlWithoutQueueITToken);
      die();
    }
}
catch
{
//log the exception
}

```

Helper method to get the current url (you can have your own):
```php
 function getFullRequestUri()
 {
     // Get HTTP/HTTPS (the possible values for this vary from server to server)
    $myUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && !in_array(strtolower($_SERVER['HTTPS']),array('off','no'))) ? 'https' :    'http';
    // Get domain portion
    $myUrl .= '://'.$_SERVER['HTTP_HOST'];
    // Get path to script
    $myUrl .= $_SERVER['REQUEST_URI'];
    // Add path info, if any
    if (!empty($_SERVER['PATH_INFO'])) $myUrl .= $_SERVER['PATH_INFO'];

    return $myUrl; 
 }
```

