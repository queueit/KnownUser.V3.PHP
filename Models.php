<?php 
namespace QueueIT\KnownUserV3\SDK;

class Utils
{
    public static  function isNullOrEmptyString($value){
    return (!isset($value) || trim($value)==='');
    }
}
class EventConfig
{
    public $eventId;
    public $layoutName; 
    public $culture; 
    public $queueDomain;
    public $extendCookieValidity; 
    public $cookieValidityMinute;
    public $cookieDomain;
    public $version;
}

class RequestValidationResult
{     
    public $eventId;
    public $redirectUrl;
    public $queueId;

    function __construct($eventId, $queueId, $redirectUrl) {
       $this->eventId= $eventId;
       $this->queueId= $queueId;
       $this->redirectUrl = $redirectUrl;
    }

    public function doRedirect() {
        return !Utils::isNullOrEmptyString($this->redirectUrl);
    }     
}

class KnownUserException extends \Exception
{
    function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }
}