<?php
require __DIR__ . '/vendor/simpletest/simpletest/autorun.php';
require_once( __DIR__ . '/../UserInQueueStateCookieRepository.php');
error_reporting(E_ALL);

class CookieManagerMockClass implements QueueIT\KnownUserV3\SDK\ICookieManager 
{
    public $cookieList;
    public $setCookieCalls;
    public $getCookieCalls;

    function __construct() {
        $this->cookieList = array();
        $this->setCookieCalls = array();
        $this->getCookieCalls = array();
    }

    public function setCookie($cookieName, $value, $expire, $domain) {
        $this->cookieList[$cookieName] = array(
            "name" => $cookieName,
            "value" => $value,
            "expiration" => $expire,
            "cookieDomain" => $domain
        );
        $this->setCookieCalls[count($this->setCookieCalls)] = array("name" => $cookieName,
            "value" => $value,
            "expiration" => $expire,
            "cookieDomain" => $domain);
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
    public function test_store_getState_ExtendableCookie_CookieIsSaved() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";
        $cookieValidity = 10;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $testObject->store($eventId, $queueId, true, $cookieValidity, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);

        $this->assertTrue($state->isValid);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->isStateExtendable);
        $this->assertTrue(abs(time() + 10 * 60 - $state->expires) < 100);
        $this->assertTrue(abs(intval($cookieManager->cookieList[$cookieKey]["expiration"]) - time() - 24 * 60 * 60) < 100);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["cookieDomain"] == $cookieDomain);
    }

    public function test_store_getState_TamperedCookie_StateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";
        $cookieValidity = 10;
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $testObject->store($eventId, $queueId, false, $cookieValidity, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);
        $this->assertTrue($state->isValid);

        $oldCookieValue = $cookieManager->cookieList[$cookieKey]["value"];
        $cookieManager->cookieList[$cookieKey]["value"] = str_replace("IsCookieExtendable=false", "IsCookieExtendable=true", $oldCookieValue);
        $state2 = $testObject->getState($eventId, $secretKey);
        $this->assertTrue(!$state2->isValid);
    }

    public function test_store_getState_ExpiredCookie_StateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $testObject->store($eventId, $queueId, true, -1, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);
        $this->assertFalse($state->isValid);
    }

    public function test_store_getState_DifferentEventId_StateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $testObject->store($eventId, $queueId, true, 10, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);
        $this->assertTrue($state->isValid);

        $state2 = $testObject->getState("event2", $secretKey);
        $this->assertTrue(!$state2->isValid);
    }

    public function test_store_getState_InvalidCookie_StateIsNotValid() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);


        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);

        $testObject->store($eventId, $queueId, true, 20, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);
        $this->assertTrue($state->isValid);

        $cookieManager->cookieList[$cookieKey]["value"] = "IsCookieExtendable=ooOOO&Expires=|||&QueueId=000&Hash=23232$$$";
        $state2 = $testObject->getState($eventId, $secretKey);
        $this->assertFalse($state2->isValid);
    }

    public function test_cancelQueueCookie_Test() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store($eventId, $queueId, true, 20, $cookieDomain, $secretKey);
        $state = $testObject->getState($eventId, $secretKey);
        $this->assertTrue($state->isValid);

        $testObject->cancelQueueCookie($eventId, $cookieDomain);
        $state2 = $testObject->getState($eventId, $secretKey);
        $this->assertTrue(!$state2->isValid);

        $this->assertTrue(intval($cookieManager->setCookieCalls[1]["expiration"]) == -1);
        $this->assertTrue($cookieManager->setCookieCalls[1]["cookieDomain"] == $cookieDomain);
        $this->assertTrue($cookieManager->setCookieCalls[1]["value"] == null);
    }

    public function test_extendQueueCookie_CookieDoesNotExist_Test() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store("event2", $queueId, true, 20, $cookieDomain, $secretKey);
        $testObject->extendQueueCookie($eventId, 20, $cookieDomain, $secretKey);
        $this->assertTrue(count($cookieManager->setCookieCalls) == 1);
    }

    public function test_extendQueueCookie_CookietExist_Test() {
        $eventId = "event1";
        $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
        $cookieDomain = ".test.com";
        $queueId = "queueId";
        $cookieKey = QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository::getCookieKey($eventId);

        $cookieManager = new CookieManagerMockClass();
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository($cookieManager);
        $testObject->store($eventId, $queueId, true, 20, $cookieDomain, $secretKey);
        $testObject->extendQueueCookie($eventId, 12, $cookieDomain, $secretKey);

        $state = $testObject->getState($eventId, $secretKey);
        $this->assertTrue($state->isValid);

        $this->assertTrue($state->isValid);
        $this->assertTrue($state->queueId == $queueId);
        $this->assertTrue($state->isStateExtendable);
        $this->assertTrue(abs(time() + 12 * 60 - $state->expires) < 100);
        $this->assertTrue(abs(intval($cookieManager->cookieList[$cookieKey]["expiration"]) - time() - 24 * 60 * 60) < 100);
        $this->assertTrue($cookieManager->cookieList[$cookieKey]["cookieDomain"] == $cookieDomain);
    }
}