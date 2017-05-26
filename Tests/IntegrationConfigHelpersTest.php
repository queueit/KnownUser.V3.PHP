<?php
require_once('simpletest/autorun.php');
require_once( __DIR__ .'/../IntegrationConfigHelpers.php');
error_reporting(E_ALL);
class ComparisonOperatorHelperTest extends UnitTestCase 
{
  function  test_evaluate_equalS()
    {
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", false, false, "test1", "test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", false, false, "test1", "Test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", false, true, "test1", "Test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", true, false, "test1", "Test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", true, false, "test1", "test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EqualS", true, true, "test1", "Test1"));
    }
 function  test_evaluate_contains()
    {
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains", false, false, "test_test1_test", "test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains",false, false, "test_test1_test", "Test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains",false, true, "test_test1_test", "Test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains", true, false, "test_test1_test", "Test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains",true, true, "test_test1", "Test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains",true, false, "test_test1", "test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("Contains",false, false, "test_dsdsdsdtest1", "*"));
    }
      function  test_evaluate_startsWith()
    {
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("StartsWith", false, false, "test1_test1_test", "test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("StartsWith",false, false, "test1_test1_test", "Test1"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("StartsWith", false, true, "test1_test1_test", "Test1"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("StartsWith", true, true, "test1_test1_test", "Test1"));
    }
         function  test_evaluate_endsWith()
    {
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EndsWith",false, false, "test1_test1_testshop", "shop"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EndsWith",false, false, "test1_test1_testshop2", "shop"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EndsWith", false, true, "test1_test1_testshop", "Shop"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("EndsWith", true, true, "test1_test1_testshop", "Shop"));
    }
             function  test_evaluate_matchesWith()
    {
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("MatchesWith",false, false, "test1_test1_testshop", "#.*shop.*#"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("MatchesWith",false, false, "test1_test1_testshop2", "#.*Shop.*#"));
            $this->assertTrue( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("MatchesWith", false, true, "test1_test1_testshop", "#.*Shop.*#"));
            $this->assertFalse( QueueIT\KnownUserV3\SDK\ComparisonOperatorHelper::evaluate("MatchesWith", true, true, "test1_test1_testshop", "#.*Shop.*#"));
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
            $this->assertFalse( QueueIT\KnownUserV3\SDK\UrlValidatorHelper::evaluate($triggerPart, "http://test.tesdomain.com:8080/test?q=2"));

           $triggerPart ["ValueToCompare"] = "/Test/t1";
           $triggerPart ["UrlPart"] = "PagePath";
           $triggerPart ["Operator"]= "EqualS";
           $triggerPart ["IsIgnoreCase"] = true;
           $triggerPart ["IsNegative"] = false;
           $this->assertTrue( QueueIT\KnownUserV3\SDK\UrlValidatorHelper::evaluate($triggerPart,  "http://test.tesdomain.com:8080/test/t1?q=2&y02"));
   


            $triggerPart ["UrlPart"] = "HostName";
            $triggerPart ["ValueToCompare"] = "test.tesdomain.com";
            $triggerPart ["Operator"]= "Contains";
            $triggerPart ["IsIgnoreCase"] = true;
            $triggerPart ["IsNegative"] = false;
            $this->assertTrue( QueueIT\KnownUserV3\SDK\UrlValidatorHelper::evaluate($triggerPart, "http://m.test.tesdomain.com:8080/test?q=2"));


            $triggerPart ["UrlPart"] = "HostName";
            $triggerPart ["ValueToCompare"] = "test.tesdomain.com";
            $triggerPart ["Operator"]= "Contains";
            $triggerPart ["IsIgnoreCase"] = true;
            $triggerPart ["IsNegative"] = true;
            $this->assertFalse( QueueIT\KnownUserV3\SDK\UrlValidatorHelper::evaluate($triggerPart,"http://m.test.tesdomain.com:8080/test?q=2"));

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
        $this->assertFalse( QueueIT\KnownUserV3\SDK\CookieValidatorHelper::evaluate($triggerPart, array("c1"=>"hhh")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $this->assertFalse( QueueIT\KnownUserV3\SDK\CookieValidatorHelper::evaluate($triggerPart, array("c2"=>"ddd","c1"=>"1")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $triggerPart ["IsNegative"] = false;
        $triggerPart ["IsIgnoreCase"] = true;
        $this->assertTrue( QueueIT\KnownUserV3\SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"1")));

        $triggerPart = array();
        $triggerPart ["CookieName"] = "c1";
        $triggerPart ["Operator"] = "Contains";
        $triggerPart ["ValueToCompare"] = "1";
        $triggerPart ["IsNegative"] = true;
        $triggerPart ["IsIgnoreCase"] = true;
        $this->assertFalse( QueueIT\KnownUserV3\SDK\CookieValidatorHelper::evaluate($triggerPart,array("c2"=>"ddd","c1"=>"1")));

    }


}
class IntegrationEvaluatorTest extends UnitTestCase 
{
   function test_getMatchedIntegrationConfig_OneTrigger_And_NotMatched()
    {
      
        $integrationConfig = array(
                "Integrations"=>array( 
                                     array(
                                            "Triggers"=> array(
                                                array(
                                                "LogicalOperator"=>"And",
                                                "TriggerParts"=>array(
                                                                        array(
                                                                        "CookieName" =>"c1",
                                                                        "Operator" =>"EqualS",
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
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();

        $this->assertTrue( $testObject->getMatchedIntegrationConfig($integrationConfig,   $url, array()) === null);
    
    }
    function test_getMatchedIntegrationConfig_OneTrigger_And_Matched()
    {
      
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
                                                                        "Operator" =>"EqualS",
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
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,  
                $url,array("c2"=>"ddd","c1"=>"Value1"))["Name"]==="integration1");
    }


    function test_getMatchedIntegrationConfig_OneTrigger_Or_NotMatched()
    {
      
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
                                                                        "Operator" =>"EqualS",
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
                                                                        "IsNegative"=>false
                                                                        )
                                                                    )
                                                    )
                                            )
                                        )
                )
        );

        $url = "http://test.tesdomain.com:8080/test?q=2";
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,   $url,array("c2"=>"ddd","c1"=>"Value1"))==null);

    }

    function test_getMatchedIntegrationConfig_OneTrigger_Or_Matched()
    {
      
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
                                                                        "Operator" =>"EqualS",
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
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,   $url,array("c2"=>"ddd","c1"=>"Value1"))==null);

    }
    function test_getMatchedIntegrationConfig_TwoTriggers_Matched()
    {
      
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
                                                                        "Operator" =>"EqualS",
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
                                                                            "Operator" =>"EqualS",
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
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,   $url,array("c2"=>"ddd","c1"=>"Value1"))["Name"]=="integration1");
 
    }
    function test_getMatchedIntegrationConfig_ThreeIntegrationsInOrder_SecondMatched()
    {
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
                                                                        "Operator" =>"EqualS",
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
        $testObject = new QueueIT\KnownUserV3\SDK\IntegrationEvaluator();
     
        $this->assertTrue($testObject->getMatchedIntegrationConfig($integrationConfig,   $url,array("c2"=>"ddd","c1"=>"Value1"))["Name"]=="integration1");
    }
       
}