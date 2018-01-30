<?php 
namespace QueueIT\KnownUserV3\SDK;

class Utils
{
    public static  function isNullOrEmptyString($value){
    return (!isset($value) || trim($value)==='');
    }
}

class QueueEventConfig
{
    public $eventId;
    public $layoutName; 
    public $culture; 
    public $queueDomain;
    public $extendCookieValidity; 
    public $cookieValidityMinute;
    public $cookieDomain;
    public $version;
    public function getString()
    {
        return "EventId:".$this->eventId ."&Version:". $this->version
            ."&QueueDomain:".$this->queueDomain ."&CookieDomain:".$this->cookieDomain. "&ExtendCookieValidity:".$this->extendCookieValidity
            ."&CookieValidityMinute:" .$this->cookieValidityMinute."&LayoutName:".$this->layoutName."&Culture:".$this->culture;
    }
}

class CancelEventConfig
{
    public $eventId;
    public $queueDomain;
    public $cookieDomain;
    public $version;
    public function getString()
    {
        return "EventId:".$this->eventId ."&Version:". $this->version
            ."&QueueDomain:".$this->queueDomain ."&CookieDomain:".$this->cookieDomain;
    }
}

class RequestValidationResult
{     
    public $eventId;
    public $redirectUrl;
    public $queueId;
    public $actionType;
	public $redirectType;

    function __construct($actionType, $eventId, $queueId, $redirectUrl, $redirectType) {
       $this->actionType = $actionType;
       $this->eventId = $eventId;
       $this->queueId = $queueId;
       $this->redirectUrl = $redirectUrl;
	   $this->redirectType = $redirectType;
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

class ActionTypes
{
    const QueueAction="Queue" ;
    const CancelAction="Cancel" ;
    const IgnoreAction="Ignore" ;
    
}