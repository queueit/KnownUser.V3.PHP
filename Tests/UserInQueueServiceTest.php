<?php

require_once('simpletest/autorun.php');
require_once( __DIR__ . '/../UserInQueueStateCookieRepository.php');
require_once( __DIR__ . '/../Models.php');
require_once( __DIR__ . '/../UserInQueueService.php');

error_reporting(E_ALL);

class UserInQueueStateRepositoryMockClass implements QueueIT\KnownUserV3\SDK\IUserInQueueStateRepository 
{
    public $arrayFunctionCallsArgs;
    public $arrayReturns;

    function __construct() {
        $this->arrayFunctionCallsArgs = array(
            'store' => array(),
            'getState' => array(),
            'cancelQueueCookie' => array(),
            'extendQueueCookie' => array()
        );

        $this->arrayReturns = array(
            'store' => array(),
            'getState' => array(),
            'cancelQueueCookie' => array(),
            'extendQueueCookie' => array()
        );
    }

    public function store($eventId, $queueId,$isStateExtendable, $cookieValidityMinute, $cookieDomain, $customerSecretKey) {
        array_push($this->arrayFunctionCallsArgs['store'], array($eventId,
            $queueId,
            $isStateExtendable,
            $cookieValidityMinute,
            $cookieDomain,
            $customerSecretKey));
    }

    public function getState($eventId, $customerSecretKey) {
        array_push($this->arrayFunctionCallsArgs['getState'], array($eventId,
            $customerSecretKey));

        return $this->arrayReturns['getState'][count($this->arrayFunctionCallsArgs['getState']) - 1];
    }

    public function cancelQueueCookie($eventId, $cookieDomain) {
        array_push($this->arrayFunctionCallsArgs['cancelQueueCookie'], array($eventId, $cookieDomain));
    }

    public function extendQueueCookie($eventId, $cookieValidityMinute, $cookieDomain, $customerSecretKey) {
        array_push($this->arrayFunctionCallsArgs['store'], array(
            $eventId,
            $cookieValidityMinute,
            $cookieDomain,
            $customerSecretKey));
    }

    public function expectCall($functionName, $secquenceNo, array $argument) {
        if (count($this->arrayFunctionCallsArgs[$functionName]) >= $secquenceNo) {

            $argArr = $this->arrayFunctionCallsArgs[$functionName][$secquenceNo - 1];
            if (count($argument) != count($argArr)) {
                return false;
            }

            for ($i = 0; $i <= count($argArr) - 1;  ++$i) {
                //print($argArr[$i]."\xA".$argument[$i]);
                if ($argArr[$i] !== $argument[$i]) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function expectCallAny($functionName) {
        if (count($this->arrayFunctionCallsArgs[$functionName]) >= 1) {
            return true;
        }
        return false;
    }
}

class UserInQueueServiceTest extends UnitTestCase 
{
    public function test_ValidateRequest_ValidState_ExtendableCookie_NoCookieExtensionFromConfig_DoNotRedirectDoNotStoreCookieWithExtension() {
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain";
        $eventConfig->cookieDomain = "testDomain";

        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = false;

        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();

        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(TRUE, "queueId", TRUE  ,   PHP_INT_MAX));
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);

        $result = $testObject->validateRequest("url", "token", $eventConfig, "customerid", "key");
        
        $this->assertTrue(!$result->doRedirect());
        $this->assertTrue($result->queueId == "queueId");
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));
        $this->assertTrue($cookieProviderMock->expectCall('getState', 1, array("e1", 'key')));
    }

    public function test_ValidateRequest_ValidState_ExtendableCookie_CookieExtensionFromConfig_DoNotRedirectDoStoreCookieWithExtension() {
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieDomain = "testDomain";
        $eventConfig->cookieValidityMinute=10;
        $eventConfig->extendCookieValidity=true;
         
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(TRUE, "queueId", TRUE  ,   PHP_INT_MAX));

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);

        $result = $testObject->validateRequest("url", "token", $eventConfig, "customerid", "key");
        $this->assertTrue(!$result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
        $this->assertTrue($result->queueId == "queueId");
     
        $this->assertTrue($cookieProviderMock->expectCall('store', 1, array("e1", 'queueId',true, 10, 'testDomain', "key")));
    }

    public function test_ValidateRequest_ValidState_NoExtendableCookie_DoNotRedirectDoNotStoreCookieWithExtension() {
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;

        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(TRUE, "queueId", FALSE  ,   PHP_INT_MAX));

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);

        $result = $testObject->validateRequest("url", "token", $eventConfig, "customerid", "key");
        $this->assertTrue(!$result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
        $this->assertTrue($result->queueId == "queueId");
        $this->assertFalse($cookieProviderMock->expectCallAny('store', 1));
    }

    public function test_ValidateRequest_NoCookie_TampredToken_RedirectToErrorPageWithHashError_DoNotStoreCookie() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;
        $eventConfig->version = 11;
        $url = "http://test.test.com?b=h";
         
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE, NULL, FALSE  ,   PHP_INT_MAX));
            
        $token = $this->generateHash('e1','queueId', strval(time() + (3 * 60)), 'False', null, $key);
        $token = str_replace("False", 'True', $token);
        // var_dump( $token);
        $expectedErrorUrl = "https://testDomain.com/error/hash?c=testCustomer&e=e1" .
                "&ver=v3-php-1.0.0.0"
                . "&cver=11"
                . "&queueittoken=" . $token
                . "&t=" . urlencode($url);

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));

        $this->assertTrue($result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
        $matches = array();
        preg_match("/&ts=[^&]*/", $result->redirectUrl, $matches);
        $timestamp = str_replace("&ts=", "", $matches[0]);
        $timestamp = str_replace("&", "", $timestamp);
        $this->assertTrue(time() - intval($timestamp) < 100);

        $urlWithoutTimeStamp = preg_replace("/&ts=[^&]*/", "", $result->redirectUrl);

        $this->assertTrue(strtolower($urlWithoutTimeStamp) == strtolower($expectedErrorUrl));
    }

    public function test_ValidateRequest_NoCookie_ExpiredTimeStampInToken_RedirectToErrorPageWithTimeStampError_DoNotStoreCookie() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;
        $eventConfig->version = 11;
        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE,NULL, FALSE,   PHP_INT_MAX));
        $token = $this->generateHash('e1','queueId', strval(time() - (3 * 60)), 'False', null, $key);

        $expectedErrorUrl = "https://testDomain.com/error/timestamp?c=testCustomer&e=e1" .
                  "&ver=v3-php-1.0.0.0"
                    . "&cver=11"
                . "&queueittoken=" . $token
                . "&t=" . urlencode($url);

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));

        $this->assertTrue($result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
        $matches = array();
        preg_match("/&ts=[^&]*/", $result->redirectUrl, $matches);
        $timestamp = str_replace("&ts=", "", $matches[0]);
        $timestamp = str_replace("&", "", $timestamp);
        $this->assertTrue(time() - intval($timestamp) < 100);

        $urlWithoutTimeStamp = preg_replace("/&ts=[^&]*/", "", $result->redirectUrl);
        $this->assertTrue(strtolower($urlWithoutTimeStamp) == strtolower($expectedErrorUrl));
    }

    public function test_ValidateRequest_NoCookie_EventIdMismatch_RedirectToErrorPageWithEventIdMissMatchError_DoNotStoreCookie() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e2";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;
        $eventConfig->version = 11;
        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE,NULL, FALSE,   PHP_INT_MAX));
        $token = $this->generateHash('e1', 'queueId',strval(time() - (3 * 60)), 'False', null, $key);

        $expectedErrorUrl = "https://testDomain.com/error/eventid?c=testCustomer&e=e2" .
                "&ver=v3-php-1.0.0.0"
                . "&cver=11"
                . "&queueittoken=" . $token
                . "&t=" . urlencode($url);

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));

        $this->assertTrue($result->doRedirect());
        $this->assertTrue($result->eventId == 'e2');
        $matches = array();
        preg_match("/&ts=[^&]*/", $result->redirectUrl, $matches);
        $timestamp = str_replace("&ts=", "", $matches[0]);
        $timestamp = str_replace("&", "", $timestamp);
        $this->assertTrue(time() - intval($timestamp) < 100);

        $urlWithoutTimeStamp = preg_replace("/&ts=[^&]*/", "", $result->redirectUrl);
        $this->assertTrue(strtolower($urlWithoutTimeStamp) == strtolower($expectedErrorUrl));
    }

    public function test_ValidateRequest_NoCookie_ValidToken_ExtendableCookie_DoNotRedirect_StoreEextendableCookie() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->cookieDomain = "testDomain";

        $eventConfig->extendCookieValidity = true;

        $eventConfig->version = 11;
        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE, NULL, FALSE,   PHP_INT_MAX));
        
        $token = $this->generateHash('e1', 'queueId',strval(time() + (3 * 60)), 'true', null, $key);
        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertTrue(!$result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
        $this->assertTrue($result->queueId == 'queueId');
        $this->assertTrue($cookieProviderMock->expectCall('store', 1, array("e1",'queueId', true, 10, 'testDomain', $key)));
    }

    public function test_ValidateRequest_NoCookie_ValidToken_CookieValidityMinuteFromToken_DoNotRedirect_StoreNonEextendableCookie() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 30;
        $eventConfig->cookieDomain = "testDomain";

        $eventConfig->extendCookieValidity = true;

        $eventConfig->version = 11;
        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE, NULL, FALSE,   PHP_INT_MAX));
        $token = $this->generateHash('e1', 'queueId',strval(time() + (3 * 60)), 'false', 3, $key);

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertTrue(!$result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
         $this->assertTrue($result->queueId == 'queueId');
        $this->assertTrue($cookieProviderMock->expectCall('store', 1, array("e1",'queueId', false, 3, 'testDomain', $key)));
    }

    public function test_NoCookie_NoValidToken_WithoutToken_RedirectToQueue() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;
        $eventConfig->version = 11;
        $eventConfig->culture = 'en-US';
        $eventConfig->layoutName = 'testlayout';

        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE, NULL, FALSE,   PHP_INT_MAX));
        $token = "";

        $expectedErrorUrl = "https://testDomain.com?c=testCustomer&e=e1" .
                "&ver=v3-php-1.0.0.0"
                . "&cver=11"
                . "&cid=en-US"
                . "&l=testlayout"
                . "&t=" . urlencode($url);

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, $token, $eventConfig, "testCustomer", $key);
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));

        $this->assertTrue($result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
         $this->assertTrue($result->queueId == null);
        $this->assertTrue(strtolower($result->redirectUrl) == strtolower($expectedErrorUrl));
    }

    public function test_ValidateRequest_NoCookie_InValidToken() {
        $key = "4e1db821-a825-49da-acd0-5d376f2068db";
        $eventConfig = new QueueIT\KnownUserV3\SDK\EventConfig();
        $eventConfig->eventId = "e1";
        $eventConfig->queueDomain = "testDomain.com";
        $eventConfig->cookieValidityMinute = 10;
        $eventConfig->extendCookieValidity = true;
        $eventConfig->version = 11;
        $eventConfig->culture = 'en-US';
        $eventConfig->layoutName = 'testlayout';


        $url = "http://test.test.com?b=h";
        $cookieProviderMock = new UserInQueueStateRepositoryMockClass ();
        array_push($cookieProviderMock->arrayReturns['getState'], new QueueIT\KnownUserV3\SDK\StateInfo(FALSE, NULL, FALSE,   PHP_INT_MAX));
        $token = "";

        $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueService($cookieProviderMock);
        $result = $testObject->validateRequest($url, "ts_sasa~cv_adsasa~ce_falwwwse~q_944c1f44-60dd-4e37-aabc-f3e4bb1c8895", $eventConfig, "testCustomer", $key);
        $this->assertFalse($cookieProviderMock->expectCallAny('store'));

        $this->assertTrue($result->doRedirect());
        $this->assertTrue($result->eventId == 'e1');
           $this->assertTrue($result->queueId == null);

        $this->assertTrue(strpos($result->redirectUrl, "https://testDomain.com/error/hash?c=testCustomer&e=e1") == 0);
    }

    public function generateHash($eventId,$queueId ,$timestamp, $extendableCookie, $cookieValidityMinute, $secretKey) {
        $token = 'e_' . $eventId . '~ts_' . $timestamp . '~ce_' . $extendableCookie. '~q_'. $queueId;
        if (isset($cookieValidityMinute))
            $token = $token . '~cv_' . $cookieValidityMinute;

        return $token . '~h_' . hash_hmac('sha256', $token, $secretKey);
    }
}