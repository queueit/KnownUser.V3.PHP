<?php
namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');
require_once('UserInQueueService.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('IntegrationConfigHelpers.php');
require_once('QueueITHelpers.php');



class KnownUser 
{
    //used for unittest
    private static $userInQueueService = NULL;
    private static function getUserInQueueService() {
        if (KnownUser::$userInQueueService == NULL) 
        {
            return new UserInQueueService(new UserInQueueStateCookieRepository(KnownUser::getHttpRequestProvider()->getCookieManager()));
        }
        return KnownUser::$userInQueueService;
    }

    //used for unittest
    private static $httpRequestProvider= NULL;
    private static function getHttpRequestProvider() {
        if (KnownUser::$httpRequestProvider == NULL) 
        {
            return new HttpRequestProvider();
        }
        return KnownUser::$httpRequestProvider;
    }
    private static $debugInfoArray=NULL;
    public static function extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $secretKey) {
        if (empty($eventId)) {
            throw new KnownUserException("eventId can not be null or empty.");
        }
        if (empty($secretKey)) {
            throw new KnownUserException("secretKey can not be null or empty.");
        }
        if (!is_int($cookieValidityMinute) || intval($cookieValidityMinute) <= 0) {
            throw new KnownUserException("cookieValidityMinute should be integer greater than 0.");
        }
        $userInQueueService = KnownUser::getUserInQueueService();
        $userInQueueService->extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $secretKey);
    }

    public static function resolveRequestByLocalEventConfig($targetUrl, $queueitToken, QueueEventConfig $queueConfig, $customerId, $secretKey) {
        if (KnownUser::getIsDebug($queueitToken, $secretKey))
        {
            $dic = array(
            "targetUrl"=> $targetUrl,
            "queueitToken"=> $queueitToken,
            "queueConfig"=>$queueConfig != null ? $queueConfig->getString() : "NULL",
            "OriginalURL"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
            KnownUser::doCookieLog($dic);
        }
        if (Utils::isNullOrEmptyString($customerId)) {
            throw new KnownUserException("customerId can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($secretKey)) {
            throw new KnownUserException("secretKey can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($queueConfig->eventId)) {
            throw new KnownUserException("eventId from queueConfig can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($queueConfig->queueDomain)) {
            throw new KnownUserException("queueDomain from queueConfig can not be null or empty.");
        }

        if (!is_int($queueConfig->cookieValidityMinute) || intval($queueConfig->cookieValidityMinute) <= 0) {
            throw new KnownUserException("cookieValidityMinute from queueConfig should be integer greater than 0.");
        }

        if (!is_bool($queueConfig->extendCookieValidity)) {
            throw new KnownUserException("extendCookieValidity from queueConfig should be valid boolean.");
        }

        $userInQueueService = KnownUser::getUserInQueueService();
        return $userInQueueService->validateQueueRequest($targetUrl, $queueitToken, $queueConfig, $customerId, $secretKey);
    }
    
    public static function cancelRequestByLocalConfig($targetUrl, $queueitToken,CancelEventConfig $cancelConfig, $customerId, $secretKey) {
        if (KnownUser::getIsDebug($queueitToken, $secretKey))
        {
            $dic = array(
            "targetUrl"=> $targetUrl,
            "queueitToken"=> $queueitToken,
            "cancelConfig"=>$cancelConfig != null ? $cancelConfig->getString() : "NULL",
            "OriginalURL"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
            KnownUser::doCookieLog($dic);
        }

        if (Utils::isNullOrEmptyString($targetUrl)) {
            throw new KnownUserException("targetUrl can not be null or empty.");
        }
        if (Utils::isNullOrEmptyString($customerId)) {
            throw new KnownUserException("customerId can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($secretKey)) {
            throw new KnownUserException("secretKey can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($cancelConfig->eventId)) {
            throw new KnownUserException("eventId from cancelConfig can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($cancelConfig->queueDomain)) {
            throw new KnownUserException("queueDomain from cancelConfig can not be null or empty.");
        }
        $userInQueueService = KnownUser::getUserInQueueService();
        return $userInQueueService->validateCancelRequest($targetUrl, $cancelConfig, $customerId, $secretKey);
    }

    public static function validateRequestByIntegrationConfig($currentUrl, $queueitToken, $integrationsConfigString, $customerId, $secretKey) {
        $isDebug = KnownUser::getIsDebug($queueitToken, $secretKey);
        if ($isDebug)
        {
            $dic = array(
           "queueitToken"=> $queueitToken,
           "pureUrl"=> $currentUrl,
           "OriginalURL"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
           KnownUser::doCookieLog($dic);
        }
        if (Utils::isNullOrEmptyString($currentUrl)) {
            throw new KnownUserException("currentUrl can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($integrationsConfigString)) {
            throw new KnownUserException("integrationsConfigString can not be null or empty.");
        }


        try {
                $integrationEvaluator = new IntegrationEvaluator();
                $customerIntegration = json_decode($integrationsConfigString, true);
                if ($isDebug)
                {
                    $dic = array("configVersion"=>$customerIntegration["Version"]);
                    KnownUser::doCookieLog($dic);
                }
                $matchedConfig = $integrationEvaluator->getMatchedIntegrationConfig($customerIntegration, $currentUrl,
                    KnownUser::getCookieArray(), KnownUser::getHttpRequestProvider()->getUserAgent());

                if ($isDebug)
                {
                    $dic = array("matchedConfig"=>(($matchedConfig !=NULL) ? $matchedConfig["Name"]:"NULL"));
                    KnownUser::doCookieLog($dic);
                }
                
                if ($matchedConfig == NULL) {
                       return new RequestValidationResult(NULL,NULL, NULL, NULL);
                } 


                if(!array_key_exists("ActionType",$matchedConfig) || $matchedConfig["ActionType"]== ActionTypes::QueueAction)
                {
                    
                    $eventConfig = new QueueEventConfig();
                    $targetUrl = "";
                    $eventConfig->eventId = $matchedConfig["EventId"];
                    $eventConfig->queueDomain = $matchedConfig["QueueDomain"];
                    $eventConfig->layoutName = $matchedConfig["LayoutName"];
                    $eventConfig->culture = $matchedConfig["Culture"];
                    $eventConfig->cookieDomain = $matchedConfig["CookieDomain"];
                    $eventConfig->extendCookieValidity = $matchedConfig["ExtendCookieValidity"];
                    $eventConfig->cookieValidityMinute = $matchedConfig["CookieValidityMinute"];
                    $eventConfig->version = $customerIntegration["Version"];

                    switch ($matchedConfig["RedirectLogic"]) {
                        case "ForcedTargetUrl":
                        case "ForecedTargetUrl":
                            $targetUrl = $matchedConfig["ForcedTargetUrl"];
                            break;
                        case "EventTargetUrl":
                            $targetUrl = "";
                            break;
                        default :
                        $targetUrl = $currentUrl;
                }
                 return KnownUser::resolveRequestByLocalEventConfig($targetUrl, $queueitToken, $eventConfig, $customerId, $secretKey);
                }
                else //cancel action
                {
                    $cancelEventConfig = new CancelEventConfig();
                    $cancelEventConfig->eventId = $matchedConfig["EventId"];
                    $cancelEventConfig->queueDomain = $matchedConfig["QueueDomain"];
                    $cancelEventConfig->cookieDomain = $matchedConfig["CookieDomain"];
                    $cancelEventConfig->version = $customerIntegration["Version"];
                   return KnownUser::cancelRequestByLocalConfig($currentUrl, $queueitToken, $cancelEventConfig, $customerId, $secretKey);
                }
        }
             catch (\Exception $e) {
                throw new KnownUserException("integrationConfiguration text was not valid: ". $e->getMessage());
            }
           
        
    }

    private static function getCookieArray() {
        $arryCookie = array();
        foreach ($_COOKIE as $key => $val) {
            $arryCookie[$key] = $val;
        }
        return $arryCookie;
    }

    private static function doCookieLog(array $debugInfos)
    {  
        if(KnownUser::$debugInfoArray !=NULL)
        {
            foreach (KnownUser::$debugInfoArray as $key => $value)
            {
                if (!array_key_exists($key,$debugInfos)) {
                    $debugInfos[$key]= $value;
                }
            }
        }
  
        $cookieNameValues = array();
        foreach ($debugInfos as $key => $value)
        {
          array_push( $cookieNameValues, $key.'='.$value);
        }

        KnownUser::getHttpRequestProvider()->getCookieManager()->setCookie("queueitdebug",  implode('&', $cookieNameValues ),0,NULL);    
        KnownUser::$debugInfoArray = $debugInfos;
    }

    private static function getIsDebug($queueitToken, $secretKey)
    {

        $queueParams = QueueUrlParams::extractQueueParams($queueitToken);
        if(!Utils::isNullOrEmptyString($queueitToken)) {
            if (!Utils::isNullOrEmptyString($queueParams->redirectType) && strtolower($queueParams->redirectType) == "debug")
            {


                $calculatedHash = hash_hmac('sha256', $queueParams->queueITTokenWithoutHash, $secretKey);
                return strtoupper($calculatedHash) == strtoupper($queueParams->hashCode);
            }
        }
        return false;
    }

}

class CookieManager implements ICookieManager 
{
    public function getCookie($cookieName) {
        if (array_key_exists($cookieName,$_COOKIE)) {
            return $_COOKIE[$cookieName];
        } else {
            return null;
        }
    }

    public function setCookie($name, $value, $expire, $domain) {
        if ($domain == NULL) {
            $domain = "";
        }
        setcookie($name, $value, $expire, "/", $domain, false, true);
    }
}
interface  IHttpRequestProvider 
{
    function getUserAgent();
    function getCookieManager();
    function getAbsoluteUri();
}

class HttpRequestProvider implements IHttpRequestProvider
{
    function getUserAgent() {
        return array_key_exists ('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : "";
    }
    function getCookieManager()
    {
        return new CookieManager(); 
    }
    function getAbsoluteUri()
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
}