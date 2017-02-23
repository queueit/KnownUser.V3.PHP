<?php
error_reporting(E_ALL);
require_once( __DIR__ .'/../knownuserv3/Models.php');
require_once( __DIR__ .'/../knownuserv3/KnownUser.php');
$configText = file_get_contents('integrationconfig.json');
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
                $queueittoken ,$configText,'customerid','secretKey');

    if($result->doRedirect())
    {
      //user has not passed queue so redirect it to queue
        header('Location: '.$result->redirectUrl);
        die();
    }
    if(!empty($queueittoken))
    {
    //user has passed the queue redirect to current URL without queueittoken to remove token from address bar
     header('Location: '.$currentUrlWithoutQueueITToken);
     die();
    }
}
catch(\Exception $e)
{
//log the exception
}

    //QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie('eventid',60,'','secretKey');
   //  QueueIT\KnownUserV3\SDK\KnownUser::cancelQueueCookie('eventid');
 function getFullRequestUri()
 {
     // Get HTTP/HTTPS (the possible values for this vary from server to server)
    $myUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && !in_array(strtolower($_SERVER['HTTPS']),array('off','no'))) ? 'https' : 'http';
    // Get domain portion
    $myUrl .= '://'.$_SERVER['HTTP_HOST'];
    // Get path to script
    $myUrl .= $_SERVER['REQUEST_URI'];
    // Add path info, if any
    if (!empty($_SERVER['PATH_INFO'])) $myUrl .= $_SERVER['PATH_INFO'];

    return $myUrl; 
 }
?>
 <html>
  <head>
    <title>Sample "Hello, World" Application</title>
  </head>
  <body bgcolor=white>

    <table border="0" cellpadding="10">
      <tr>
        <td>
         
        </td>
        <td>
          <h1>Sample "Hello, World" BuyTickets</h1>
        </td>
      </tr>
    </table>

    <p>This is the home page for the HelloWorld Web application. </p>
    <p>To prove that they work, you can execute either of the following links:
    <ul>
      <li>To a <a href="page2.php">Page2</a>.
      <li>To a <a href="page3.php">Page3</a>.
    </ul>

  </body>
</html>