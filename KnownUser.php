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
            "TargetUrl"=> $targetUrl,
            "QueueitToken"=> $queueitToken,
            "QueueConfig"=>$queueConfig != null ? $queueConfig->getString() : "NULL",
            "OriginalUrl"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
			KnownUser::logMoreRequestDetails($dic);
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
            "TargetUrl"=> $targetUrl,
            "QueueitToken"=> $queueitToken,
            "CancelConfig"=>$cancelConfig != null ? $cancelConfig->getString() : "NULL",
            "OriginalUrl"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
			KnownUser::logMoreRequestDetails($dic);
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

    public static function validateRequestByIntegrationConfig($currentUrlWithoutQueueITToken, $queueitToken, $integrationsConfigString, $customerId, $secretKey) {
        $isDebug = KnownUser::getIsDebug($queueitToken, $secretKey);
        if ($isDebug)
        {
            $dic = array(
           "QueueitToken"=> $queueitToken,
           "PureUrl"=> $currentUrlWithoutQueueITToken,
           "OriginalUrl"=> KnownUser::getHttpRequestProvider()->getAbsoluteUri());
		   KnownUser::logMoreRequestDetails($dic);
           KnownUser::doCookieLog($dic);
        }
        if (Utils::isNullOrEmptyString($currentUrlWithoutQueueITToken)) {
            throw new KnownUserException("currentUrlWithoutQueueITToken can not be null or empty.");
        }

        if (Utils::isNullOrEmptyString($integrationsConfigString)) {
            throw new KnownUserException("integrationsConfigString can not be null or empty.");
        }


        try {
                $integrationEvaluator = new IntegrationEvaluator();
                $customerIntegration = json_decode($integrationsConfigString, true);
                if ($isDebug)
                {
                    $dic = array("ConfigVersion"=>$customerIntegration["Version"]);
                    KnownUser::doCookieLog($dic);
                }
                $matchedConfig = $integrationEvaluator->getMatchedIntegrationConfig($customerIntegration, $currentUrlWithoutQueueITToken,
                   KnownUser::getHttpRequestProvider());

                if ($isDebug)
                {
                    $dic = array("MatchedConfig"=>(($matchedConfig !=NULL) ? $matchedConfig["Name"]:"NULL"));
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
                        $targetUrl = $currentUrlWithoutQueueITToken;
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
                   return KnownUser::cancelRequestByLocalConfig($currentUrlWithoutQueueITToken, $queueitToken, $cancelEventConfig, $customerId, $secretKey);
                }
        }
             catch (\Exception $e) {
                throw new KnownUserException("integrationConfiguration text was not valid: ". $e->getMessage());
            }
           
        
    }

	private static function logMoreRequestDetails(array &$debugInfos)
	{
		$allHeaders = KnownUser::getHttpRequestProvider()->getHeaderArray();

		$debugInfos["ServerUtcTime"] = gmdate("Y-m-d\TH:i:s\Z");
        $debugInfos["RequestIP"] = KnownUser::getHttpRequestProvider()->getUserHostAddress();
        $debugInfos["RequestHttpHeader_Via"] = array_key_exists ('via', $allHeaders) ? $allHeaders['via'] : "";
        $debugInfos["RequestHttpHeader_Forwarded"] = array_key_exists ('forwarded', $allHeaders) ? $allHeaders['forwarded'] : "";
        $debugInfos["RequestHttpHeader_XForwardedFor"] = array_key_exists ('x-forwarded-for', $allHeaders) ? $allHeaders['x-forwarded-for'] : "";
        $debugInfos["RequestHttpHeader_XForwardedHost"] = array_key_exists ('x-forwarded-host', $allHeaders) ? $allHeaders['x-forwarded-host'] : "";
        $debugInfos["RequestHttpHeader_XForwardedProto"] = array_key_exists ('x-forwarded-proto', $allHeaders) ? $allHeaders['x-forwarded-proto'] : "";
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

        KnownUser::getHttpRequestProvider()->getCookieManager()->setCookie("queueitdebug", implode('|', $cookieNameValues ), 0, NULL);    
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
    public function getCookieArray() {
        $arryCookie = array();
        foreach ($_COOKIE as $key => $val) {
            $arryCookie[$key] = $val;
        }
        return $arryCookie;
    }
}
interface  IHttpRequestProvider 
{
    function getUserAgent();
	function getUserHostAddress();
    function getCookieManager();
    function getAbsoluteUri();
    function getHeaderArray();
}

class HttpRequestProvider implements IHttpRequestProvider
{
    private $cookieManager;
    private $allHeadersLowerCaseKeyArray;
    
	function getUserAgent() 
	{
        return array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : "";
    }

	function getUserHostAddress()
	{
		return array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : "";
	}

    function getCookieManager()
    {
        if($this->cookieManager==NULL)
        {
            $this->cookieManager = new CookieManager();
        }
        return $this->cookieManager;
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
    function getHeaderArray()
    {
        if($this->allHeadersLowerCaseKeyArray == NULL)
        {
            $tempArray=array();
            foreach( getallheaders() as $key=>$value)
            {
                $tempArray[strtolower($key)]=$value;
            }
            $this->allHeadersLowerCaseKeyArray = $tempArray;
        }
        return $this->allHeadersLowerCaseKeyArray;
    }
}

//https://github.com/ralouphie/getallheaders/blob/master/src/getallheaders.php
//PHP getallheaders polyfill
if (!function_exists('getallheaders')) {
    /**
     * Get all HTTP header key/values as an associative array for the current request.
     *
     * @return string[string] The HTTP header key/value pairs.
     */
    function getallheaders()
    {
        $headers = array();
        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }
        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }
        return $headers;
    }
}