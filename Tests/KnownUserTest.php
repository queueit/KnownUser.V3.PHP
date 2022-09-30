<?php
#has already been included in TestSuite.php
#require_once( __DIR__ . '/vendor/simpletest/simpletest/autorun.php');

require_once(__DIR__ . '/../KnownUser.php');
require_once(__DIR__ . '/HttpRequestProviderMock.php');
require_once(__DIR__ . '/../UserInQueueService.php');
require_once(__DIR__ . '/../Models.php');

error_reporting(E_ALL);

class KnownUserCookieManagerMock implements QueueIT\KnownUserV3\SDK\ICookieManager
{
    public $debugInfoCookie;
    public $cookieArray;

    public function getCookie($cookieName)
    {
        return $this->debugInfoCookie;
    }

    public function setCookie($name, $value, $expire, $domain, $isCookieHttpOnly, $isCookieSecure)
    {
        if(is_null($value)){ 
            $value = ""; 
        }
        $this->debugInfoCookie = $value;
    }

    function getCookieArray()
    {
        return $this->cookieArray;
    }
}

class UserInQueueServiceMock implements QueueIT\KnownUserV3\SDK\IUserInQueueService
{
    public $arrayFunctionCallsArgs;
    public $arrayReturns;
    public $validateCancelRequestResult;
    public $validateCancelRequestRaiseException;
    public $validateQueueRequestResult;
    public $validateQueueRequestRaiseException;

    function __construct()
    {
        $this->arrayFunctionCallsArgs = array(
            'validateRequest' => array(),
            'extendQueueCookie' => array(),
            'validateCancelRequest' => array(),
            'getIgnoreActionResult' => array()
        );

        $this->arrayReturns = array(
            'validateRequest' => array(),
            'validateCancelRequest' => array(),
            'extendQueueCookie' => array()
        );
    }

    public function validateQueueRequest(
        $currentPageUrl,
        $queueitToken,
        QueueIT\KnownUserV3\SDK\QueueEventConfig
        $config,
        $customerId,
        $secretKey
    ) {
        array_push($this->arrayFunctionCallsArgs['validateRequest'], array(
            $currentPageUrl,
            $queueitToken,
            $config,
            $customerId,
            $secretKey
        ));


        if ($this->validateQueueRequestRaiseException)
            throw new Exception("exception");

        else
            return $this->validateQueueRequestResult;
    }

    public function validateCancelRequest(
        $currentPageUrl,
        QueueIT\KnownUserV3\SDK\CancelEventConfig $config,
        $customerId,
        $secretKey
    ) {
        array_push($this->arrayFunctionCallsArgs['validateCancelRequest'], array(
            $currentPageUrl,
            $config,
            $customerId,
            $secretKey
        ));
        if ($this->validateCancelRequestRaiseException)
            throw new Exception("exception");
        else
            return $this->validateCancelRequestResult;
    }

    public function getIgnoreActionResult($actionName)
    {
        array_push($this->arrayFunctionCallsArgs['getIgnoreActionResult'], "call");
        return new QueueIT\KnownUserV3\SDK\RequestValidationResult(QueueIT\KnownUserV3\SDK\ActionTypes::IgnoreAction, null, null, null, null, $actionName);
    }

    public function extendQueueCookie(
        $eventId,
        $cookieValidityMinute,
        $cookieDomain,
        $isCookieHttpOnly,
        $isCookieSecure,
        $secretKey
    ) {
        array_push($this->arrayFunctionCallsArgs['extendQueueCookie'], array(
            $eventId,
            $cookieValidityMinute,
            $cookieDomain,
            $isCookieHttpOnly,
            $isCookieSecure,
            $secretKey
        ));
    }

    public function expectCall($functionName, $secquenceNo, array $argument)
    {
        if (count($this->arrayFunctionCallsArgs[$functionName]) >= $secquenceNo) {

            $argArr = $this->arrayFunctionCallsArgs[$functionName][$secquenceNo - 1];
            if (count($argument) != count($argArr)) {
                return false;
            }

            for ($i = 0; $i <= count($argArr) - 1; ++$i) {
                if ($argArr[$i] !== $argument[$i]) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function expectCallAny($functionName)
    {
        if (count($this->arrayFunctionCallsArgs[$functionName]) >= 1) {
            return true;
        }
        return false;
    }
}

class KnownUserTest extends UnitTestCase
{
    function setHttpHeaderRequestProvider()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);
    }

    function test_cancelRequestByLocalConfig()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-proto" => "xfp"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $cancelEventconfig->cookieDomain = "cookiedomain";
        $cancelEventconfig->eventId = "eventid";
        $cancelEventconfig->queueDomain = "queuedomain";
        $cancelEventconfig->version = 1;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("url", "queueittoken", $cancelEventconfig, "customerid", "secretkey");

        $this->assertTrue("url" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][0]);
        $this->assertTrue("customerid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][2]);
        $this->assertTrue("secretkey" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][3]);
        $this->assertTrue("eventid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->eventId);
        $this->assertTrue("queuedomain" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->queueDomain);
        $this->assertTrue("cookiedomain" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->cookieDomain);
        $this->assertTrue("1" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->version);
        $this->assertFalse($result->isAjaxResult);
    }

    function test_cancelRequestByLocalConfig_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2furl",
            "x-forwarded-proto" => "xfp"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $cancelEventconfig->cookieDomain = "cookiedomain";
        $cancelEventconfig->eventId = "eventid";
        $cancelEventconfig->queueDomain = "queuedomain";
        $cancelEventconfig->version = 1;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("url", "queueittoken", $cancelEventconfig, "customerid", "secretkey");

        $this->assertTrue("http://url" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][0]);
        $this->assertTrue("customerid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][2]);
        $this->assertTrue("secretkey" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][3]);
        $this->assertTrue("eventid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->eventId);
        $this->assertTrue("queuedomain" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->queueDomain);
        $this->assertTrue("cookiedomain" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->cookieDomain);
        $this->assertTrue("1" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->version);
        $this->assertTrue($result->isAjaxResult);

        $this->assertTrue(strtolower($result->getAjaxRedirectUrl()) == "http%3a%2f%2fq.qeuue-it.com");
    }

    function test_cancelRequestByLocalConfig_empty_eventId()
    {

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $eventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->version = 12;


        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("targeturl", "queueittoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId from cancelConfig can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_cancelRequestByLocalConfig_empty_secreteKey()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $eventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("targeturl", "queueittoken", $eventconfig, "customerid", NULL);
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "secretKey can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_cancelRequestByLocalConfig_empty_queueDomain()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->eventId = "eventId";
        //$eventconfig->queueDomain = "queueDomain";
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("targeturl", "queueittoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "queueDomain from cancelConfig can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_cancelRequestByLocalConfig_empty_customerId()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("targeturl", "queueittoken",  $eventconfig, NULL, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "customerId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_cancelRequestByLocalConfig_empty_targeturl()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->version = 12;
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig(NULL, "queueittoken",  $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "targetUrl can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_null_EventId()
    {
        $this->setHttpHeaderRequestProvider();

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie(NULL, 10, "cookieDomain", false, false, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_null_SecretKey()
    {
        $this->setHttpHeaderRequestProvider();

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", 10, "cookieDomain", false, false, NULL);
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "secretKey can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_Invalid_CookieValidityMinute()
    {
        $this->setHttpHeaderRequestProvider();

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", "invalidInt", "cookieDomain", false, false, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_Negative_CookieValidityMinute()
    {
        $this->setHttpHeaderRequestProvider();
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", -1, "cookieDomain", false, false, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie()
    {
        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("eventid", 10, "cookieDomain", true, true, "secretkey");

        $this->assertTrue($userInQueueservice->expectCall('extendQueueCookie', 1, array("eventid", 10, "cookieDomain", true, true, "secretkey")));
    }

    function test_resolveQueueRequestByLocalConfig_empty_eventId()
    {
        $this->setHttpHeaderRequestProvider();
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        //$eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;


        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId from queueConfig can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_empty_secreteKey()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", NULL);
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "secretKey can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_empty_queueDomain()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        //$eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "queueDomain from queueConfig can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_empty_customerId()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        //$eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, NULL, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "customerId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_Invalid_extendCookieValidity()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = NULL;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "extendCookieValidity from queueConfig should be valid boolean.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_Invalid_cookieValidityMinute()
    {
        $this->setHttpHeaderRequestProvider();

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = TRUE;
        $eventconfig->cookieValidityMinute = "test";
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute from queueConfig should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig_zero_cookieValidityMinute()
    {
        $this->setHttpHeaderRequestProvider();
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = TRUE;
        $eventconfig->cookieValidityMinute = 0;
        $eventconfig->version = 12;

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute from queueConfig should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_resolveQueueRequestByLocalConfig()
    {
        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");

        $this->assertTrue($userInQueueservice->expectCall('validateRequest', 1, array("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey")));
        $this->assertFalse($result->isAjaxResult);
    }

    function test_resolveQueueRequestByLocalConfig_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(

            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2furl",
            "x-forwarded-proto" => "xfp"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");

        $this->assertTrue($userInQueueservice->expectCall('validateRequest', 1, array("http://url", "queueIttoken", $eventconfig, "customerid", "secretkey")));
        $this->assertTrue($result->isAjaxResult);
        $this->assertTrue(strtolower($result->getAjaxRedirectUrl()) == "http%3a%2f%2fq.qeuue-it.com");
    }

    function test_validateRequestByIntegrationConfig_empty_currentUrl()
    {
        $this->setHttpHeaderRequestProvider();

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("", "queueIttoken", "{}", "customerId", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "currentUrlWithoutQueueITToken can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_validateRequestByIntegrationConfig_empty_integrationsConfigString()
    {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("currentUrl", "queueIttoken", Null, "customerId", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "integrationsConfigString can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_validateRequestByIntegrationConfig()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userAgent = "googlebot";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);

        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        },
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "googlebot",
                            "ValidatorType": "UserAgentValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": false
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "AllowTParameter",
                  "ForcedTargetUrl": ""
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result =  QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "http://test.com?event1=true");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][1] == "queueIttoken");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][3] == "customerid");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][4] == "secretkey");

        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->queueDomain == "knownusertest.queue-it.net");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->eventId == "event1");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->culture == "");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->layoutName == "Christmas Layout by Queue-it");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->extendCookieValidity);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->cookieValidityMinute == 20);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->cookieDomain == ".test.com");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->version == 3);
        $this->assertFalse($result->isAjaxResult);
    }

    function test_validateRequestByIntegrationConfig_AjaxCall()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "a" => "b",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2ftest.com%3fevent1%3dtrue",
            "e" => "f"
        );
        $httpRequestProvider->userAgent = "googlebot";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        },
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "googlebot",
                            "ValidatorType": "UserAgentValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": false
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "AllowTParameter",
                  "ForcedTargetUrl": ""
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://url?event1", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "http://test.com?event1=true");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][1] == "queueIttoken");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][3] == "customerid");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][4] == "secretkey");

        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->queueDomain == "knownusertest.queue-it.net");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->eventId == "event1");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->culture == "");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->layoutName == "Christmas Layout by Queue-it");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->extendCookieValidity);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->cookieValidityMinute == 20);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->cookieDomain == ".test.com");
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][2]->version == 3);
        $this->assertTrue($result->isAjaxResult);
        $this->assertTrue(strtolower($result->getAjaxRedirectUrl()) == "http%3a%2f%2fq.qeuue-it.com");
    }

    function test_validateRequestByIntegrationConfig_NotMatch()
    {
        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $integrationConfigString = <<<EOT
        {
          "Description": "test",
          "Integrations": [
          ],
          "CustomerId": "knownusertest",
          "AccountId": "knownusertest",
          "Version": 3,
          "PublishDate": "2017-05-15T21:39:12.0076806Z",
          "ConfigDataVersion": "1.0.0.1"
        }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 0);
        $this->assertFalse($result->doRedirect());
    }

    function test_validateRequestByIntegrationConfig_ForcedTargeturl()
    {
        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                          "Operator": "Contains",
                          "ValueToCompare": "event1",
                          "UrlPart": "PageUrl",
                          "ValidatorType": "UrlValidator",
                          "IsNegative": false,
                          "IsIgnoreCase": true
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "ForcedTargetUrl",
                  "ForcedTargetUrl": "http://test.com"
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");

        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "http://test.com");
    }

    function test_validateRequestByIntegrationConfig_ForcedTargeturl_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "a" => "b",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2fabcdef.com%3fevent1%3dtrue",
            "e" => "f"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                          "Operator": "Contains",
                          "ValueToCompare": "event1",
                          "UrlPart": "PageUrl",
                          "ValidatorType": "UrlValidator",
                          "IsNegative": false,
                          "IsIgnoreCase": true
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "ForcedTargetUrl",
                  "ForcedTargetUrl": "http://test.com"
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1", "queueIttoken", $integrationConfigString, "customerid", "secretkey");

        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "http://test.com");
        $this->assertTrue($result->isAjaxResult);
    }

    function test_validateRequestByIntegrationConfig_ForecedTargeturl()
    {
        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                          "Operator": "Contains",
                          "ValueToCompare": "event1",
                          "UrlPart": "PageUrl",
                          "ValidatorType": "UrlValidator",
                          "IsNegative": false,
                          "IsIgnoreCase": true
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "ForecedTargetUrl",
                  "ForcedTargetUrl": "http://test.com"
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");

        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "http://test.com");
    }

    function test_validateRequestByIntegrationConfig_EventTargetUrl()
    {

        $this->setHttpHeaderRequestProvider();
        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                          "Operator": "Contains",
                          "ValueToCompare": "event1",
                          "UrlPart": "PageUrl",
                          "ValidatorType": "UrlValidator",
                          "IsNegative": false,
                          "IsIgnoreCase": true
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "EventTargetUrl"
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "");
    }

    function test_validateRequestByIntegrationConfig_EventTargetUrl_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "a" => "b",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2ftest.com%3fevent1%3dtrue",
            "e" => "f"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult =  new QueueIT\KnownUserV3\SDK\RequestValidationResult("Queue", "eventid", "", "http://q.qeuue-it.com", "", null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
              "Description": "test",
              "Integrations": [
                {
                  "Name": "event1action",
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
                  "IsCookieHttpOnly": false,
                  "IsCookieSecure": false,
                  "LayoutName": "Christmas Layout by Queue-it",
                  "Culture": "",
                  "ExtendCookieValidity": true,
                  "CookieValidityMinute": 20,
                  "Triggers": [
                    {
                      "TriggerParts": [
                        {
                          "Operator": "Contains",
                          "ValueToCompare": "event1",
                          "UrlPart": "PageUrl",
                          "ValidatorType": "UrlValidator",
                          "IsNegative": false,
                          "IsIgnoreCase": true
                        }
                      ],
                      "LogicalOperator": "And"
                    }
                  ],
                  "QueueDomain": "knownusertest.queue-it.net",
                  "RedirectLogic": "EventTargetUrl"
                }
              ],
              "CustomerId": "knownusertest",
              "AccountId": "knownusertest",
              "Version": 3,
              "PublishDate": "2017-05-15T21:39:12.0076806Z",
              "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateRequest'][0][0] == "");
        $this->assertTrue($result->isAjaxResult);
    }

    function test_validateRequestByIntegrationConfig_CancelAction()
    {
        $this->setHttpHeaderRequestProvider();

        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "redirectUrl", null, null);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": false,
                    "IsCookieSecure": false,
                    "ActionType":"Cancel",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue($result->redirectUrl == "redirectUrl");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][0] == "http://test.com?event1=true");
        $this->assertTrue("customerid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][2]);
        $this->assertTrue("secretkey" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][3]);
        $this->assertTrue("event1" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->eventId);
        $this->assertTrue("knownusertest.queue-it.net" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->queueDomain);
        $this->assertTrue(".test.com" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->cookieDomain);
        $this->assertTrue("3" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->version);
        $this->assertFalse($result->isAjaxResult);
    }

    function test_validateRequestByIntegrationConfig_CancelAction_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "a" => "b",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2furl",
            "e" => "f"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "http://q.qeuue-it.com", null, null);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": false,
                    "IsCookieSecure": false,
                    "ActionType":"Cancel",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");

        $this->assertTrue(strtolower($result->getAjaxRedirectUrl()) == "http%3a%2f%2fq.qeuue-it.com");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest']) == 1);
        $this->assertTrue($userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][0] == "http://url");
        $this->assertTrue("customerid" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][2]);
        $this->assertTrue("secretkey" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][3]);
        $this->assertTrue("event1" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->eventId);
        $this->assertTrue("knownusertest.queue-it.net" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->queueDomain);
        $this->assertTrue(".test.com" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->cookieDomain);
        $this->assertTrue("3" == $userInQueueservice->arrayFunctionCallsArgs['validateCancelRequest'][0][1]->version);
        $this->assertTrue($result->isAjaxResult);
    }

    function test_validateRequestByIntegrationConfig_IgnoreAction()
    {
        $this->setHttpHeaderRequestProvider();

        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": null,
                    "IsCookieSecure": null,
                    "ActionType":"Ignore",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue($result->actionType == "Ignore");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['getIgnoreActionResult']) == 1);
        $this->assertFalse($result->isAjaxResult);
        $this->assertTrue($result->actionName == "event1action");
    }

    function test_validateRequestByIntegrationConfig_IgnoreAction_AjaxCall()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->headerArray = array(
            "a" => "b",
            "c" => "d",
            "x-queueit-ajaxpageurl" => "http%3a%2f%2furl",
            "e" => "f"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": false,
                    "IsCookieSecure": false,
                    "ActionType":"Ignore",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;

        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
        $this->assertTrue($result->actionType == "Ignore");
        $this->assertTrue(count($userInQueueservice->arrayFunctionCallsArgs['getIgnoreActionResult']) == 1);
        $this->assertTrue($result->isAjaxResult);
        $this->assertTrue($result->actionName == "event1action");
    }

    function test_validateRequestByIntegrationConfig_debug()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "redirectUrl", null, null);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": false,
                    "IsCookieSecure": false,
                    "ActionType": "Cancel",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;
        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "http://test.com?event1=true&queueittoken=" . $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $token,
            $integrationConfigString,
            "customerid",
            "secretkey"
        );

        $expectedCookie =
            "TargetUrl=http://test.com?event1=true&queueittoken=" . $token .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|CancelConfig=EventId:event1" .
                         "&Version:3" .
                         "&QueueDomain:knownusertest.queue-it.net" .
                         "&CookieDomain:.test.com" .
                         "&IsCookieHttpOnly:false" .
                         "&IsCookieSecure:false" .
                         "&ActionName:event1action" .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=userIP" .
            "|RequestHttpHeader_Via=v" .
            "|RequestHttpHeader_Forwarded=f" .
            "|RequestHttpHeader_XForwardedFor=xff" .
            "|RequestHttpHeader_XForwardedHost=xfh" .
            "|RequestHttpHeader_XForwardedProto=xfp" .
            "|MatchedConfig=event1action" .
            "|ConfigVersion=3" .
            "|PureUrl=http://test.com?event1=true&queueittoken=" . $token;

        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function test_validateRequestByIntegrationConfig_withoutmatch_debug()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);

        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "redirectUrl", null, null);

        $integrationConfigString = <<<EOT
        {
            "Description": "test",
            "Integrations": [
            {
                "Name": "event1action",
                "EventId": "event1",
                "CookieDomain": ".test.com",
                "IsCookieHttpOnly": false,
                "IsCookieSecure": false,
                "ActionType":"Cancel",
                "Triggers": [
                {
                    "TriggerParts": [
                    {
                        "Operator": "Contains",
                        "ValueToCompare": "notmatch",
                        "UrlPart": "PageUrl",
                        "ValidatorType": "UrlValidator",
                        "IsNegative": false,
                        "IsIgnoreCase": true
                    }
                    ],
                    "LogicalOperator": "And"
                }
                ],
                "QueueDomain": "knownusertest.queue-it.net"
            }
            ],
            "CustomerId": "knownusertest",
            "AccountId": "knownusertest",
            "Version": 3,
            "PublishDate": "2017-05-15T21:39:12.0076806Z",
            "ConfigDataVersion": "1.0.0.1"
        }
EOT;
        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "http://test.com?event1=true&queueittoken=" . $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $token,
            $integrationConfigString,
            "customerid",
            "secretkey"
        );

        $expectedCookie =

            "MatchedConfig=NULL" .
            "|ConfigVersion=3" .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|PureUrl=http://test.com?event1=true&queueittoken=" . $token .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=" .
            "|RequestHttpHeader_Via=" .
            "|RequestHttpHeader_Forwarded=" .
            "|RequestHttpHeader_XForwardedFor=" .
            "|RequestHttpHeader_XForwardedHost=" .
            "|RequestHttpHeader_XForwardedProto=";

        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function test_validateRequestByIntegrationConfig_notvalidhash_debug()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);

        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "redirectUrl", null, null);

        $integrationConfigString = <<<EOT
        {
            "Description": "test",
            "Integrations": [
            {
                "Name": "event1action",
                "EventId": "event1",
                "CookieDomain": ".test.com",
                "IsCookieHttpOnly": false,
                "IsCookieSecure": false,
                "ActionType":"Cancel",
                "Triggers": [
                {
                    "TriggerParts": [
                    {
                        "Operator": "Contains",
                        "ValueToCompare": "notmatch",
                        "UrlPart": "PageUrl",
                        "ValidatorType": "UrlValidator",
                        "IsNegative": false,
                        "IsIgnoreCase": true
                    }
                    ],
                    "LogicalOperator": "And"
                }
                ],
                "QueueDomain": "knownusertest.queue-it.net"
            }
            ],
            "CustomerId": "knownusertest",
            "AccountId": "knownusertest",
            "Version": 3,
            "PublishDate": "2017-05-15T21:39:12.0076806Z",
            "ConfigDataVersion": "1.0.0.1"
        }
EOT;
        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "http://test.com?event1=true&queueittoken=" . $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $token . "ss",
            $integrationConfigString,
            "customerid",
            "secretkey"
        );

        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == Null);
    }

    function test_validateRequestByIntegrationConfig_debug_invalid_config()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $integrationConfigString = "Invalid config";
        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $expectedCookie =
            "Exception=integrationsConfigString is invalid." .
            "|ConfigVersion=NULL" .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|PureUrl=http://test.com?event1=true&queueittoken=" . $token .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=userIP" .
            "|RequestHttpHeader_Via=v" .
            "|RequestHttpHeader_Forwarded=f" .
            "|RequestHttpHeader_XForwardedFor=xff" .
            "|RequestHttpHeader_XForwardedHost=xfh" .
            "|RequestHttpHeader_XForwardedProto=xfp";

        $exceptionThrown = false;
        try {
            $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
                "http://test.com?event1=true&queueittoken=" . $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
                $token,
                $integrationConfigString,
                "customerid",
                "secretkey"
            );
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "integrationsConfigString is invalid.";
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function test_validateRequestByIntegrationConfig_debug_null_config()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $userInQueueservice = new UserInQueueServiceMock();

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $expectedCookie =
            "Exception=integrationsConfigString can not be null or empty." .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|PureUrl=http://test.com?event1=true&queueittoken=" . $token .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=userIP" .
            "|RequestHttpHeader_Via=v" .
            "|RequestHttpHeader_Forwarded=f" .
            "|RequestHttpHeader_XForwardedFor=xff" .
            "|RequestHttpHeader_XForwardedHost=xfh" .
            "|RequestHttpHeader_XForwardedProto=xfp";

        $exceptionThrown = false;
        try {
            $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
                "http://test.com?event1=true&queueittoken=" . $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
                $token,
                null,
                "customerid",
                "secretkey"
            );
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "integrationsConfigString can not be null or empty.";
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function test_validateRequestByIntegrationConfig_debug_missing_customerId()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            null,
            NULL,
            "secretekey"
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_validateRequestByIntegrationConfig_debug_missing_secretKey()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            null,
            "customerid",
            NULL
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_validateRequestByIntegrationConfig_debug_expiredtoken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() - (3 * 60));
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            null,
            "customerid",
            "secretekey"
        );

        $this->assertTrue("https://customerid.api2.queue-it.net/customerid/diagnostics/connector/error/?code=timestamp", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_validateRequestByIntegrationConfig_debug_modifiedToken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp) . "modified",
            null,
            "customerid",
            "secretekey"
        );

        $this->assertTrue("https://customerid.api2.queue-it.net/customerid/diagnostics/connector/error/?code=hash", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_validateRequestByIntegrationConfig_Exception_NoDebugToken_NoDebugCookie()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResultRaiseException = true;
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Cancel", "eventid", "queueid", "redirectUrl", null, null);

        $var = "some text";
        $integrationConfigString = <<<EOT
            {
                "Description": "test",
                "Integrations": [
                {
                    "Name": "event1action",
                    "EventId": "event1",
                    "CookieDomain": ".test.com",
                    "IsCookieHttpOnly": false,
                    "IsCookieSecure": false,
                    "ActionType":"Cancel",
                    "Triggers": [
                    {
                        "TriggerParts": [
                        {
                            "Operator": "Contains",
                            "ValueToCompare": "event1",
                            "UrlPart": "PageUrl",
                            "ValidatorType": "UrlValidator",
                            "IsNegative": false,
                            "IsIgnoreCase": true
                        }
                        ],
                        "LogicalOperator": "And"
                    }
                    ],
                    "QueueDomain": "knownusertest.queue-it.net"
                }
                ],
                "CustomerId": "knownusertest",
                "AccountId": "knownusertest",
                "Version": 3,
                "PublishDate": "2017-05-15T21:39:12.0076806Z",
                "ConfigDataVersion": "1.0.0.1"
            }
EOT;
        try {
            $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
                "http://test.com?event1=true&queueittoken=" ,
                "queueittoken",
                $integrationConfigString,
                "customerid",
                "secretkey"
            );
        } catch (Exception $ex) {
        }
        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == null);
    }

    function test_resolveQueueRequestByLocalConfig_debug()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->eventId = "eventId";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->isCookieHttpOnly = false;
        $eventconfig->isCookieSecure = false;
        $eventconfig->version = 12;
        $eventconfig->actionName = "event1action";

        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", $token, $eventconfig, "customerid", "secretkey");

        $expectedCookie =
            "TargetUrl=targeturl" .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|QueueConfig=EventId:eventId" .
                        "&Version:12" .
                        "&ActionName:event1action" .
                        "&QueueDomain:queueDomain" .
                        "&CookieDomain:cookieDomain" .
                        "&IsCookieHttpOnly:false" .
                        "&IsCookieSecure:false" .
                        "&ExtendCookieValidity:true" .
                        "&CookieValidityMinute:10" .
                        "&LayoutName:layoutName" .
                        "&Culture:culture" .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=userIP" .
            "|RequestHttpHeader_Via=v" .
            "|RequestHttpHeader_Forwarded=f" .
            "|RequestHttpHeader_XForwardedFor=xff" .
            "|RequestHttpHeader_XForwardedHost=xfh" .
            "|RequestHttpHeader_XForwardedProto=xfp";

        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function test_resolveQueueRequestByLocalConfig_debug_missing_customerId()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();

        $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $eventconfig,
            NULL,
            "secretekey"
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_resolveQueueRequestByLocalConfig_debug_missing_secretKey()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $eventconfig,
            "customerid",
            NULL
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_resolveQueueRequestByLocalConfig_debug_expiredtoken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() - (3 * 60));
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            null,
            "customerid",
            "secretekey"
        );

        $this->assertTrue("https://customerid.api2.queue-it.net/customerid/diagnostics/connector/error/?code=timestamp", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_resolveQueueRequestByLocalConfig_debug_modifiedToken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp) . "modified",
            $eventconfig,
            "customerid",
            "secretekey"
        );

        $this->assertTrue("https://customerid.api2.queue-it.net/customerid/diagnostics/connector/error/?code=hash", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_resolveQueueRequestByLocalConfig__Exception_NoDebugToken_NoDebugCookie()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateQueueRequestRaiseException = true;
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);

        $eventconfig = new \QueueIT\KnownUserV3\SDK\QueueEventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;
        $eventconfig->actionName = "event1action";

        try {
            QueueIT\KnownUserV3\SDK\KnownUser::resolveQueueRequestByLocalConfig("targeturl", "queueittoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $ex) {
        }
        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == null);
    }
    //no need for test_resolveQueueRequestByLocalConfig_debug_nullconfig() since you cannot send null to resolveQueueRequestByLocalConfig

    function test_cancelRequestByLocalConfig_debug()
    {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $userInQueueservice->validateCancelRequestResult = new QueueIT\KnownUserV3\SDK\RequestValidationResult("Debug", "eventid", "queueid", "http://q.qeuue-it.com", null, null);
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userHostAddress = "userIP";
        $httpRequestProvider->headerArray = array(
            "via" => "v",
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-host" => "xfh",
            "x-forwarded-proto" => "xfp"
        );
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->absoluteUri = "OriginalURL";
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);

        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $cancelEventconfig->eventId = "eventid";
        $cancelEventconfig->queueDomain = "queuedomain";
        $cancelEventconfig->version = 1;
        $cancelEventconfig->cookieDomain = "cookiedomain";
        $cancelEventconfig->isCookieHttpOnly = false;
        $cancelEventconfig->isCookieSecure = false;
        $cancelEventconfig->actionName = "cancelAction";

        $hashTimestamp = strval(time() + (3 * 60));
        $token = $this->generateHashDebugValidHash("secretkey", $hashTimestamp);
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("targeturl", $token, $cancelEventconfig, "customerid", "secretkey");

        $expectedCookie =
            "TargetUrl=targeturl" .
            "|SdkVersion=" . QueueIT\KnownUserV3\SDK\UserInQueueService::getSDKVersion() .
            "|RunTime=" . phpversion() .
            "|QueueitToken=" . $token .
            "|CancelConfig=EventId:eventid" .
                         "&Version:1" .
                         "&QueueDomain:queuedomain" .
                         "&CookieDomain:cookiedomain" .
                         "&IsCookieHttpOnly:false" .
                         "&IsCookieSecure:false" .
                         "&ActionName:cancelAction" .
            "|OriginalUrl=OriginalURL" .
            "|ServerUtcTime=" . $timestamp .
            "|RequestIP=userIP" .
            "|RequestHttpHeader_Via=v" .
            "|RequestHttpHeader_Forwarded=f" .
            "|RequestHttpHeader_XForwardedFor=xff" .
            "|RequestHttpHeader_XForwardedHost=xfh" .
            "|RequestHttpHeader_XForwardedProto=xfp";

        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == $expectedCookie);
    }

    function  test_cancelRequestByLocalConfig_debug_missing_customerId()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $cancelEventconfig,
            NULL,
            "secretkey"
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_cancelRequestByLocalConfig_debug_missing_secretKey()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() + (3 * 60));
        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $cancelEventconfig,
            "customerid",
            NULL
        );

        $this->assertTrue("https://api2.queue-it.net/diagnostics/connector/error/?code=setup", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_cancelRequestByLocalConfig_debug_expiredToken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() - (3 * 60));
        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("secretkey", $hashTimestamp),
            $cancelEventconfig,
            "customerId",
            "secretkey"
        );

        $this->assertTrue("https://customerId.api2.queue-it.net/customerId/diagnostics/connector/error/?code=timestamp", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_cancelRequestByLocalConfig_debug_modifiedToken()
    {
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'debugInfoArray');
        $r->setAccessible(true);
        $r->setValue(null, NULL);
        $hashTimestamp = strval(time() - (3 * 60));
        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig(
            "url",
            $this->generateHashDebugValidHash("wrong", $hashTimestamp),
            $cancelEventconfig,
            "customerId",
            "secretkey"
        );

        $this->assertTrue("https://customerId.api2.queue-it.net/customerId/diagnostics/connector/error/?code=hash", $result->redirectUrl);
        $this->assertTrue($r->getValue() == NULL);
    }

    function test_cancelRequestByLocalConfig_Exception_NoDebugToken_NoDebugCookie()
    {
        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->cookieManager = new KnownUserCookieManagerMock();
        $httpRequestProvider->headerArray = array(
            "forwarded" => "f",
            "x-forwarded-for" => "xff",
            "x-forwarded-proto" => "xfp"
        );
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'httpRequestProvider');
        $r->setAccessible(true);
        $r->setValue(null, $httpRequestProvider);

        $userInQueueservice = new UserInQueueServiceMock();
        $userInQueueservice->validateCancelRequestRaiseException = true;
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $cancelEventconfig = new \QueueIT\KnownUserV3\SDK\CancelEventConfig();
        $cancelEventconfig->cookieDomain = "cookiedomain";
        $cancelEventconfig->eventId = "eventid";
        $cancelEventconfig->queueDomain = "queuedomain";
        $cancelEventconfig->version = 1;
        try {
            $result = QueueIT\KnownUserV3\SDK\KnownUser::cancelRequestByLocalConfig("url", "queueittoken", $cancelEventconfig, "customerid", "secretkey");
        } catch (Exception $ex) {
        }
        $this->assertTrue($httpRequestProvider->cookieManager->debugInfoCookie == null);
    }
    //no need for test_cancelRequestByLocalConfig_debug_nullconfig() since you cannot send null to cancelRequestByLocalConfig

    public function generateHashDebugValidHash($secretKey, $timestamp)
    {
        $token = 'e_eventId' .   '~rt_debug' . '~ts_' . $timestamp;
        return $token . '~h_' . hash_hmac('sha256', $token, $secretKey);
    }
}
