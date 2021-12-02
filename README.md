# KnownUser.V3.PHP
Before getting started please read the [documentation](https://github.com/queueit/Documentation/tree/main/serverside-connectors) to get acquainted with server-side connectors.

This connector supports PHP >= 5.3.3.

You can find the latest released version [here](https://github.com/queueit/KnownUser.V3.PHP/releases/latest) and packagist package [here](https://packagist.org/packages/queueit/knownuserv3).

## Implementation
The KnownUser validation must be done on *all requests except requests for static and cached pages, resources like images, css files and ...*. 
So, if you add the KnownUser validation logic to a central place, then be sure that the Triggers only fire on page requests (including ajax requests) and not on e.g. image.

If we have the `integrationconfig.json` copied  in the folder beside other knownuser files inside web application folder then 
the following method is all that is needed to validate that a user has been through the queue:
 
```php
require_once( __DIR__ .'Models.php');
require_once( __DIR__ .'KnownUser.php');

$configText = file_get_contents('integrationconfig.json');
$customerID = ""; //Your Queue-it customer ID
$secretKey = ""; //Your 72 char secret key as specified in Go Queue-it self-service platform

$queueittoken = isset( $_GET["queueittoken"] )? $_GET["queueittoken"] :'';

try
{
    $fullUrl = getFullRequestUri();
    $currentUrlWithoutQueueitToken = preg_replace("/([\\?&])("."queueittoken"."=[^&]*)/i", "", $fullUrl);

    //Verify if the user has been through the queue
    $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
       $currentUrlWithoutQueueitToken, $queueittoken, $configText, $customerID, $secretKey);
		
    if($result->doRedirect())
    {
        //Adding no cache headers to prevent browsers to cache requests
        header("Expires:Fri, 01 Jan 1990 00:00:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        //end
    
        if(!$result->isAjaxResult)
        {
            //Send the user to the queue - either because hash was missing or because is was invalid
            header('Location: ' . $result->redirectUrl);		            
        }
        else
        {
            header('HTTP/1.0: 200');
            header($result->getAjaxQueueRedirectHeaderKey() . ': ' . $result->getAjaxRedirectUrl());            
            header("Access-Control-Expose-Headers" . ': ' . $result->getAjaxQueueRedirectHeaderKey());            
        }
		
        die();
    }
    if(!empty($queueittoken) && $result->actionType == "Queue")
    {        
	//Request can continue - we remove queueittoken form querystring parameter to avoid sharing of user specific token
        header('Location: ' . $currentUrlWithoutQueueitToken);
	die();
    }
}
catch(\Exception $e)
{
    // There was an error validating the request
    // Use your own logging framework to log the error
    // This was a configuration error, so we let the user continue
}
```

Helper method to get the current url (you can have your own).
The result of this helper method is used to match Triggers and as the Target url (where to return the users to).
It is therefor important that the result is exactly the url of the users browsers. 

So if your webserver is e.g. behind a load balancer that modifies the host name or port, reformat the helper method as needed:
```php
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
```

## Implementation using inline queue configuration
Specify the configuration in code without using the Trigger/Action paradigm. In this case it is important *only to queue-up page requests* and not requests for resources. 
This can be done by adding custom filtering logic before caling the `KnownUser::resolveQueueRequestByLocalConfig()` method. 

The following is an example of how to specify the configuration in code:

```php
require_once( __DIR__ .'Models.php');
require_once( __DIR__ .'KnownUser.php');

$customerID = ""; //Your Queue-it customer ID
$secretKey = ""; //Your 72 char secret key as specified in Go Queue-it self-service platform

$eventConfig = new QueueIT\KnownUserV3\SDK\QueueEventConfig();
$eventConfig->eventId = ""; // ID of the queue to use
$eventConfig->queueDomain = "xxx.queue-it.net"; //Domain name of the queue.
//$eventConfig->cookieDomain = ".my-shop.com"; //Optional - Domain name where the Queue-it session cookie should be saved.
$eventConfig->cookieValidityMinute = 15; //Validity of the Queue-it session cookie should be positive number.
$eventConfig->extendCookieValidity = true; //Should the Queue-it session cookie validity time be extended each time the validation runs? 
//$eventConfig->culture = "da-DK"; //Optional - Culture of the queue layout in the format specified here: https://msdn.microsoft.com/en-us/library/ee825488(v=cs.20).aspx. If unspecified then settings from Event will be used.
// $eventConfig->layoutName = "NameOfYourCustomLayout"; //Optional - Name of the queue layout. If unspecified then settings from Event will be used.

$queueittoken = isset( $_GET["queueittoken"] )? $_GET["queueittoken"] :'';

try
{
    $fullUrl = getFullRequestUri();
    $currentUrlWithoutQueueitToken = preg_replace("/([\\?&])("."queueittoken"."=[^&]*)/i", "", $fullUrl);

    //Verify if the user has been through the queue
    $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig(
       $currentUrlWithoutQueueitToken, $queueittoken, $eventConfig, $customerID, $secretKey);
	
    if($result->doRedirect())
    {
        //Adding no cache headers to prevent browsers to cache requests
        header("Expires:Fri, 01 Jan 1990 00:00:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        //end
	 if(!$result->isAjaxResult)
        {
            //Send the user to the queue - either because hash was missing or because is was invalid
            header('Location: ' . $result->redirectUrl);		            
        }
        else
        {
            header('HTTP/1.0: 200');
            header($result->getAjaxQueueRedirectHeaderKey() . ': '. $result->getAjaxRedirectUrl());            
            header("Access-Control-Expose-Headers" . ': ' . $result->getAjaxQueueRedirectHeaderKey());            
        }
        
        die();
    }
    if(!empty($queueittoken) && $result->actionType == "Queue")
    {        
	//Request can continue - we remove queueittoken form querystring parameter to avoid sharing of user specific token
        header('Location: ' . $currentUrlWithoutQueueitToken);
	die();
    }
}
catch(\Exception $e)
{
    // There was an error validating the request
    // Use your own logging framework to log the error
    // This was a configuration error, so we let the user continue
}
```
## Request body trigger (advanced)

The connector supports triggering on request body content. An example could be a POST call with specific item ID where you want end-users to queue up for.
For this to work, you will need to contact Queue-it support or enable request body triggers in your integration settings in your GO Queue-it platform account.
Once enabled you will need to update your integration so request body is available for the connector.  
You need to create a new context provider similar to this one:

```php

class HttpRequestBodyProvider extends QueueIT\KnownUserV3\SDK\HttpRequestProvider
{
    function getRequestBodyAsString()
    {
        $requestBody = file_get_contents('php://input');

        if(isset($requestBody)){
            return $requestBody;
        }
        else{
            return '';            
        }
    }
}

```

And then use it instead of default `HttpRequestProvider`

```php
// Default implementation of HttpRequestProvider always returns empty string as request body. 
// Use following line to set a custom httpRequestBodyProvider
QueueIT\KnownUserV3\SDK\KnownUser::setHttpRequestProvider(new HttpRequestBodyProvider());
```