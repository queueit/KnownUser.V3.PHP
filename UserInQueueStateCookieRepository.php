<?php
namespace QueueIT\KnownUserV3\SDK;

interface IUserInQueueStateRepository 
{
    public function store($eventId, $queueId, $isStateExtendable, $cookieValidityMinute, $cookieDomain, $customerSecretKey);
    public function getState($eventId, $secretKey);
    public function cancelQueueCookie($eventId, $cookieDomain);
    public function extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $secretKey);
}

interface ICookieManager 
{
    public function setCookie($name, $value, $expire, $domain);
    public function getCookie($cookieName);
    public function getCookieArray();
}

class UserInQueueStateCookieRepository implements IUserInQueueStateRepository 
{
    const _QueueITDataKey = "QueueITAccepted-SDFrts345E-V3";
    private $cookieManager;

    function __construct(ICookieManager $cookieManager) {
        $this->cookieManager = $cookieManager;
    }

    public function cancelQueueCookie($eventId, $cookieDomain) {
        $cookieKey = self::getCookieKey($eventId);
        $this->cookieManager->setCookie($cookieKey, null, -1, $cookieDomain);
    }

    public static function getCookieKey($eventId) {
        return self::_QueueITDataKey . '_' . $eventId;
    }

    public function store($eventId, $queueId, $isStateExtendable, $cookieValidityMinute, $cookieDomain, $secretKey) {
        $cookieKey = self::getCookieKey($eventId);
        $expirationTime = strval(time() + ($cookieValidityMinute * 60));
        $isStateExtendableString = ($isStateExtendable) ? 'true' : 'false';
        $cookieValue = $this->createCookieValue($queueId, $isStateExtendableString, $expirationTime, $secretKey);
        $this->cookieManager->setCookie($cookieKey, $cookieValue, time() + (24 * 60 * 60), $cookieDomain);
    }

    private function createCookieValue($queueId, $isStateExtendable, $expirationTime, $secretKey) {
        $hashValue = hash_hmac('sha256', $queueId . $isStateExtendable . $expirationTime, $secretKey);
        $cookieValue = "QueueId=" . $queueId . "&IsCookieExtendable=" . $isStateExtendable . "&" . "Expires=" . $expirationTime . "&" . "Hash=" . $hashValue;

        return $cookieValue;
    }

    private function getCookieNameValueMap($cookieValue) {
        $result = array();
        $cookieNameValues = explode("&", $cookieValue);

        if (count($cookieNameValues) < 4) {
            return $result;
        }

        for ($i = 0; $i < 4; ++$i) {
            $arr = explode("=", $cookieNameValues[$i]);
            if (count($arr) == 2) {
                $result[$arr[0]] = $arr[1];
            }
        }

        return $result;
    }

    private function isCookieValid(array $cookieNameValueMap, $secretKey) {
        if (!array_key_exists("IsCookieExtendable",$cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("Expires",$cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("Hash",$cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("QueueId",$cookieNameValueMap)) {
            return false;
        }
        $hashValue = hash_hmac('sha256', $cookieNameValueMap["QueueId"] . $cookieNameValueMap["IsCookieExtendable"] . $cookieNameValueMap["Expires"], $secretKey);

        if ($hashValue !== $cookieNameValueMap["Hash"]) {
            return false;
        }

        if (intval($cookieNameValueMap["Expires"]) < time()) {
            return false;
        }

        return true;
    }

    public function extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $secretKey) {
        $cookieKey = self::getCookieKey($eventId);
        if ($this->cookieManager->getCookie($cookieKey) === null) {
            return;
        }
        $cookieNameValueMap = $this->getCookieNameValueMap($this->cookieManager->getCookie($cookieKey));
        if (!$this->isCookieValid($cookieNameValueMap, $secretKey)) {
            return;
        }

        $expirationTime = strval(time() + ( $cookieValidityMinute * 60));
        $cookieValue = $this->createCookieValue($cookieNameValueMap["QueueId"],$cookieNameValueMap["IsCookieExtendable"], $expirationTime, $secretKey);
        $this->cookieManager->setCookie($cookieKey, $cookieValue, time() + (24 * 60 * 60), $cookieDomain);
    }

    public function getState($eventId, $secretKey) {
        $cookieKey = self::getCookieKey($eventId);
        if ($this->cookieManager->getCookie($cookieKey) === null) {
            return new StateInfo(FALSE, null, FALSE,0);
        }
        $cookieNameValueMap = $this->getCookieNameValueMap($this->cookieManager->getCookie($cookieKey));

        if (!$this->isCookieValid($cookieNameValueMap, $secretKey)) {
            return new StateInfo(FALSE, null, FALSE,0);
        }
        return new StateInfo(
            TRUE, 
            $cookieNameValueMap["QueueId"], 
            $cookieNameValueMap["IsCookieExtendable"] === 'true',
            intval($cookieNameValueMap["Expires"]));
    }
}

class StateInfo 
{
    public $isValid;
    public $queueId;
    public $isStateExtendable;
    //used just for unit tests
    public $expires;

    public function __construct($isValid, $queueId, $isStateExtendable, $expires) {
        $this->isValid = $isValid;
        $this->queueId = $queueId;
        $this->isStateExtendable = $isStateExtendable;
        $this->expires  = $expires;
    }
}
