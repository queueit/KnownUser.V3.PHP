# KnownUser.V3.PHP
Before getting started please read the [documentation](https://github.com/queueit/Documentation/tree/main/serverside-connectors) to get acquainted with server-side connectors.

This connector supports PHP >= 5.3.3.

You can find the latest released version [here](https://github.com/queueit/KnownUser.V3.PHP/releases/latest) and packagist package [here](https://packagist.org/packages/queueit/knownuserv3).

## Implementation
The KnownUser validation must be done on *all requests except requests for static and cached pages, resources like images, css files and ...*. 
So, if you add the KnownUser validation logic to a central place, then make sure that the Triggers only fire on page requests (including ajax requests) and not on e.g. image.

If the integrationconfig.json file is placed in the same folder as the other KnownUser files within the web application directory, 
then the following method is all that’s needed to validate that a user has passed through the queue:
 
```php
require_once( __DIR__ .'/Models.php');
require_once( __DIR__ .'/KnownUser.php');

$configText = file_get_contents('integrationconfig.json');
$customerID = ""; //Your Queue-it customer ID
$secretKey = ""; //Your 72 characters secret key as specified in Go Queue-it self-service platform

try
{
    $fullUrl = getFullRequestUri();
    $queueittoken = QueueIT\KnownUserV3\SDK\Utils::getParameterByName($fullUrl, QueueIT\KnownUserV3\SDK\KnownUser::QueueItTokenKey);
    $currentUrlWithoutQueueitToken = preg_replace("/([\\?&])("."queueittoken"."=[^&]*)/i", "", $fullUrl);

    //Verify if the user has passed through the queue
    $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig($currentUrlWithoutQueueitToken, 
			$queueittoken, $configText, $customerID, $secretKey);
		
    if($result->doRedirect())
    {
        //Adding no cache headers to prevent browsers to cache requests
        header("Expires:Fri, 01 Jan 1990 00:00:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        //end
    
        if(!$result->isAjaxResult)
        {
            //Send the user to the queue - either because the hash was missing or because it was invalid
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
	//Request can continue - we remove queueittoken from the query string to avoid sharing a user specific token
        header('Location: ' . $currentUrlWithoutQueueitToken);
	die();
    }
}
catch(\Exception $e)
{
    // There is an error validating the request
    // Use your own logging framework to log the error
    // This is a configuration error, so we allow the user to continue
}
```

Helper method to get the current url (you can use your own implementation).
The result of this helper method is used to match Triggers and as the Target url (where users are returned to).
It is therefore important that the result exactly matches the URL in the user's browser.

So, if your web server is, for example, behind a load balancer that modifies the hostname or port, adjust the helper method accordingly:
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
This can be done by adding custom filtering logic before calling the `QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig()` method. 

The following is an example of how to specify the configuration in code:

```php
require_once( __DIR__ .'/Models.php');
require_once( __DIR__ .'/KnownUser.php');

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

try
{    
    $fullUrl = getFullRequestUri();
    $queueittoken = QueueIT\KnownUserV3\SDK\Utils::getParameterByName($fullUrl, QueueIT\KnownUserV3\SDK\KnownUser::QueueItTokenKey);
    $currentUrlWithoutQueueitToken = preg_replace("/([\\?&])("."queueittoken"."=[^&]*)/i", "", $fullUrl);

    //Verify if the user has passed through the queue
    $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig($currentUrlWithoutQueueitToken, 
			$queueittoken, $eventConfig, $customerID, $secretKey);
	
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
	//Request can continue - we remove queueittoken form the query string parameter to avoid sharing of user specific token
        header('Location: ' . $currentUrlWithoutQueueitToken);
	die();
    }
}
catch(\Exception $e)
{
    // There is an error validating the request
    // Use your own logging framework to log the error
    // This is a configuration error, so we allow the user to continue
}
```
## Request body trigger (advanced)

The connector supports triggering on request body content. An example could be a POST call with specific item ID where you want end-users to queue up for.
For this to work, you need to contact Queue-it support or enable request body triggers in your integration settings in your GO Queue-it platform account.
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
