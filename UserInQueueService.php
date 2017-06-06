<?php 
namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('QueueITHelpers.php');

interface IUserInQueueService
{
    public function validateRequest(
        $currentPageUrl,
        $queueitToken,
        EventConfig $config,
        $customerId,
        $secretKey);

    public function cancelQueueCookie($eventId, $cookieDomain);

    public function extendQueueCookie(
        $eventId,
        $cookieValidityMinute,
        $cookieDomain,
        $secretKey);       
}

class UserInQueueService implements IUserInQueueService
{
    const SDK_VERSION = "1.0.0.0";
    private $userInQueueStateRepository;

    function __construct(IUserInQueueStateRepository $userInQueueStateRepository) {
        $this->userInQueueStateRepository = $userInQueueStateRepository;
    }

    public function validateRequest(
        $targetUrl,
        $queueitToken,
        EventConfig $config,
        $customerId,
        $secretKey) {
        $state = $this->userInQueueStateRepository->getState($config->eventId, $secretKey);

        if ($state->isValid) {
            if ($state->isStateExtendable && $config->extendCookieValidity) {
                $this->userInQueueStateRepository->store(
                    $config->eventId,
                    $state->queueId,
                    true,
                    $config->cookieValidityMinute,
                    !Utils::isNullOrEmptyString($config->cookieDomain) ? $config->cookieDomain : '',
                    $secretKey);
            }
            $result =  new RequestValidationResult($config->eventId, $state->queueId, NULL);
            return $result;
        }

        if(!empty($queueitToken)) {
            $queueParams = QueueUrlParams::extractQueueParams($queueitToken);
            return $this->getQueueITTokenValidationResult($customerId, $targetUrl, $config->eventId, $secretKey, $config, $queueParams);
        } else {
            return $this->getInQueueRedirectResult($customerId, $targetUrl, $config);
        }
    }

    private function getQueueITTokenValidationResult(
         $customerId,
         $targetUrl,
         $eventId,
         $secretKey,
         EventConfig $config,
         QueueUrlParams $queueParams) {
        $calculatedHash = hash_hmac('sha256', $queueParams->queueITTokenWithoutHash, $secretKey);
        if (strtoupper($calculatedHash) != strtoupper($queueParams->hashCode)) {
            return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "hash");
        }

        if (strtoupper($queueParams->eventId) != strtoupper($eventId)) {
            return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "eventid");
        }

        if ($queueParams->timeStamp < time()) {
            return $this->getVaidationErrorResult($customerId, $targetUrl, $config, $queueParams, "timestamp");
        }

        $this->userInQueueStateRepository->store(
            $config->eventId,
            $queueParams->queueId,
            $queueParams->extendableCookie,
            !is_null( $queueParams->cookieValidityMinute) ? $queueParams->cookieValidityMinute : $config->cookieValidityMinute,
            !Utils::isNullOrEmptyString($config->cookieDomain) ? $config->cookieDomain : '',
            $secretKey);

            $result =  new RequestValidationResult($config->eventId, $queueParams->queueId, NULL);
            return $result;
    }

    private function getVaidationErrorResult(
        $customerId,
        $targetUrl,
        EventConfig $config,
        QueueUrlParams $qParams,
        $errorCode) {
        $query =$this->getQueryString($customerId, $config)
            ."&queueittoken=" .$qParams->queueITToken
            ."&ts=" . time()
            .(!empty($targetUrl) ? ("&t=". urlencode( $targetUrl)) : "");
        $domainAlias = $config->queueDomain;
        if (substr($domainAlias, -1) !== "/") {
            $domainAlias = $domainAlias . "/";
        }
        $redirectUrl = "https://". $domainAlias. "error/". $errorCode. "?" .$query;
        $result = new RequestValidationResult($config->eventId, null, $redirectUrl);

        return $result;
    }

    private function getInQueueRedirectResult($customerId, $targetUrl, EventConfig $config) {
        $redirectUrl = "https://". $config->queueDomain ."?" 
            .$this->getQueryString($customerId, $config)
            .(!empty($targetUrl) ? "&t=". urlencode( $targetUrl) : "");
        $result = new RequestValidationResult($config->eventId, null, $redirectUrl);

        return $result;
    }

    private function getQueryString($customerId, EventConfig $config) {
        $queryStringList = array();
        array_push($queryStringList,"c=".urlencode($customerId));
        array_push($queryStringList,"e=".urlencode($config->eventId));
        array_push($queryStringList,"ver=v3-php-".UserInQueueService::SDK_VERSION); 
        array_push($queryStringList,"cver=". (!is_null($config->version)?$config->version:'-1'));

        if (!Utils::isNullOrEmptyString($config->culture)) {
            array_push($queryStringList, "cid=" . urlencode($config->culture));
        }

        if (!Utils::isNullOrEmptyString($config->layoutName)) {
            array_push($queryStringList, "l=" . urlencode($config->layoutName));
        }

        return implode("&", $queryStringList);
    }

    public function cancelQueueCookie($eventId,$cookieDomain) {
        $this->userInQueueStateRepository->cancelQueueCookie($eventId, $cookieDomain);
    }

    public function ExtendQueueCookie(
        $eventId,
        $cookieValidityMinute,
        $cookieDomain,
        $secretKey) {
        $this->userInQueueStateRepository->extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $secretKey);
    }
}
