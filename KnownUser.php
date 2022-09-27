<?php

namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');
require_once('UserInQueueService.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('IntegrationConfigHelpers.php');
require_once('QueueITHelpers.php');

class KnownUser
{
    const QueueITAjaxHeaderKey = "x-queueit-ajaxpageurl";    

    //used for unittest
    private static $userInQueueService = null;
    private static function getUserInQueueService()
    {
        if (KnownUser::$userInQueueService == null) {
            return new UserInQueueService(new UserInQueueStateCookieRepository(KnownUser::getHttpRequestProvider()->getCookieManager()));
        }
        return KnownUser::$userInQueueService;
    }

    public static function setHttpRequestProvider(IHttpRequestProvider $customHttpRequestProvider){
        KnownUser::$httpRequestProvider = $customHttpRequestProvider;
    }

    //used for unittest
    private static $httpRequestProvider = null;
    private static function getHttpRequestProvider()
    {
        if (KnownUser::$httpRequestProvider == null) {
            return new HttpRequestProvider();
        }
        return KnownUser::$httpRequestProvider;
    }

    private static $debugInfoArray = null;
    public static function extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey)
    {
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
        $userInQueueService->extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey);
    }

    private static function _cancelRequestByLocalConfig(
        $targetUrl,
        $queueitToken,
        CancelEventConfig $cancelConfig,
        $customerId,
        $secretKey,
        $isDebug)
     {
        $targetUrl = KnownUser::generateTargetUrl($targetUrl);

        if ($isDebug) {
            $dic = array(
                "TargetUrl" => $targetUrl,
                "SdkVersion" => UserInQueueService::getSDKVersion(),
                "RunTime" => KnownUser::getRuntime(),
                "QueueitToken" => $queueitToken,
                "CancelConfig" => $cancelConfig != null ? $cancelConfig->getString() : "NULL",
                "OriginalUrl" => KnownUser::getHttpRequestProvider()->getAbsoluteUri()
            );
            KnownUser::logMoreRequestDetails($dic);
            KnownUser::updateDebugCookieDetails($dic);
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
        $result =  $userInQueueService->validateCancelRequest($targetUrl, $cancelConfig, $customerId, $secretKey);
        $result->isAjaxResult = KnownUser::isQueueAjaxCall();
        return $result;
    }

    public static function cancelRequestByLocalConfig(
        $targetUrl,
        $queueitToken,
        CancelEventConfig $cancelConfig,
        $customerId,
        $secretKey
    ) {
        $connectorDiagnostics = ConnectorDiagnostics::verify($customerId, $secretKey, $queueitToken);

        if ($connectorDiagnostics->hasError) {
            return $connectorDiagnostics->validationResult;
        }

        try {
            $result =  KnownUser::_cancelRequestByLocalConfig($targetUrl, $queueitToken, $cancelConfig, $customerId, $secretKey, $connectorDiagnostics->isEnabled);
            KnownUser::sendDebugCookie();
            return $result;
        } catch (\Exception $e) {
            if ($connectorDiagnostics->isEnabled) {
                $dic = array("Exception" => $e->getMessage());
                KnownUser::updateDebugCookieDetails($dic);
                KnownUser::sendDebugCookie();
            }
            throw $e;
        }
    }

    public static function validateRequestByIntegrationConfig($currentUrlWithoutQueueITToken, $queueitToken, $integrationsConfigString, $customerId, $secretKey)
    {
        $connectorDiagnostics = ConnectorDiagnostics::verify($customerId, $secretKey, $queueitToken);

        if ($connectorDiagnostics->hasError) {
            return $connectorDiagnostics->validationResult;
        }

        try {
            if ($connectorDiagnostics->isEnabled) {
                $dic = array(
                    "SdkVersion" => UserInQueueService::getSDKVersion(),
                    "RunTime" => KnownUser::getRuntime(),
                    "QueueitToken" => $queueitToken,
                    "PureUrl" => $currentUrlWithoutQueueITToken,
                    "OriginalUrl" => KnownUser::getHttpRequestProvider()->getAbsoluteUri()
                );
                KnownUser::logMoreRequestDetails($dic);
                KnownUser::updateDebugCookieDetails($dic);
            }

            if (Utils::isNullOrEmptyString($currentUrlWithoutQueueITToken)) {
                throw new KnownUserException("currentUrlWithoutQueueITToken can not be null or empty.");
            }

            if (Utils::isNullOrEmptyString($integrationsConfigString)) {
                throw new KnownUserException("integrationsConfigString can not be null or empty.");
            }

            $integrationEvaluator = new IntegrationEvaluator();
            $customerIntegration = json_decode($integrationsConfigString, true);
            if ($connectorDiagnostics->isEnabled) {
                if($customerIntegration == null)
                {
                    $dic = array("ConfigVersion" => "NULL");
                }
                else
                {
                    $dic = array("ConfigVersion" => $customerIntegration["Version"]);
                }
                KnownUser::updateDebugCookieDetails($dic);
            }
            if ($customerIntegration == null) {
                throw new KnownUserException("integrationsConfigString is invalid.");
            }

            $matchedConfig = $integrationEvaluator->getMatchedIntegrationConfig($customerIntegration, $currentUrlWithoutQueueITToken, KnownUser::getHttpRequestProvider());

            if ($connectorDiagnostics->isEnabled) {
                $dic = array("MatchedConfig" => (($matchedConfig != null) ? $matchedConfig["Name"] : "NULL"));
                KnownUser::updateDebugCookieDetails($dic);
            }

            $result = null;
            if ($matchedConfig == null) {
                $result = new RequestValidationResult(null, null, null, null, null, null);
            } else if (
                !array_key_exists("ActionType", $matchedConfig) ||
                $matchedConfig["ActionType"] == ActionTypes::QueueAction
            ) {
                $result = KnownUser::handleQueueAction(
                    $currentUrlWithoutQueueITToken,
                    $queueitToken,
                    $customerIntegration,
                    $customerId,
                    $secretKey,
                    $matchedConfig,
                    $connectorDiagnostics->isEnabled
                );
            } else if ($matchedConfig["ActionType"] == ActionTypes::CancelAction) {
                $result = KnownUser::handleCancelAction(
                    $currentUrlWithoutQueueITToken,
                    $queueitToken,
                    $customerIntegration,
                    $customerId,
                    $secretKey,
                    $matchedConfig,
                    $connectorDiagnostics->isEnabled
                );
            } else {
                $result = KnownUser::handleIgnoreAction($matchedConfig["Name"]);
            }
            KnownUser::sendDebugCookie();
            return $result;
        } catch (\Exception $e) {
            if ($connectorDiagnostics->isEnabled) {
                $dic = array("Exception" => $e->getMessage());
                KnownUser::updateDebugCookieDetails($dic);
                KnownUser::sendDebugCookie();
            }
            throw new KnownUserException($e->getMessage());
        }
    }

    public static function resolveQueueRequestByLocalConfig($targetUrl, $queueitToken, QueueEventConfig $queueConfig, $customerId, $secretKey)
    {
        $connectorDiagnostics = ConnectorDiagnostics::verify($customerId, $secretKey, $queueitToken);

        if ($connectorDiagnostics->hasError) {
            return $connectorDiagnostics->validationResult;
        }

        try {
            $targetUrl = KnownUser::generateTargetUrl($targetUrl);
            $result =  KnownUser::_resolveQueueRequestByLocalConfig($targetUrl, $queueitToken, $queueConfig, $customerId, $secretKey, $connectorDiagnostics->isEnabled);
            KnownUser::sendDebugCookie();
            return $result;
        } catch (\Exception $e) {
            if ($connectorDiagnostics->isEnabled) {
                $dic = array("Exception" => $e->getMessage());
                KnownUser::updateDebugCookieDetails($dic);
                KnownUser::sendDebugCookie();
            }
            throw $e;
        }
    }

    private static function _resolveQueueRequestByLocalConfig($targetUrl, $queueitToken, QueueEventConfig $queueConfig, $customerId, $secretKey, $isDebug)
    {
        if ($isDebug) {
            $dic = array(
                "TargetUrl" => $targetUrl,
                "SdkVersion" => UserInQueueService::getSDKVersion(),
                "RunTime" => KnownUser::getRuntime(),
                "QueueitToken" => $queueitToken,
                "QueueConfig" => $queueConfig != null ? $queueConfig->getString() : "NULL",
                "OriginalUrl" => KnownUser::getHttpRequestProvider()->getAbsoluteUri()
            );
            KnownUser::logMoreRequestDetails($dic);
            KnownUser::updateDebugCookieDetails($dic);
        }
        if ($queueConfig == null) {
            throw new KnownUserException("eventConfig can not be null.");
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
        $result = $userInQueueService->validateQueueRequest($targetUrl, $queueitToken, $queueConfig, $customerId, $secretKey);
        $result->isAjaxResult = KnownUser::isQueueAjaxCall();
        return $result;
    }

    private static function handleQueueAction(
        $currentUrlWithoutQueueITToken,
        $queueitToken,
        $customerIntegration,
        $customerId,
        $secretKey,
        $matchedConfig,
        $isDebug
    ) {
        $eventConfig = new QueueEventConfig();
        $targetUrl = "";
        $eventConfig->eventId = $matchedConfig["EventId"];
        $eventConfig->layoutName = $matchedConfig["LayoutName"];
        $eventConfig->culture = $matchedConfig["Culture"];
        $eventConfig->queueDomain = $matchedConfig["QueueDomain"];
        $eventConfig->extendCookieValidity = $matchedConfig["ExtendCookieValidity"];
        $eventConfig->cookieValidityMinute = $matchedConfig["CookieValidityMinute"];
        $eventConfig->cookieDomain = $matchedConfig["CookieDomain"];
        $eventConfig->isCookieHttpOnly =  array_key_exists("IsCookieHttpOnly", $matchedConfig) ? $matchedConfig["IsCookieHttpOnly"] : false;
        $eventConfig->isCookieSecure = array_key_exists("IsCookieSecure", $matchedConfig) ? $matchedConfig["IsCookieSecure"] : false;
        $eventConfig->version = $customerIntegration["Version"];
        $eventConfig->actionName = $matchedConfig["Name"];

        switch ($matchedConfig["RedirectLogic"]) {
            case "ForcedTargetUrl":
            case "ForecedTargetUrl":
                $targetUrl = $matchedConfig["ForcedTargetUrl"];
                break;
            case "EventTargetUrl":
                $targetUrl = "";
                break;
            default:
                $targetUrl = KnownUser::generateTargetUrl($currentUrlWithoutQueueITToken);
        }

        return KnownUser::_resolveQueueRequestByLocalConfig($targetUrl, $queueitToken, $eventConfig, $customerId, $secretKey, $isDebug);
    }

    private static function handleCancelAction(
        $currentUrlWithoutQueueITToken,
        $queueitToken,
        $customerIntegration,
        $customerId,
        $secretKey,
        $matchedConfig,
        $isDebug
    ) {
        $cancelEventConfig = new CancelEventConfig();
        $cancelEventConfig->queueDomain = $matchedConfig["QueueDomain"];
        $cancelEventConfig->eventId = $matchedConfig["EventId"];
        $cancelEventConfig->version = $customerIntegration["Version"];
        $cancelEventConfig->cookieDomain = $matchedConfig["CookieDomain"];
        $cancelEventConfig->isCookieHttpOnly = array_key_exists("IsCookieHttpOnly", $matchedConfig) ? $matchedConfig["IsCookieHttpOnly"] : false;
        $cancelEventConfig->isCookieSecure = array_key_exists("IsCookieSecure", $matchedConfig) ? $matchedConfig["IsCookieSecure"] : false;
        $cancelEventConfig->actionName = $matchedConfig["Name"];

        return KnownUser::_cancelRequestByLocalConfig($currentUrlWithoutQueueITToken, $queueitToken, $cancelEventConfig, $customerId, $secretKey, $isDebug);
    }

    private static function handleIgnoreAction($actionName)
    {
        $userInQueueService = KnownUser::getUserInQueueService();
        $result =  $userInQueueService->getIgnoreActionResult($actionName);
        $result->isAjaxResult = KnownUser::isQueueAjaxCall();
        return $result;
    }

    private static function logMoreRequestDetails(array &$debugInfos)
    {
        $allHeaders = KnownUser::getHttpRequestProvider()->getHeaderArray();
        $debugInfos["ServerUtcTime"] = gmdate("Y-m-d\TH:i:s\Z");
        $debugInfos["RequestIP"] = KnownUser::getHttpRequestProvider()->getUserHostAddress();
        $debugInfos["RequestHttpHeader_Via"] = array_key_exists('via', $allHeaders) ? $allHeaders['via'] : "";
        $debugInfos["RequestHttpHeader_Forwarded"] = array_key_exists('forwarded', $allHeaders) ? $allHeaders['forwarded'] : "";
        $debugInfos["RequestHttpHeader_XForwardedFor"] = array_key_exists('x-forwarded-for', $allHeaders) ? $allHeaders['x-forwarded-for'] : "";
        $debugInfos["RequestHttpHeader_XForwardedHost"] = array_key_exists('x-forwarded-host', $allHeaders) ? $allHeaders['x-forwarded-host'] : "";
        $debugInfos["RequestHttpHeader_XForwardedProto"] = array_key_exists('x-forwarded-proto', $allHeaders) ? $allHeaders['x-forwarded-proto'] : "";
    }

    private static function updateDebugCookieDetails(array $debugInfos)
    {
        if (KnownUser::$debugInfoArray != null) {
            foreach (KnownUser::$debugInfoArray as $key => $value) {
                if (!array_key_exists($key, $debugInfos)) {
                    $debugInfos[$key] = $value;
                }
            }
        }

        KnownUser::$debugInfoArray = $debugInfos;
    }

    private static function sendDebugCookie()
    {
        if (KnownUser::$debugInfoArray != null) {
            $cookieNameValues = array();
            foreach (KnownUser::$debugInfoArray as $key => $value) {
                array_push($cookieNameValues, $key . '=' . $value);
            }
            KnownUser::getHttpRequestProvider()->getCookieManager()->setCookie("queueitdebug", implode('|',  $cookieNameValues), 0, null, false, false);
        }
    }

    private static function generateTargetUrl($originalTargetUrl)
    {
        if (!KnownUser::isQueueAjaxCall()) {
            return $originalTargetUrl;
        }

        $headers = KnownUser::getHttpRequestProvider()->getHeaderArray();
        return urldecode($headers[KnownUser::QueueITAjaxHeaderKey]);
    }

    private static function isQueueAjaxCall()
    {
        return array_key_exists(
            KnownUser::QueueITAjaxHeaderKey,
            KnownUser::getHttpRequestProvider()->getHeaderArray()
        );
    }

    private static function getRuntime()
    {
        return phpversion();
    }
}

class CookieManager implements ICookieManager
{
    public function getCookie($cookieName)
    {
        if (array_key_exists($cookieName, $_COOKIE)) {
            return $_COOKIE[$cookieName];
        } else {
            return null;
        }
    }

    public function setCookie($name, $value, $expire, $domain, $isHttpOnly, $isSecure)
    {
        if ($domain == null) {
            $domain = "";
        }
        if ($value == null) {
            $value = "";
        }
        setcookie($name, $value, $expire, "/", $domain, $isSecure, $isHttpOnly);
    }

    public function getCookieArray()
    {
        $arryCookie = array();
        foreach ($_COOKIE as $key => $val) {
            $arryCookie[$key] = $val;
        }
        return $arryCookie;
    }
}

interface IHttpRequestProvider
{
    function getUserAgent();
    function getUserHostAddress();
    function getCookieManager();
    function getAbsoluteUri();
    function getHeaderArray();
    function getRequestBodyAsString();
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
        if ($this->cookieManager == null) {
            $this->cookieManager = new CookieManager();
        }
        return $this->cookieManager;
    }

    function getAbsoluteUri()
    {
        // Get HTTP/HTTPS (the possible values for this vary from server to server)
        $myUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && !in_array(strtolower($_SERVER['HTTPS']), array('off', 'no'))) ? 'https' : 'http';
        // Get domain portion
        $myUrl .= '://' . $_SERVER['HTTP_HOST'];
        // Get path to script
        $myUrl .= $_SERVER['REQUEST_URI'];
        // Add path info, if any
        if (!empty($_SERVER['PATH_INFO']))
            $myUrl .= $_SERVER['PATH_INFO'];

        return $myUrl;
    }

    function getHeaderArray()
    {
        if ($this->allHeadersLowerCaseKeyArray == null) {
            $tempArray = array();
            foreach (getallheaders() as $key => $value) {
                $tempArray[strtolower($key)] = $value;
            }
            $this->allHeadersLowerCaseKeyArray = $tempArray;
        }
        return $this->allHeadersLowerCaseKeyArray;
    }

    function getRequestBodyAsString()
    {        
        return '';
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
