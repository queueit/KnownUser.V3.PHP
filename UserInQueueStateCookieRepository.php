<?php
namespace QueueIT\KnownUserV3\SDK;

require_once('Models.php');

interface IUserInQueueStateRepository 
{
    public function store($eventId, $queueId, $fixedCookieValidityMinutes, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $redirectType, $secretKey);
    public function getState($eventId, $cookieValidityMinutes, $secretKey, $validateTime);
    public function cancelQueueCookie($eventId, $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
    public function reissueQueueCookie($eventId, $cookieValidityMinutes, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey);
}

interface ICookieManager 
{
    public function setCookie($name, $value, $expire, $domain, $isCookieHttpOnly, $isCookieSecure);
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

    public function cancelQueueCookie($eventId, $cookieDomain, $isCookieHttpOnly, $isCookieSecure) {
        $cookieKey = self::getCookieKey($eventId);
        $this->cookieManager->setCookie($cookieKey, null, -1, $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
    }

    public static function getCookieKey($eventId) {
        return self::_QueueITDataKey . '_' . $eventId;
    }

    public function store($eventId, $queueId, $fixedCookieValidityMinutes, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $redirectType, $secretKey) {
        $cookieKey = self::getCookieKey($eventId);
        $cookieValue = $this->createCookieValue($eventId, $queueId, strval($fixedCookieValidityMinutes), $redirectType, $secretKey);
        $this->cookieManager->setCookie($cookieKey, $cookieValue, time() + (24 * 60 * 60), $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
    }

    private function createCookieValue($eventId, $queueId, $fixedCookieValidityMinutes, $redirectType, $secretKey) {
        $issueTime = time();
        $hashValue = $this->generateHash($eventId, $queueId, $fixedCookieValidityMinutes, $redirectType, $issueTime, $secretKey);
        
        $fixedCookieValidityMinutesPart = "";
        if(!Utils::isNullOrEmptyString($fixedCookieValidityMinutes)) {
            $fixedCookieValidityMinutesPart = "&FixedValidityMins=" . $fixedCookieValidityMinutes;
        }
        
        $cookieValue = "EventId=" . $eventId . "&QueueId=" . $queueId . $fixedCookieValidityMinutesPart . "&RedirectType=" . $redirectType . "&IssueTime=" . $issueTime . "&Hash=" . $hashValue;		
        return $cookieValue;
    }

    private function getCookieNameValueMap($cookieValue) {
        $result = array();
        $cookieNameValues = explode("&", $cookieValue);
        $length = count($cookieNameValues);

        for ($i = 0; $i < $length; ++$i) {
            $arr = explode("=", $cookieNameValues[$i]);
            if (count($arr) == 2) {
                $result[$arr[0]] = $arr[1];
            }
        }

        return $result;
    }

    private function generateHash($eventId, $queueId, $fixedCookieValidityMinutes, $redirectType, $issueTime, $secretKey) {
        return hash_hmac('sha256', $eventId . $queueId . $fixedCookieValidityMinutes . $redirectType . $issueTime, $secretKey);
    }

    private function isCookieValid($secretKey, array $cookieNameValueMap, $eventId, $cookieValidityMinutes, $validateTime) {
        if (!array_key_exists("EventId", $cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("QueueId", $cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("RedirectType", $cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("IssueTime", $cookieNameValueMap)) {
            return false;
        }
        if (!array_key_exists("Hash", $cookieNameValueMap)) {
            return false;
        }

        $fixedCookieValidityMinutes = "";
        if (array_key_exists("FixedValidityMins", $cookieNameValueMap)) {
            $fixedCookieValidityMinutes = $cookieNameValueMap["FixedValidityMins"];
        }

        $hashValue = $this->generateHash(
            $cookieNameValueMap["EventId"], 
            $cookieNameValueMap["QueueId"],
            $fixedCookieValidityMinutes,
            $cookieNameValueMap["RedirectType"],
            $cookieNameValueMap["IssueTime"],
            $secretKey);

        if ($hashValue !== $cookieNameValueMap["Hash"]) {
            return false;
        }

        if(strtolower($eventId) !== strtolower($cookieNameValueMap["EventId"])) {
            return false;
        }     

        if($validateTime) {
            $validity = $cookieValidityMinutes;
            if(!Utils::isNullOrEmptyString($fixedCookieValidityMinutes)) {
                $validity = intval($fixedCookieValidityMinutes);
            }

            $expirationTime = $cookieNameValueMap["IssueTime"] + ($validity*60);
            if($expirationTime < time()) {
                return false;
            }
        }

        return true;
    }

    public function reissueQueueCookie($eventId, $cookieValidityMinutes, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey) {
        $cookieKey = self::getCookieKey($eventId);
        if ($this->cookieManager->getCookie($cookieKey) === null) {
            return;
        }
        $cookieNameValueMap = $this->getCookieNameValueMap($this->cookieManager->getCookie($cookieKey));
        if (!$this->isCookieValid($secretKey, $cookieNameValueMap, $eventId, $cookieValidityMinutes, true)) {
            return;
        }
        $fixedCookieValidityMinutes = "";
        if (array_key_exists("FixedValidityMins", $cookieNameValueMap)) {
            $fixedCookieValidityMinutes = $cookieNameValueMap["FixedValidityMins"];
        }

        $cookieValue = $this->createCookieValue(
            $eventId, 
            $cookieNameValueMap["QueueId"], 
            $fixedCookieValidityMinutes, 
            $cookieNameValueMap["RedirectType"], 
            $secretKey);

        $this->cookieManager->setCookie(
            $cookieKey,
            $cookieValue,
            time() + (24 * 60 * 60),
            $cookieDomain,
            $isCookieHttpOnly,
            $isCookieSecure);
    }

    public function getState($eventId, $cookieValidityMinutes, $secretKey, $validateTime) {
        try{
            $cookieKey = self::getCookieKey($eventId);
            if ($this->cookieManager->getCookie($cookieKey) === null) {
                return new StateInfo(false, false, null, null, null);
            }
            $cookieNameValueMap = $this->getCookieNameValueMap($this->cookieManager->getCookie($cookieKey));

            if (!$this->isCookieValid($secretKey, $cookieNameValueMap, $eventId, $cookieValidityMinutes, $validateTime)) {
                return new StateInfo(true, false, null, null, null);
            }

            $fixedCookieValidityMinutes = null;
            if (array_key_exists("FixedValidityMins", $cookieNameValueMap)) {
                $fixedCookieValidityMinutes = intval($cookieNameValueMap["FixedValidityMins"]);
            }

            return new StateInfo(
                true,
                true, 
                $cookieNameValueMap["QueueId"],
                $fixedCookieValidityMinutes,
                $cookieNameValueMap["RedirectType"]
            );
        } catch(\Exception $e){
            return new StateInfo(true, false, null, null, null);
        }
    }
}

class StateInfo 
{
    public $isFound;
    public $isValid;
    public $queueId;
    public $fixedCookieValidityMinutes;
    public $redirectType;

    public function __construct($isFound, $isValid, $queueId, $fixedCookieValidityMinutes, $redirectType) {
        $this->isFound = $isFound;
        $this->isValid = $isValid;
        $this->queueId = $queueId;
        $this->fixedCookieValidityMinutes = $fixedCookieValidityMinutes;
        $this->redirectType = $redirectType;        
    }

    public function isStateExtendable() {
        return $this->isValid && $this->fixedCookieValidityMinutes === null;
    }
}
