<?php 
namespace QueueIT\KnownUserV3\SDK;
require_once('Models.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('QueueITHelpers.php');


interface IUserInQueueService
{
     public function validateRequest(
        string $currentPageUrl,
        string $queueitToken,
        EventConfig $config,
        string $customerId,
        string $secretKey):RequestValidationResult;


    public function cancelQueueCookie(string $eventId):void;
    public function extendQueueCookie(
        string $eventId,
        int $cookieValidityMinute,
         string $cookieDomain,
        string $secretKey
        ):void;
        

}
class UserInQueueService implements IUserInQueueService
{
    const CodeVersion= "1";
    
    function __construct ( IUserInQueueStateRepository $userInQueueStateRepository)
                            {
                                $this->userInQueueStateRepository= $userInQueueStateRepository;
                            }
    private $userInQueueStateRepository;


    public function  validateRequest(
            string $targetUrl,
            string $queueitToken,
            EventConfig $config,
            string $customerId,
            string $secretKey):RequestValidationResult
        {
            if ($this->userInQueueStateRepository->hasValidState($config->eventId, $secretKey))
            {
                if ($this->userInQueueStateRepository->isStateExtendable($config->eventId,$secretKey)
                    && $config->extendCookieValidity)
                {
                
                    $this->userInQueueStateRepository->store($config->eventId,
                        true,
                         $config->cookieValidityMinute,
                       isset($config->cookieDomain)?$config->cookieDomain:'',
                        $secretKey);
                }
                $result =  new RequestValidationResult();
                $result->eventId= $config->eventId;
                return $result;
            }
            if(!empty($queueitToken))
            {
                  
                $queueParams = QueueUrlParams::extractQueueParams($queueitToken);
                return $this->getQueueITTokenValidationResult($customerId,$targetUrl, $config->eventId, $secretKey, $config, $queueParams);
            }
            else
            {
                return $this->getInQueueRedirectResult($customerId, $targetUrl, $config);
            }
        }

       private  function getQueueITTokenValidationResult(
            string $customerId,
            string $targetUrl,
            string $eventId,
            string $secretKey,
            EventConfig $config,
            QueueUrlParams $queueParams):RequestValidationResult
        {
         
            $calculatedHash = hash_hmac('sha256',$queueParams->queueITTokenWithoutHash , $secretKey);
            if ($calculatedHash != $queueParams->hashCode)
                return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "hash");
     
            if ($queueParams->eventId != $eventId)
                return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "eventid");

            if ($queueParams->timeStamp < time())
                return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "timestamp");

            $this->userInQueueStateRepository->store(
                $config->eventId,
                $queueParams->extendableCookie,
                isset( $queueParams->cookieValidityMinute) ? $queueParams->cookieValidityMinute: $config->cookieValidityMinute,
                isset($config->cookieDomain)?$config->cookieDomain:'',
                
                $secretKey);

            $result =  new RequestValidationResult();
            $result->eventId= $config->eventId;
            return $result;
        }

        private  function getVaidationErrorResult(
            string $customerId,
             string $targetUrl,
             EventConfig $config,
             QueueUrlParams $qParams,
             string $errorCode):RequestValidationResult
        {
            $query =$this->getQueryString($customerId,  $config)
                ."&queueittoken=" .$qParams->queueITToken
                ."&ts=" . time()
                 .(!empty($targetUrl)? ("&t=". urlencode( $targetUrl)):"");
            $domainAlias = $config->queueDomain;
            if(substr($domainAlias, -1) !== "/");
                $domainAlias =$domainAlias . "/";
            $redirectUrl = "https://". $domainAlias. "error/". $errorCode. "?" .$query;
            $result =  new RequestValidationResult();
            $result->redirectUrl = $redirectUrl;
            $result->eventId = $config->eventId;
            return $result;
        }
        private function getInQueueRedirectResult(string $customerId,string $targetUrl, EventConfig $config)
        {
            $redirectUrl = "https://". $config->queueDomain ."?" 
                .$this->getQueryString($customerId, $config)
                .(!empty($targetUrl)? "&t=". urlencode( $targetUrl):"");
            $result =  new RequestValidationResult();
            $result->redirectUrl = $redirectUrl;
            $result->eventId= $config->eventId;
            
            return $result;
        }

      function getQueryString(
            string $customerId,
            EventConfig $config):string
        {
         
            $queryStringList = array();
            array_push($queryStringList,"c=".urlencode($customerId));
            array_push($queryStringList,"e=".urlencode($config->eventId));
            array_push($queryStringList,"ver=v3-php-".UserInQueueService::CodeVersion); 
            array_push($queryStringList,"cver=". (isset($config->version)?$config->version:'-1'));

            if (isset($config->culture))
                array_push($queryStringList,"cid=".urlencode($config->culture));

            if (isset($config->layoutName))
                array_push($queryStringList,"l=".urlencode($config->layoutName));


            return implode("&", $queryStringList);
        }

        public function cancelQueueCookie(string $eventId):void
        {
            $this->userInQueueStateRepository->cancelQueueCookie($eventId);
        }
        public function  ExtendQueueCookie(
            string $eventId,
            int $cookieValidityMinute,
             string $cookieDomain,
            string $secretKey):void
        {
            $this->userInQueueStateRepository->extendQueueCookie($eventId, $cookieValidityMinute,$cookieDomain, $secretKey);
        }
}
   
?>