<?php

require_once('simpletest/autorun.php');
require_once( __DIR__ . '/../KnownUser.php');
require_once( __DIR__ . '/../UserInQueueService.php');

error_reporting(E_ALL);

class HttpRequestProviderMock implements QueueIT\KnownUserV3\SDK\IHttpRequestProvider
{
    public $userAgent;
    public function getUserAgent() {
        return $this->userAgent;
    }
}

class UserInQueueServiceMock implements QueueIT\KnownUserV3\SDK\IUserInQueueService {

    public $arrayFunctionCallsArgs;
    public $arrayReturns;

    function __construct() {
        $this->arrayFunctionCallsArgs = array(
            'validateRequest' => array(),
            'cancelQueueCookie' => array(),
            'extendQueueCookie' => array()
        );

        $this->arrayReturns = array(
            'validateRequest' => array(),
            'cancelQueueCookie' => array(),
            'extendQueueCookie' => array()
        );
    }

    public function validateRequest(
    $currentPageUrl, $queueitToken, QueueIT\KnownUserV3\SDK\EventConfig $config, $customerId, $secretKey) {
        array_push($this->arrayFunctionCallsArgs['validateRequest'], array(
            $currentPageUrl,
            $queueitToken,
            $config,
            $customerId,
            $secretKey));
    }

    public function cancelQueueCookie($eventId, $cookieDomain) {
        array_push($this->arrayFunctionCallsArgs['cancelQueueCookie'], array(
            $eventId,
            $cookieDomain));
    }

    public function extendQueueCookie(
    $eventId, $cookieValidityMinute, $cookieDomain, $secretKey
    ) {
        array_push($this->arrayFunctionCallsArgs['extendQueueCookie'], array(
            $eventId,
            $cookieValidityMinute,
            $cookieDomain,
            $secretKey));
    }

    public function expectCall($functionName, $secquenceNo, array $argument) {
        if (count($this->arrayFunctionCallsArgs[$functionName]) >= $secquenceNo) {

            $argArr = $this->arrayFunctionCallsArgs[$functionName][$secquenceNo - 1];
            if (count($argument) != count($argArr)) {
                return false;
            }

            for ($i = 0; $i <= count($argArr) - 1; ++$i) {
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

class KnownUserTest extends UnitTestCase {

    function test_cancelQueueCookie() {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        QueueIT\KnownUserV3\SDK\KnownUser::cancelQueueCookie("eventid", "cookieDomain");

        $this->assertTrue($userInQueueservice->expectCall('cancelQueueCookie', 1, array("eventid", "cookieDomain")));
    }
    function test_cancelQueueCookie_null_EventId() {

        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::cancelQueueCookie(NULL, "cookieDomain");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    
    function test_extendQueueCookie_null_EventId() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie(NULL, 10, "cookieDomain", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_null_SecretKey() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", 10, "cookieDomain", NULL);
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "secretKey can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_Invalid_CookieValidityMinute() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", "invalidInt", "cookieDomain", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie_Negative_CookieValidityMinute() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("event1", -1, "cookieDomain", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }

    function test_extendQueueCookie() {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        QueueIT\KnownUserV3\SDK\KnownUser::extendQueueCookie("eventid", 10, "cookieDomain", "secretkey");

        $this->assertTrue($userInQueueservice->expectCall('extendQueueCookie', 1, array("eventid", 10, "cookieDomain", "secretkey")));
    }

    function test_validateRequestByLocalEventConfig_empty_eventId() {

        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "eventId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig_empty_secreteKey() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", NULL);
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "secretKey can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig_empty_queueDomain() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "queueDomain can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig_empty_customerId() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, NULL, "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "customerId can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig_Invalid_extendCookieValidity() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "extendCookieValidity should be valid boolean.";
        }
        $this->assertTrue($exceptionThrown);
    }  
    function test_validateRequestByLocalEventConfig_Invalid_cookieValidityMinute() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig_zero_cookieValidityMinute() {
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
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
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "cookieValidityMinute should be integer greater than 0.";
        }
        $this->assertTrue($exceptionThrown);
    }
    function test_validateRequestByLocalEventConfig() {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);
        $eventconfig = new \QueueIT\KnownUserV3\SDK\EventConfig();
        $eventconfig->cookieDomain = "cookieDomain";
        $eventconfig->layoutName = "layoutName";
        $eventconfig->culture = "culture";
        $eventconfig->eventId = "eventId";
        $eventconfig->queueDomain = "queueDomain";
        $eventconfig->extendCookieValidity = true;
        $eventconfig->cookieValidityMinute = 10;
        $eventconfig->version = 12;

        QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByLocalEventConfig("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey");

        $this->assertTrue($userInQueueservice->expectCall('validateRequest', 1, array("targeturl", "queueIttoken", $eventconfig, "customerid", "secretkey")));
    }

    function test_validateRequestByIntegrationConfig_empty_currentUrl() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("", "queueIttoken", "{}","customerId", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "currentUrl can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }
     function test_validateRequestByIntegrationConfig_empty_integrationsConfigString() {
        $exceptionThrown = false;
        try {
            QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("currentUrl", "queueIttoken",Null,"customerId", "secretkey");
        } catch (Exception $e) {
            $exceptionThrown = $e->getMessage() == "integrationsConfigString can not be null or empty.";
        }
        $this->assertTrue($exceptionThrown);
    }

  
    function test_validateRequestByIntegrationConfig() {
        $userInQueueservice = new UserInQueueServiceMock();
        $r = new ReflectionProperty('QueueIT\KnownUserV3\SDK\KnownUser', 'userInQueueService');
        $r->setAccessible(true);
        $r->setValue(null, $userInQueueservice);

        $httpRequestProvider = new HttpRequestProviderMock();
        $httpRequestProvider->userAgent="googlebot";
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

        QueueIT\KnownUserV3\SDK\KnownUser::validateRequestByIntegrationConfig("http://test.com?event1=true", "queueIttoken", $integrationConfigString, "customerid", "secretkey");
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
    }
    function test_validateRequestByIntegrationConfig_NotMatch() {

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
    function test_validateRequestByIntegrationConfig_ForcedTargeturl() {

        $userInQueueservice = new UserInQueueServiceMock();
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
        ;
    }
    function test_validateRequestByIntegrationConfig_ForecedTargeturl() {

        $userInQueueservice = new UserInQueueServiceMock();
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
        ;
    }
    function test_validateRequestByIntegrationConfig_EventTargetUrl() {

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
                  "ActionType": "Queue",
                  "EventId": "event1",
                  "CookieDomain": ".test.com",
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
 
}
