<?php

namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');
require_once('UserInQueueStateCookieRepository.php');
require_once('QueueITHelpers.php');

interface IUserInQueueService
{
    public function validateQueueRequest(
        $currentPageUrl,
        $queueitToken,
        QueueEventConfig $config,
        $customerId,
        $secretKey
    );

    public function validateCancelRequest(
        $targetUrl,
        CancelEventConfig $cancelConfig,
        $customerId,
        $secretKey
    );

    public function extendQueueCookie(
        $eventId,
        $cookieValidityMinutes,
        $cookieDomain,
        $secretKey
    );

    public function getIgnoreActionResult(
        $actionName
    );
}

class UserInQueueService implements IUserInQueueService
{
    public static function getSDKVersion()
    {
        return "v3-php-" . "3.6.0";
    }

    private $userInQueueStateRepository;

    function __construct(IUserInQueueStateRepository $userInQueueStateRepository)
    {
        $this->userInQueueStateRepository = $userInQueueStateRepository;
    }

    public function validateQueueRequest(
        $targetUrl,
        $queueitToken,
        QueueEventConfig $config,
        $customerId,
        $secretKey
    ) {
        $state = $this->userInQueueStateRepository->getState($config->eventId, $config->cookieValidityMinute, $secretKey, true);

        if ($state->isValid) {
            if ($state->isStateExtendable() && $config->extendCookieValidity) {
                $this->userInQueueStateRepository->store(
                    $config->eventId,
                    $state->queueId,
                    null,
                    !Utils::isNullOrEmptyString($config->cookieDomain) ? $config->cookieDomain : '',
                    $state->redirectType,
                    $secretKey
                );
            }
            $result = new RequestValidationResult(
                ActionTypes::QueueAction,
                $config->eventId,
                $state->queueId,
                null,
                $state->redirectType,
                $config->actionName
            );

            return $result;
        }

        if (!Utils::isNullOrEmptyString($queueitToken)) {
            $queueParams = QueueUrlParams::extractQueueParams($queueitToken);
            return $this->getQueueITTokenValidationResult($customerId, $targetUrl, $secretKey, $config, $queueParams);
        } else {
            return $this->cancelQueueCookieReturnQueueResult($customerId, $targetUrl, $config);
        }
    }

    private function getQueueITTokenValidationResult(
        $customerId,
        $targetUrl,
        $secretKey,
        QueueEventConfig $config,
        QueueUrlParams $queueParams
    ) {
        $calculatedHash = hash_hmac('sha256', $queueParams->queueITTokenWithoutHash, $secretKey);

        if (strtoupper($calculatedHash) != strtoupper($queueParams->hashCode)) {
            return $this->cancelQueueCookieReturnErrorResult($customerId, $targetUrl, $config, $queueParams, "hash");
        }

        if (strtoupper($queueParams->eventId) != strtoupper($config->eventId)) {
            return $this->cancelQueueCookieReturnErrorResult($customerId, $targetUrl, $config, $queueParams, "eventid");
        }

        if ($queueParams->timeStamp < time()) {
            return $this->cancelQueueCookieReturnErrorResult($customerId, $targetUrl, $config, $queueParams, "timestamp");
        }

        $this->userInQueueStateRepository->store(
            $config->eventId,
            $queueParams->queueId,
            $queueParams->cookieValidityMinutes,
            !Utils::isNullOrEmptyString($config->cookieDomain) ? $config->cookieDomain : '',
            $queueParams->redirectType,
            $secretKey
        );

        $result = new RequestValidationResult(
            ActionTypes::QueueAction,
            $config->eventId,
            $queueParams->queueId,
            null,
            $queueParams->redirectType,
            $config->actionName
        );

        return $result;
    }

    private function cancelQueueCookieReturnErrorResult(
        $customerId,
        $targetUrl,
        QueueEventConfig $config,
        QueueUrlParams $qParams,
        $errorCode
    ) {
        $this->userInQueueStateRepository->cancelQueueCookie($config->eventId, $config->cookieDomain);

        $query = $this->getQueryString($customerId, $config->eventId, $config->version, $config->culture, $config->layoutName, $config->actionName)
            . "&queueittoken=" . $qParams->queueITToken
            . "&ts=" . time()
            . (!Utils::isNullOrEmptyString($targetUrl) ? ("&t=" . rawurlencode($targetUrl)) : "");

        $uriPath = "error/" . $errorCode . "/";

        $redirectUrl = $this->generateRedirectUrl($config->queueDomain, $uriPath, $query);

        $result = new RequestValidationResult(
            ActionTypes::QueueAction,
            $config->eventId,
            null,
            $redirectUrl,
            null,
            $config->actionName
        );

        return $result;
    }

    private function cancelQueueCookieReturnQueueResult(
        $customerId,
        $targetUrl,
        QueueEventConfig $config
    ) {
        $this->userInQueueStateRepository->cancelQueueCookie($config->eventId, $config->cookieDomain);

        $query = $this->getQueryString($customerId, $config->eventId, $config->version, $config->culture, $config->layoutName, $config->actionName) .
            (!Utils::isNullOrEmptyString($targetUrl) ? "&t=" . rawurlencode($targetUrl) : "");

        $redirectUrl = $this->generateRedirectUrl($config->queueDomain, "", $query);

        $result = new RequestValidationResult(
            ActionTypes::QueueAction,
            $config->eventId,
            null,
            $redirectUrl,
            null,
            $config->actionName
        );

        return $result;
    }

    private function getQueryString(
        $customerId,
        $eventId,
        $configVersion,
        $culture,
        $layoutName,
        $actionName
    ) {
        $queryStringList = array();
        array_push($queryStringList, "c=" . rawurlencode($customerId));
        array_push($queryStringList, "e=" . rawurlencode($eventId));
        array_push($queryStringList, "ver=" . UserInQueueService::getSDKVersion());
        array_push($queryStringList, "cver=" . (!is_null($configVersion) ? $configVersion : '-1'));
        array_push($queryStringList, "man=" . rawurlencode($actionName));

        if (!Utils::isNullOrEmptyString($culture)) {
            array_push($queryStringList, "cid=" . rawurlencode($culture));
        }

        if (!Utils::isNullOrEmptyString($layoutName)) {
            array_push($queryStringList, "l=" . rawurlencode($layoutName));
        }

        return implode("&", $queryStringList);
    }

    private function generateRedirectUrl($queueDomain, $uriPath, $query)
    {
        if (substr($queueDomain, -1) !== "/")
            $queueDomain = $queueDomain . "/";

        return "https://" . $queueDomain . $uriPath . "?" . $query;
    }

    public function extendQueueCookie(
        $eventId,
        $cookieValidityMinutes,
        $cookieDomain,
        $secretKey
    ) {
        $this->userInQueueStateRepository->reissueQueueCookie($eventId, $cookieValidityMinutes, $cookieDomain, $secretKey);
    }

    public function validateCancelRequest($targetUrl, CancelEventConfig $cancelConfig, $customerId, $secretKey)
    {
        //we do not care how long cookie is valid while canceling cookie
        $state = $this->userInQueueStateRepository->getState($cancelConfig->eventId, -1, $secretKey, false);

        if ($state->isValid) {
            $this->userInQueueStateRepository->cancelQueueCookie($cancelConfig->eventId, $cancelConfig->cookieDomain);

            $query = $this->getQueryString($customerId, $cancelConfig->eventId, $cancelConfig->version, null, null, $cancelConfig->actionName)
                . (!Utils::isNullOrEmptyString($targetUrl) ? ("&r=" . rawurlencode($targetUrl)) : "");


            $uriPath = "cancel/" . $customerId . "/" . $cancelConfig->eventId . "/";

            $redirectUrl = $this->generateRedirectUrl($cancelConfig->queueDomain, $uriPath, $query);

            return new RequestValidationResult(
                ActionTypes::CancelAction,
                $cancelConfig->eventId,
                $state->queueId,
                $redirectUrl,
                $state->redirectType,
                $cancelConfig->actionName
            );
        } else {
            return new RequestValidationResult(
                ActionTypes::CancelAction,
                $cancelConfig->eventId,
                null,
                null,
                null,
                $cancelConfig->actionName
            );
        }
    }

    public function getIgnoreActionResult($actionName)
    {
        return new RequestValidationResult(
            ActionTypes::IgnoreAction,
            null,
            null,
            null,
            null,
            $actionName
        );
    }
}
