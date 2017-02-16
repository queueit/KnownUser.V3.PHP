<?php 
namespace QueueIT\KnownUserV3\SDK;
class EventConfig
{
        public $eventId;
        public $layoutName; 
        public $culture; 
        public $queueDomain;
        public $extendCookieValidity; 
        public $cookieValidityMinute ;
        public $cookieDomain;
        public $version;
}

 class RequestValidationResult
    {
        public $redirectUrl;
        public function doRedirect():bool
        {
            return isset($this->redirectUrl);
        }
        public $eventId;
    }

class KnownUserException extends \Exception
    {
        function __construct(string $message, $code=0)
        {
            parent::__construct($message, $code);
        }
    }
?>