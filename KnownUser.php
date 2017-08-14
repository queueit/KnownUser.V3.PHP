<?php
namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');
require_once('UserInQueueService.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('IntegrationConfigHelpers.php');

class KnownUser 
{
    //used for unittest
    private static $userInQueueService = NULL;

    private static function getUserInQueueService() {
        if (KnownUser::$userInQueueService == NULL) 
        {
            return new UserInQueueService(new UserInQueueStateCookieRepository(new CookieManager()));
        }
        return KnownUser::$userInQueueService;
    }

    //used for unittest
    private static $httpRequestProvider= null;
    private static function getHttpRequestProvider() {
        if (KnownUser::$httpRequestProvider == NULL) 
        {
            return new HttpRequestProvider();
        }
        return KnownUser::$httpRequestProvider;
    }

    public static function cancelQueueCookie($eventId, $cookieDomain) {
        if (empty($eventId)) {
            throw new KnownUserException("eventId can not be null or empty.");
        }

        $userInQueueService = KnownUser::getUserInQueueService();
        $userInQueueService->cancelQueueCookie($eventId, $cookieDomain);
    }

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

    public static function validateRequestByLocalEventConfig($targetUrl, $queueitToken, EventConfig $eventConfig, $customerId, $secretKey) {
        if (empty($customerId)) {
            throw new KnownUserException("customerId can not be null or empty.");
        }

        if (empty($secretKey)) {
            throw new KnownUserException("secretKey can not be null or empty.");
        }

        if (empty($eventConfig->eventId)) {
            throw new KnownUserException("eventId can not be null or empty.");
        }

        if (empty($eventConfig->queueDomain)) {
            throw new KnownUserException("queueDomain can not be null or empty.");
        }

        if (!is_int($eventConfig->cookieValidityMinute) || intval($eventConfig->cookieValidityMinute) <= 0) {
            throw new KnownUserException("cookieValidityMinute should be integer greater than 0.");
        }

        if (!is_bool($eventConfig->extendCookieValidity)) {
            throw new KnownUserException("extendCookieValidity should be valid boolean.");
        }

        $userInQueueService = KnownUser::getUserInQueueService();
        return $userInQueueService->validateRequest($targetUrl, $queueitToken, $eventConfig, $customerId, $secretKey);
    }

    public static function validateRequestByIntegrationConfig($currentUrl, $queueitToken, $integrationsConfigString, $customerId, $secretKey) {
        if (empty($currentUrl)) {
            throw new KnownUserException("currentUrl can not be null or empty.");
        }

        if (empty($integrationsConfigString)) {
            throw new KnownUserException("integrationsConfigString can not be null or empty.");
        }

        $eventConfig = new EventConfig();
        $targetUrl = "";

        try {
            $integrationEvaluator = new IntegrationEvaluator();
            $customerIntegration = json_decode($integrationsConfigString, true);
            $integrationConfig = $integrationEvaluator->getMatchedIntegrationConfig($customerIntegration, $currentUrl,
                 KnownUser::getCookieArray(), KnownUser::getHttpRequestProvider()->getUserAgent());

            if ($integrationConfig == null) {
                return new RequestValidationResult(NULL, NULL, NULL);
            }         
            $eventConfig->eventId = $integrationConfig["EventId"];
            $eventConfig->queueDomain = $integrationConfig["QueueDomain"];
            $eventConfig->layoutName = $integrationConfig["LayoutName"];
            $eventConfig->culture = $integrationConfig["Culture"];
            $eventConfig->cookieDomain = $integrationConfig["CookieDomain"];
            $eventConfig->extendCookieValidity = $integrationConfig["ExtendCookieValidity"];
            $eventConfig->cookieValidityMinute = $integrationConfig["CookieValidityMinute"];
            $eventConfig->version = $customerIntegration["Version"];

            switch ($integrationConfig["RedirectLogic"]) {
                case "ForcedTargetUrl":
                case "ForecedTargetUrl":
                    $targetUrl = $integrationConfig["ForcedTargetUrl"];
                    break;
                case "EventTargetUrl":
                    $targetUrl = "";
                    break;
                default :
                    $targetUrl = $currentUrl;
            }
        } catch (\Exception $e) {
            throw new KnownUserException("integrationConfiguration text was not valid: ". $e->getMessage());
        }
        return KnownUser::validateRequestByLocalEventConfig($targetUrl, $queueitToken, $eventConfig, $customerId, $secretKey);
    }

    private static function getCookieArray() {
        $arryCookie = array();
        foreach ($_COOKIE as $key => $val) {
            $arryCookie[$key] = $val;
        }

        return $arryCookie;
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
}

class HttpRequestProvider implements IHttpRequestProvider
{
    function getUserAgent() {
        return array_key_exists ('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : "";
    }
}