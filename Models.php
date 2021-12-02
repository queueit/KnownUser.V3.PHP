<?php 
namespace QueueIT\KnownUserV3\SDK;
require_once('QueueITHelpers.php');

class QueueEventConfig
{
    public $eventId;
    public $layoutName; 
    public $culture; 
    public $queueDomain;
    public $extendCookieValidity; 
    public $cookieValidityMinute;
    public $cookieDomain;
    public $isCookieHttpOnly;
    public $isCookieSecure;
    public $version;
    public $actionName;

    function __construct() {
        $this->version = -1;
        $this->actionName = "unspecified";
    }

    public function getString() {
        return "EventId:" . $this->eventId 
            . "&Version:" . $this->version
            . "&ActionName:" . $this->actionName
            . "&QueueDomain:" . $this->queueDomain
            . "&CookieDomain:" . $this->cookieDomain
            . "&IsCookieHttpOnly:" . Utils::boolToString($this->isCookieHttpOnly)
            . "&IsCookieSecure:" . Utils::boolToString($this->isCookieSecure)
            . "&ExtendCookieValidity:" . Utils::boolToString($this->extendCookieValidity)
            . "&CookieValidityMinute:" . $this->cookieValidityMinute
            . "&LayoutName:" . $this->layoutName
            . "&Culture:" . $this->culture;
    }
}

class CancelEventConfig
{
    public $eventId;
    public $queueDomain;
    public $cookieDomain;
    public $isCookieHttpOnly;
    public $isCookieSecure;
    public $version;
    public $actionName;

    function __construct() {
        $this->version = -1;
        $this->actionName = "unspecified";
    }

    public function getString() {
        return "EventId:" . $this->eventId 
            . "&Version:" . $this->version
            . "&QueueDomain:" . $this->queueDomain
            . "&CookieDomain:" . $this->cookieDomain
            . "&IsCookieHttpOnly:" . Utils::boolToString($this->isCookieHttpOnly)
            . "&IsCookieSecure:" . Utils::boolToString($this->isCookieSecure)
            . "&ActionName:" . $this->actionName;
    }
}

class RequestValidationResult
{     
    public $eventId;
    public $redirectUrl;
    public $queueId;
    public $actionType;
    public $redirectType;
    public $actionName;
    public $isAjaxResult;

    function __construct($actionType, $eventId, $queueId, $redirectUrl, $redirectType, $actionName) {
       $this->actionType = $actionType;
       $this->eventId = $eventId;
       $this->queueId = $queueId;
       $this->redirectUrl = $redirectUrl;
       $this->redirectType = $redirectType;
       $this->actionName = $actionName;     
    }

    public function doRedirect() {
        return !Utils::isNullOrEmptyString($this->redirectUrl);
    }

    public function getAjaxQueueRedirectHeaderKey() {
        return "x-queueit-redirect";
    }

    public function getAjaxRedirectUrl() {
        if (!Utils::isNullOrEmptyString($this->redirectUrl)) {
            return rawurlencode($this->redirectUrl);
        }
        return "";
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
    const QueueAction="Queue";
    const CancelAction="Cancel";
    const IgnoreAction="Ignore";    
}