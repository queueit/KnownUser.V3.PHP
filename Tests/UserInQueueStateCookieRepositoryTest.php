<?php
#has already been included in TestSuite.php
#require __DIR__ . '/vendor/simpletest/simpletest/autorun.php';

require_once( __DIR__ . '/../UserInQueueStateCookieRepository.php');
error_reporting(E_ALL);

class UserInQueueStateCookieManagerMock implements QueueIT\KnownUserV3\SDK\ICookieManager 
{
    public $cookieList;
    public $setCookieCalls;
    public $getCookieCalls;

    function __construct() {
        $this->cookieList = array();
        $this->setCookieCalls = array();
        $this->getCookieCalls = array();
    }

    public function setCookie($cookieName, $value, $expire, $domain, $isHttpOnly, $isSecure) {
        if(is_null($value)){ $value = ""; }
        
        if(is_null($expire)){ $expire = 0; }
        
        if(is_null($domain)){ $domain = ""; }

        if(is_null($isHttpOnly)){ $isHttpOnly = false; }
    
        if(is_null($isSecure)){ $isSecure = false; }

        $this->cookieList[$cookieName] = array(
            "name" => $cookieName,
            "value" => $value,
            "expiration" => $expire,
            "cookieDomain" => $domain,
            "isHttpOnly" => $isHttpOnly,
            "isSecure" => $isSecure
        );
        $this->setCookieCalls[count($this->setCookieCalls)] = array(
            "name" => $cookieName,
            "value" => $value,
            "expiration" => $expire,
            "cookieDomain" => $domain,
            "isHttpOnly" => $isHttpOnly,
            "isSecure" => $isSecure);
    }

    public function getCookie($cookieName) {
        $this->getCookieCalls[count($this->getCookieCalls)] = $cookieName;
        if (!array_key_exists($cookieName, $this->cookieList)) {
            return null;
        }
        return $this->cookieList[$cookieName]["value"];
    }

    public function getCookieArray()
    {
        return array();
    }
}

class UserInQueueStateCookieRepositoryTest extends UnitTestCase 
{
    private function generateHash($eventId, $queueId, $fixedCookieValidityMinutes, $redirectType, $issueTime, $secretKey) {
        return hash_hmac('sha256', $eventId . $queueId . $fixedCookieValidityMinutes . $redirectType . $issueTime, $secretKey);
    }

    public function test_store_hasValidState_ExtendableCookie_CookieIsSaved() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = true;
        $isCookieSecure = true;
        $queueId = "queueId";
        $cookieValidity = 10;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, null, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
    
        $this->assertTrue($state->isValid);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->isStateExtendable());
        $this->assertTrue($state->redirectType === "Queue");
        $this->assertTrue(abs(intval($cookieManager->cookieList[$cookieKey]["expiration"]) - time() - 24 * 60 * 60) < 100);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["cookieDomain"] == $cookieDomain);
        $this->assertEqual($isCookieHttpOnly, $cookieManager->cookieList[$cookieKey]["isHttpOnly"]);
        $this->assertEqual($isCookieSecure, $cookieManager->cookieList[$cookieKey]["isSecure"]);
    }
    
    public function test_store_hasValidState_nonExtendableCookie_CookieIsSaved() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = true;
        $isCookieSecure = true;
        $queueId = "queueId";
        $cookieValidity = 3;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, $cookieValidity, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Idle", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
    
        $this->assertTrue($state->isValid);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertFalse($state->isStateExtendable());
        $this->assertTrue($state->redirectType === "Idle");
        $this->assertTrue($state->fixedCookieValidityMinutes === 3);
        $this->assertTrue(abs(intval($cookieManager->cookieList[$cookieKey]["expiration"]) - time() - 24 * 60 * 60) < 100);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["cookieDomain"] == $cookieDomain);
        $this->assertEqual($isCookieHttpOnly, $cookieManager->cookieList[$cookieKey]["isHttpOnly"]);
        $this->assertEqual($isCookieSecure, $cookieManager->cookieList[$cookieKey]["isSecure"]);
    }

    public function test_store_hasValidState_tamperedCookie_stateIsNotValid_isCookieExtendable() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieValidity = 10;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, 3, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Idle", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue($state->isValid);
    
        $oldCookieValue = $cookieManager->cookieList[$cookieKey]["value"];
        $cookieManager->cookieList[$cookieKey]["value"] = str_replace("FixedValidityMins=3", "FixedValidityMins=10", $oldCookieValue);
        $state2 = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertFalse($state2->isValid);
        $this->assertFalse($state->isStateExtendable());
    }

    public function test_store_hasValidState_tamperedCookie_stateIsNotValid_eventId() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieValidity = 10;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, 3, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Idle", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue($state->isValid);
    
        $oldCookieValue = $cookieManager->cookieList[$cookieKey]["value"];
        $cookieManager->cookieList[$cookieKey]["value"] = str_replace("EventId=event1", "EventId=event2", $oldCookieValue);
        $state2 = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertFalse($state2->isValid);
        $this->assertFalse($state->isStateExtendable());
    }
    
    public function test_store_hasValidState_expiredCookie_stateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieValidity = -1;

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, null, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Idle", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertFalse($state->isValid);
    }
    
    public function test_store_hasValidState_differentEventId_stateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieValidity = 10;	

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, null, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue($state->isValid);
    
        $state2 = $testObject->getState("event2", $cookieValidity, $secretKey, true);
        $this->assertTrue(!$state2->isValid);
    }
    
    public function test_hasValidState_noCookie_stateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";
        $cookieKey = "key";
        $cookieValidity = 10;

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertFalse($state->isValid);	
    }

    public function test_hasValidState_invalidCookie_stateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
        $cookieValidity = 10;

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
    
        $testObject->store($eventId, $queueId, 20, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue($state->isValid);
    
        $cookieManager->cookieList[$cookieKey]["value"] = "IsCookieExtendable=ooOOO&Expires=|||&QueueId=000&Hash=23232$$$";
        $state2 = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertFalse($state2->isValid);
    }
    
    public function test_cancelQueueCookie() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieValidity = 20;

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store($eventId, $queueId, 20, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $state = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue($state->isValid);
    
        $testObject->cancelQueueCookie($eventId, $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
        $state2 = $testObject->getState($eventId, $cookieValidity, $secretKey, true);
        $this->assertTrue(!$state2->isValid);
    
        $this->assertTrue(intval($cookieManager->setCookieCalls[1]["expiration"]) == -1);
        $this->assertTrue($cookieManager->setCookieCalls[1]["cookieDomain"] == $cookieDomain);
        $this->assertTrue($cookieManager->setCookieCalls[1]["value"] == null);
    }
    
    public function test_extendQueueCookie_cookieExist() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = true;
        $isCookieSecure = true;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store($eventId, $queueId, null, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $testObject->reissueQueueCookie($eventId, 12, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey);
    
        $state = $testObject->getState($eventId, 5, $secretKey, true);
        $this->assertTrue($state->isValid);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->isStateExtendable());
        $this->assertTrue(abs(intval($cookieManager->cookieList[$cookieKey]["expiration"]) - time() - 24 * 60 * 60) < 100);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["cookieDomain"] == $cookieDomain);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["isHttpOnly"] == $isCookieHttpOnly);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["isSecure"] == $isCookieSecure);
    }

    public function test_extendQueueCookie_cookieDoesNotExist() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
    
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store("event2", $queueId, 20, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, "Queue", $secretKey);
        $testObject->reissueQueueCookie($eventId, 12, $cookieDomain, $isCookieHttpOnly, $isCookieSecure, $secretKey);
        $this->assertTrue(count($cookieManager->setCookieCalls) == 1);
    }

    public function test_getState_validCookieFormat_extendable() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
        $issueTime = time();
        $hash = $this->generateHash($eventId, $queueId, null, "queue", $issueTime, $secretKey);

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        
        $cookieValue = "EventId=" . $eventId .
                        "&QueueId=" . $queueId .
                        "&RedirectType=queue" .
                        "&IssueTime=". $issueTime .
                        "&Hash=" . $hash;

        $cookieManager->setCookie($cookieKey, $cookieValue, time() + (24*60*60), $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
        $state = $testObject->getState($eventId, 10, $secretKey, true);

        $this->assertTrue($state->isStateExtendable());
        $this->assertTrue($state->isValid);
        $this->assertTrue($state->isFound);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->redirectType == "queue");
    }

    public function test_getState_oldCookie_invalid_expiredCookie_extendable() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
        $issueTime = time() - (11*60);
        $hash = $this->generateHash($eventId, $queueId, null, "queue", $issueTime, $secretKey);

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        
        $cookieValue = "EventId=" . $eventId .
        "&QueueId=" . $queueId .
        "&RedirectType=queue" .
        "&IssueTime=" . $issueTime .
        "&Hash=".$hash;

        $cookieManager->setCookie($cookieKey, $cookieValue, time() + (24*60*60), $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
        $state = $testObject->getState($eventId, 10, $secretKey, true);

        $this->assertFalse($state->isValid);
        $this->assertTrue($state->isFound);
    }
    
    public function test_getState_oldCookie_invalid_expiredCookie_nonExtendable() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
        $issueTime = time() - (4*60);
        $hash = $this->generateHash($eventId, $queueId, 3, "idle", $issueTime, $secretKey);

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        
        $cookieValue = "EventId=" . $eventId .
                        "&QueueId=" . $queueId .
                        "&FixedValidityMins=3".
                        "&RedirectType=idle" .
                        "&IssueTime=" . $issueTime .
                        "&Hash=" . $hash;

        $cookieManager->setCookie($cookieKey, $cookieValue, time() + (24*60*60), $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
        $state = $testObject->getState($eventId, 10, $secretKey, true);

        $this->assertFalse($state->isValid);
        $this->assertTrue($state->isFound);
    }

    public function test_getState_validCookieFormat_nonExtendable() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $isCookieHttpOnly = false;
        $isCookieSecure = false;
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);
        $issueTime = time();
        $hash = $this->generateHash($eventId, $queueId, 3, "idle", $issueTime, $secretKey);

        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        
        $cookieValue = "EventId=" . $eventId .
                        "&QueueId=" . $queueId .
                        "&FixedValidityMins=3" .
                        "&RedirectType=idle" .
                        "&IssueTime=" . $issueTime .
                        "&Hash=" . $hash;

        $cookieManager->setCookie($cookieKey, $cookieValue, time() + (24*60*60), $cookieDomain, $isCookieHttpOnly, $isCookieSecure);
        $state = $testObject->getState($eventId, 10, $secretKey, true);

        $this->assertFalse($state->isStateExtendable());
        $this->assertTrue($state->isValid);
        $this->assertTrue($state->isFound);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->redirectType == "idle");
    }
    
    public function test_getState_NoCookie() {
        $cookieManager = new UserInQueueStateCookieManagerMock();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        
        $state = $testObject->getState($eventId, 10, $secretKey, true);

        $this->assertFalse($state->isFound);
        $this->assertFalse($state->isValid);
    }
}