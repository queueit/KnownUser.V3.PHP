>You can find the latest released version [here](https://github.com/queueit/KnownUser.V3.PHP/releases/latest) and 
>[packagist](https://packagist.org/packages/queueit/knownuserv3)

# KnownUser.V3.PHP
The Queue-it Security Framework is used to ensure that end users cannot bypass the queue by adding a server-side integration to your server. It supports php >= 5.3.3.

## Introduction
When a user is redirected back from the queue to your website, the queue engine can attache a query string parameter (`queueittoken`) containing some information about the user. 
The most important fields of the `queueittoken` are:

 - q - the users unique queue identifier
 - ts - a timestamp of how long this redirect is valid
 - h - a hash of the token


The high level logic is as follows:

![The KnownUser validation flow](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/KnownUser%20flow.PNG)

 1. User requests a page on your server
 2. The validation method sees that the has no Queue-it session cookie and no `queueittoken` and sends him to the correct queue based on the configuration
 3. User waits in the queue
 4. User is redirected back to your website, now with a `queueittoken`
 5. The validation method validates the `queueittoken` and creates a Queue-it session cookie
 6. The user browses to a new page and the Queue-it session cookie will let him go there without queuing again

## How to validate a user
To validate that the current user is allowed to enter your website (has been through the queue) these steps are needed:

 1. Providing the queue configuration to the KnownUser validation
 2. Validate the `queueittoken` and store a session cookie


### 1. Providing the queue configuration
The recommended way is to use the Go Queue-it self-service portal to setup the configuration. 
The configuration specifies a set of Triggers and Actions. A Trigger is an expression matching one, more or all URLs on your website. 
When a user enter your website and the URL matches a Trigger-expression the corresponding Action will be triggered. 
The Action specifies which queue the users should be send to. 
In this way you can specify which queue(s) should protect which page(s) on the fly without changing the server-side integration.

This configuration can then be downloaded to your application server. 
Read more about how *[here](https://github.com/queueit/KnownUser.V3.PHP/tree/master/Documentation)*.  

### 2. Validate the `queueittoken` and store a session cookie
To validate that the user has been through the queue, use the `KnownUser::validateRequestByIntegrationConfig()` method. 
This call will validate the timestamp and hash and if valid create a "QueueITAccepted-SDFrts345E-V3_[EventId]" cookie with a TTL as specified in the configuration.
If the timestamp or hash is invalid, the user is send back to the queue.


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
            header($result->getAjaxQueueRedirectHeaderKey() . ': '. $result->getAjaxRedirectUrl());            
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
### Protecting ajax calls
If you need to protect AJAX calls beside page loads you need to add the below JavaScript tags to your pages:
```
<script type="text/javascript" src="//static.queue-it.net/script/queueclient.min.js"></script>
<script
 data-queueit-intercept-domain="{YOUR_CURRENT_DOMAIN}"
   data-queueit-intercept="true"
  data-queueit-c="{YOUR_CUSTOMER_ID}"
  type="text/javascript"
  src="//static.queue-it.net/script/queueconfigloader.min.js">
</script>
```

## Alternative Implementation

### Queue configuration

If your application server (maybe due to security reasons) is not allowed to do external GET requests, then you have three options:

1. Manually download the configuration file from Queue-it Go self-service portal, save it on your application server and load it from local disk
2. Use an internal gateway server to download the configuration file and save to application server
3. Specify the configuration in code without using the Trigger/Action paradigm. In this case it is important *only to queue-up page requests* and not requests for resources. 
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
