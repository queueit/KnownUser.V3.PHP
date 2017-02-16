<?php 
namespace QueueIT\KnownUserV3\SDK;
require_once('Models.php');
require_once('UserInQueueService.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('IntegrationConfigHelpers.php');


class KnownUser
{
    
    public static function cancelQueueCookie(string $eventId)
    {
       if(empty($eventId))
            throw new KnownUserException("eventId can not be null or empty.");
        $userInQueueService = new UserInQueueService(new UserInQueueStateCookieRepository());
        $userInQueueService->cancelQueueCookie($eventId);
    }
    public static  function  extendQueueCookie(
            string $eventId,
            int $cookieValidityMinute,
            string $cookieDomain,
            string $secretKey)
    {
        if(empty($eventId))
            throw new KnownUserException("eventId can not be null or empty.");
        if(empty($secretKey))
            throw new KnownUserException("secretKey can not be null or empty.");
        $userInQueueService = new UserInQueueService(new UserInQueueStateCookieRepository() );
        $userInQueueService->extendQueueCookie($eventId, $cookieValidityMinute,$cookieDomain, $secretKey);
    }

    public static  function validateRequestByLocalEventConfig(
            string $targetUrl,
            string $queueitToken,
            EventConfig $eventConfig,
            string $customerId,
            string $secretKey):RequestValidationResult
    {
       if(empty($customerId))
            throw new KnownUserException("customerId can not be null or empty.");
        if(empty($secretKey))
            throw new KnownUserException("secretKey can not be null or empty.");

        if(empty($eventConfig->eventId))
            throw new KnownUserException("eventId  can not be null or empty.");
        if(empty($eventConfig->queueDomain))
            throw new KnownUserException("queueDomain  can not be null or empty.");

        if(!is_int($eventConfig->cookieValidityMinute) || intval($eventConfig->cookieValidityMinute)<=0)
            $eventConfig->cookieValidityMinute=10;

        $userInQueueService = new UserInQueueService(new UserInQueueStateCookieRepository());
       return $userInQueueService->validateRequest($targetUrl, $queueitToken,$eventConfig, $customerId, $secretKey);

    }
    public static function  validateRequestByIntegrationConfig(
            string $currentUrl,
            string $queueitToken,
            string $integrationsConfig,
            string $customerId,
            string $secretKey):RequestValidationResult
            {
                if(empty($currentUrl))
                    throw new KnownUserException("currentUrl can not be null or empty.");

                if(empty($integrationsConfig))
                    throw new KnownUserException("integrationsConfig can not be null or empty.");
                $integrationEvaluator = new IntegrationEvaluator(); 
                $integrationConfig = $integrationEvaluator->getMatchedIntegrationConfig(json_decode($integrationsConfig,true),$currentUrl);
                if($integrationConfig== null)
                {
                    return new RequestValidationResult();
                }

                $eventConfig = new EventConfig();
                $eventConfig->eventId = $integrationConfig["EventId"];
                
                $eventConfig->queueDomain = $integrationConfig["QueueDomain"];

                if(array_key_exists("LayoutName", $eventConfig)  )
                    $eventConfig->layoutName = $integrationConfig["LayoutName"];
                if(array_key_exists("Culture", $eventConfig)  )
                    $eventConfig->culture = $integrationConfig["Culture"];
                
                $eventConfig->cookieDomain = $integrationConfig["CookieDomain"];
                $eventConfig->extendCookieValidity = $integrationConfig["ExtendCookieValidity"] ;
                if(array_key_exists("CookieValidityMinute", $eventConfig)  )
                     $eventConfig->cookieValidityMinute =  $integrationConfig["CookieValidityMinute"];                         
                $eventConfig->version = $integrationConfig["Version"];
                $targetUrl = "";

         // var_dump($eventConfig);
         //  var_dump($integrationConfig);
                switch($integrationConfig["RedirectLogic"])
                {
                    case "ForecedTargetUrl" :
                        $targetUrl=$integrationConfig["ForcedTargetUrl"];
                        break;
                    case "EventTargetUrl":
                        $targetUrl="";
                        break;
                    default :
                        $targetUrl= $currentUrl;
                }
               //var_dump($currentUrl);
                
          //   var_dump($targetUrl);
                return KnownUser::validateRequestByLocalEventConfig($targetUrl, $queueitToken, $eventConfig, $customerId, $secretKey);
            }
}
?>