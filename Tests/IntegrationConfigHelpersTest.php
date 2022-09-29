<?php
use QueueIT\KnownUserV3\SDK;

#has already been included in TestSuite.php
#require_once(__DIR__ . '/vendor/simpletest/simpletest/autorun.php');

require_once( __DIR__ .'/../IntegrationConfigHelpers.php');
require_once( __DIR__ . '/../KnownUser.php');
require_once( __DIR__ . '/HttpRequestProviderMock.php');
require_once( __DIR__ . '/../UserInQueueService.php');
error_reporting(E_ALL);

class ComparisonOperatorHelperTest extends UnitTestCase 
{
    function  test_evaluate_equals() {
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Equals", false, false, "test1", "test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Equals", false, false, "test1", "Test1",NULL));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Equals", false, true, "test1", "Test1",NULL));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Equals", true, false, "test1", "Test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Equals", true, false, "test1", "test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Equals", true, true, "test1", "Test1",NULL));
    }
    
    function  test_evaluate_contains() {
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Contains", false, false, "test_test1_test", "test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Contains",false, false, "test_test1_test", "Test1",NULL));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Contains",false, true, "test_test1_test", "Test1",NULL));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Contains", true, false, "test_test1_test", "Test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Contains",true, true, "test_test1", "Test1",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Contains",true, false, "test_test1", "test1",NULL));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("Contains",false, false, "test_dsdsdsdtest1", "*",NULL));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("Contains",false, false, "", "*",NULL));
    }

    function test_evaluate_EqualsAny() {
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",false, false, "test1", NULL,array("test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",false, false, "test1", NULL,array("Test1")));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",false, true, "test1", NULL,array("Test1")));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",true, false, "test1", NULL,array("Test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",true, false, "test1", NULL,array("test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("EqualsAny",true, true, "test1", NULL,array("Test1")));
    }

    function test_evaluate_ContainsAny() {
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",false, false, "test_test1_test", NULL,array("test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",false, false, "test_test1_test", NULL,array("Test1")));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",false, true, "test_test1_test", NULL,array("Test1")));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",true, false, "test_test1_test", NULL,array("Test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",true, true, "test_test1", NULL,array("Test1")));
        $this->assertFalse( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",true, false, "test_test1", NULL,array("test1")));
        $this->assertTrue( SDK\ComparisonOperatorHelper::evaluate("ContainsAny",false, false, "test_dsdsdsdtest1", NULL,array("*")));
    }
}

class UrlValidatorHelperTest extends UnitTestCase 
{
    function  test_evaluate()
    {
        $triggerPart = array();
     
        $triggerPart ["UrlPart"] = "PageUrl";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"]= "http://test.tesdomain.com:8080/test?q=1";
        $this->assertFalse( SDK\UrlValidatorHelper::evaluate($triggerPart, "http://test.tesdomain.com:8080/test?q=2"));

        $triggerPart ["ValueToCompare"] = "/Test/t1";
        $triggerPart ["UrlPart"] = "PagePath";
        $triggerPart ["Operator"]= "Equals";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UrlValidatorHelper::evaluate($triggerPart,  "http://test.tesdomain.com:8080/test/t1?q=2&y02"));
   
        $triggerPart ["UrlPart"] = "HostName";
        $triggerPart ["ValueToCompare"] = "test.tesdomain.com";
        $triggerPart ["Operator"]= "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UrlValidatorHelper::evaluate($triggerPart, "http://m.test.tesdomain.com:8080/test?q=2"));

        $triggerPart ["UrlPart"] = "HostName";
        $triggerPart ["ValueToCompare"] = "test.tesdomain.com";
        $triggerPart ["Operator"]= "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertFalse( SDK\UrlValidatorHelper::evaluate($triggerPart,"http://m.test.tesdomain.com:8080/test?q=2"));

        $triggerPart ["UrlPart"] = "HostName";
        $triggerPart ["ValuesToCompare"] = array("balablaba","test.tesdomain.com");
        $triggerPart ["Operator"]= "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UrlValidatorHelper::evaluate($triggerPart,"http://m.test.tesdomain.com:8080/test?q=2"));
       
        $triggerPart ["ValuesToCompare"] = array("ssss_SSss","/Test/t1");
        $triggerPart ["UrlPart"] = "PagePath";
        $triggerPart ["Operator"]= "EqualsAny";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UrlValidatorHelper::evaluate($triggerPart,  "http://test.tesdomain.com:8080/test/t1?q=2&y02"));
    }
}

class CookieValidatorHelperTest extends UnitTestCase 
{
    function  test_evaluate()
    {
        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"] = "1";
        $this->assertFalse( SDK\CookieValidatorHelper::evaluate($triggerPart, array("c1"=>"hhh")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $this->assertFalse( SDK\CookieValidatorHelper::evaluate($triggerPart, array("c2"=>"ddd","c1"=>"1")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["IsIgnoreCase"] = true;
        $this->assertTrue( SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"1")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $triggerPart ["IsNegative"] = true;
        $triggerPart ["IsIgnoreCase"] = true;
        $this->assertFalse( SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"1")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "ContainsAny";
        $triggerPart ["ValuesToCompare"] = array("cookievalue","value");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"cookie value value value")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "EqualsAny";
        $triggerPart ["ValuesToCompare"] = array("cookievalue","1");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertFalse( SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"1")));
    }
}

class UserAgentValidatorHelperTest extends UnitTestCase 
{
    function test_evaluate()
    {
        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"] = "googlebot";
        $this->assertFalse( SDK\UserAgentValidatorHelper::evaluate($triggerPart, "Googlebot sample useraagent"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Equals";
        $triggerPart ["ValueToCompare"] = "googlebot";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertTrue( SDK\UserAgentValidatorHelper::evaluate($triggerPart,"oglebot sample useraagent"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "googlebot";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = true;
        $this->assertFalse( SDK\UserAgentValidatorHelper::evaluate($triggerPart, "googlebot"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "googlebot";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UserAgentValidatorHelper::evaluate($triggerPart, "Googlebot"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "ContainsAny";
        $triggerPart ["ValuesToCompare"] = array("googlebot");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\UserAgentValidatorHelper::evaluate($triggerPart, "Googlebot"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "EqualsAny";
        $triggerPart ["ValuesToCompare"] =array("googlebot");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertTrue( SDK\UserAgentValidatorHelper::evaluate($triggerPart, "oglebot sample useraagent"));
    }
}

class HttpHeaderValidatorHelperTest extends UnitTestCase
{
    function test_evaluate()
    {
        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"] = "googlebot";
        $this->assertFalse( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart, array("")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = false;
        $this->assertFalse( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart, array("c2"=>"t1","c3"=>"t1")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Equals";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $triggerPart ["ValueToCompare"] = "t1";
        $triggerPart ["HttpHeaderName"] = "c1";
        $this->assertTrue( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart,array("c2"=>"t1","c3"=>"t1")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = true;
        $triggerPart ["ValueToCompare"] = "t1";
        $triggerPart ["HttpHeaderName"] = "C1";
        $this->assertFalse( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart, array("c2"=>"t1","c3"=>"t1","c1"=>"test t1 test ")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"] = "t1";
        $triggerPart ["HttpHeaderName"] = "C1";
        $this->assertTrue( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart, array("c2"=>"t1","c3"=>"t1","c1"=>"test T1 test ")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "ContainsAny";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValuesToCompare"] = array("blabalabala","t1","t2");
        $triggerPart ["HttpHeaderName"] = "C1";
        $this->assertTrue( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart, array("c2"=>"t1","c3"=>"t1","c1"=>"test T1 test ")));

        $triggerPart = array();
        $triggerPart ["Operator"] = "EqualsAny";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = true;
        $triggerPart ["ValuesToCompare"] =array("bla","bla", "t1");
        $triggerPart ["HttpHeaderName"] = "c1";
        $this->assertFalse( SDK\HttpHeaderValidatorHelper::evaluate($triggerPart,array("c2"=>"t1","c3"=>"t1","c1"=>"t1")));
    }
}

class HttpRequestBodyValidatorHelperTest extends UnitTestCase 
{
	function test_evaluate() 
    {
        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["ValueToCompare"] = "admin123";
        $this->assertFalse( SDK\RequestBodyValidatorHelper::evaluate($triggerPart, "Admin123@admin.com"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Equals";
        $triggerPart ["ValueToCompare"] = "enduser";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertTrue( SDK\RequestBodyValidatorHelper::evaluate($triggerPart,"nduser"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "product123";
        $triggerPart ["IsIgnoreCase"] = false;
        $triggerPart ["IsNegative"] = true;
        $this->assertFalse( SDK\RequestBodyValidatorHelper::evaluate($triggerPart, "product123"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "product123";
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\RequestBodyValidatorHelper::evaluate($triggerPart, "Product123"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "ContainsAny";
        $triggerPart ["ValuesToCompare"] = array("product123");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = false;
        $this->assertTrue( SDK\RequestBodyValidatorHelper::evaluate($triggerPart, "Product123"));

        $triggerPart = array();
        $triggerPart ["Operator"] = "EqualsAny";
        $triggerPart ["ValuesToCompare"] =array("product123");
        $triggerPart ["IsIgnoreCase"] = true;
        $triggerPart ["IsNegative"] = true;
        $this->assertTrue( SDK\RequestBodyValidatorHelper::evaluate($triggerPart, "roduct123 in the basket"));
    }
}

class IntegrationEvaluatorTest extends UnitTestCase 
{
    function test_getMatchedIntegrationConfig_OneTrigger_And_NotMatched()
    {
      $request = new HttpRequestProviderMock();
      $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $integrationConfig = array(
                "Integrations"=>array( 
                                     array(
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );
        

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();

        $this->assertTrue( $testObject->getMatchedIntegrationConfig($integrationConfig, $url, $request) === null);
    }
    
    function test_getMatchedIntegrationConfig_OneTrigger_And_Matched()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $request->cookieManager->cookieArray = array("c2"=>"ddd","c1"=>"Value1");

        $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $result = $testObject->getMatchedIntegrationConfig($integrationConfig, $url, $request);
        $this->assertTrue($result["Name"]==="integration1");
    }

    function test_getMatchedIntegrationConfig_OneTrigger_And_NotMatched_UserAgent()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $request->cookieManager->cookieArray = array("c2"=>"ddd","c1"=>"Value1");
        $request->userAgent =  "bot.html google.com googlebot test";
        $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "ValidatorType"=> "UserAgentValidator",
                                                                        "ValueToCompare"=> "googlebot",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>true
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,  
                $url,$request)==NULL);
    }

    function test_getMatchedIntegrationConfig_OneTrigger_And_NotMatched_HttpHeader()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $request->cookieManager->cookieArray = array("c2"=>"ddd","c1"=>"Value1");
        $request->headerArray =  array("c1"=>"t1","headertest"=>"abcd efg test gklm");
        
        $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "ValidatorType"=> "HttpHeaderValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "HttpHeaderName"=>"HeaderTest",
                                                                        "Operator"=>"Contains",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>true
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,  
                $url,$request)==NULL);
    }

    function test_getMatchedIntegrationConfig_OneTrigger_Or_NotMatched()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"Or",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>false
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Equals",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>false
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,   $url,$request)==null);

    }

    function test_getMatchedIntegrationConfig_OneTrigger_Or_Matched()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"Or",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>true
                                                                        ),
                                                                        array(
                                                                        "UrlPart" => "PageUrl",
                                                                        "ValidatorType"=> "UrlValidator",
                                                                        "ValueToCompare"=> "test",
                                                                        "Operator"=>"Equals",
                                                                        "IsIgnoreCase"=>false,
                                                                        "IsNegative"=>true
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $result = $testObject->getMatchedIntegrationConfig($integrationConfig, $url, $request);
        $this->assertTrue($result["Name"]==="integration1");

    }

    function test_getMatchedIntegrationConfig_TwoTriggers_Matched()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
        $integrationConfig = array(
                "Integrations"=>array(
                                   
                                        array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>true
                                                                        )
                                                                    )
                                                    ),
                                                    array(
                                                    "LogicalOperator"=>"And",
                                                    "TriggerParts"=>array(
                                                                            array(
                                                                            "CookieName" =>"c1",
                                                                            "Operator" =>"Equals",
                                                                            "ValueToCompare" =>"Value1",
                                                                            "ValidatorType"=> "CookieValidator",
                                                                            "IsIgnoreCase"=>false,
                                                                            "IsNegative"=>false
                                                                            ),
                                                                            array(
                                                                            "UrlPart" => "PageUrl",
                                                                            "ValidatorType"=> "UrlValidator",
                                                                            "ValueToCompare"=> "test",
                                                                            "Operator"=>"Contains",
                                                                            "IsIgnoreCase"=>false,
                                                                            "IsNegative"=>false
                                                                            )
                                                                        )
                                                        )
                                            )
                                        )
                )
        );
  

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $result = $testObject->getMatchedIntegrationConfig($integrationConfig, $url,$request);
        $this->assertTrue($result["Name"]=="integration1");
 
    }

    function test_getMatchedIntegrationConfig_ThreeIntegrationsInOrder_SecondMatched()
    {
        $request = new HttpRequestProviderMock();
        $request->cookieManager = new IntegrationConfigHelpersCookieManagerMock();
               $integrationConfig = array(
                "Integrations"=>array(
                                     array(
                                            "Name"=>"integration0",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                            "UrlPart" => "PageUrl",
                                                                            "ValidatorType"=> "UrlValidator",
                                                                            "ValueToCompare"=> "Test",
                                                                            "Operator"=>"Contains",
                                                                            "IsIgnoreCase"=>false,
                                                                            "IsNegative"=>false
                                                                            )
                                                                    )
                                                    )
                                            )
                                        ),
                                     array(
                                            "Name"=>"integration1",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                            "UrlPart" => "PageUrl",
                                                                            "ValidatorType"=> "UrlValidator",
                                                                            "ValueToCompare"=> "test",
                                                                            "Operator"=>"Contains",
                                                                            "IsIgnoreCase"=>false,
                                                                            "IsNegative"=>false
                                                                            )
                                                                    )
                                                    )
                                            )
                                        ),
                                        array(
                                            "Name"=>"integration2",
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"Equals",
                                                                        "ValueToCompare" =>"value1",
                                                                        "ValidatorType"=> "CookieValidator",
                                                                        "IsIgnoreCase"=>true,
                                                                        "IsNegative"=>false
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );
  

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new SDK\IntegrationEvaluator();
     
        $result = $testObject->getMatchedIntegrationConfig($integrationConfig,$url,$request);
        $this->assertTrue($result["Name"]=="integration1");
    } 
}

class IntegrationConfigHelpersCookieManagerMock implements SDK\ICookieManager
{
    public $debugInfoCookie;
    public $cookieArray;
    public function getCookie($cookieName) {
        return $this->debugInfoCookie;
    }

    public function setCookie($name, $value, $expire, $domain, $isCookieHttpOnly, $isCookieSecure) {
        if(is_null($value)){
            $value = "";
        }
        $this->debugInfoCookie = $value;
    }
    
    function getCookieArray()
    {
        if($this->cookieArray==NULL)
            return array();
        return $this->cookieArray;
    }
}
