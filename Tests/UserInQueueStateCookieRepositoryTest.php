<?php
require_once('simpletest/autorun.php');
require_once( __DIR__ .'/../UserInQueueStateCookieRepository.php');
error_reporting(E_ALL);
class UserInQueueStateCookieRepositoryTest extends UnitTestCase 
{
  function  test_Store_HasValidState_ExtendableCookie_CookieIsSaved()
    {
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                            
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                $this->assertTrue($name==="QueueITAccepted-SDFrts345E-V3_event1");
                                                $cookieValue=$value;
                                                $this->assertTrue($path==="/");
                                              
                                                $this->assertTrue($httponly===true);
                                                $this->assertTrue($domain===".test.com");
                                                $this->assertTrue(($expire -time()-24*60*60)<100);
                                            };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                    return $cookieValue;
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, true, $cookieValidity, $cookieDomain, $secretKey);
  
            $this->assertTrue($testObject->hasValidState($eventId, $secretKey));
           $this->assertTrue($testObject->isStateExtendable($eventId,$secretKey));
    }
    function  test_Store_HasValidState_NonExtendableCookie_CookieIsSaved()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                        
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                $this->assertTrue($name==="QueueITAccepted-SDFrts345E-V3_event1");
                                                $cookieValue=$value;
                                                $this->assertTrue($path==="/");
                                              
                                                $this->assertTrue($httponly===true);
                                                $this->assertTrue($domain===".test.com");
                                                $this->assertTrue(($expire -time()-24*60*60)<100);
                                            };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                    return $cookieValue;
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, false, $cookieValidity, $cookieDomain, $secretKey);
  
            $this->assertTrue($testObject->hasValidState($eventId, $secretKey));
            $this->assertTrue(!$testObject->isStateExtendable($eventId,$secretKey));
    }
     function  test_Store_HasValidState_TamperedCookie_StateIsNotValid()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                 
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                $this->assertTrue($name==="QueueITAccepted-SDFrts345E-V3_event1");
                                                $cookieValue=$value;
                                                $this->assertTrue($path==="/");
                                         
                                                $this->assertTrue($httponly===true);
                                                $this->assertTrue($domain===".test.com");
                                                $this->assertTrue(($expire -time()-24*60*60)<100);
                                            };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                  {
                    $result = array();
                    $cookieNameValues = explode("&",$cookieValue);
                    for($i=0;$i<3;++$i)
                    {
                        $arr = explode("=",$cookieNameValues[$i]);
                        if(count($arr)==2)
                                $result[$arr[0]]=$arr[1];

                    }
          
                    return "IsCookieExtendable=true&Expires=".$result["Expires"]."&Hash=".$result["Hash"];
                  }
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, false, $cookieValidity, $cookieDomain, $secretKey);
  
            $this->assertTrue(!$testObject->hasValidState($eventId, $secretKey));
    }
     function  test_Store_HasValidState_ExpiredCookie_StateIsNotValid()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = -1;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                         
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                $this->assertTrue($name==="QueueITAccepted-SDFrts345E-V3_event1");
                                                $cookieValue=$value;
                                                $this->assertTrue($path==="/");
                                         
                                                $this->assertTrue($httponly===true);
                                                $this->assertTrue($domain===".test.com");
                                                $this->assertTrue(($expire -time()-24*60*60)<100);
                                            };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                  {
                    return $cookieValue;
                   }
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, false, $cookieValidity, $cookieDomain, $secretKey);
  
            $this->assertTrue(!$testObject->hasValidState($eventId, $secretKey));
    }
         function  test_HasValidState_DifferntEventId_StateIsNotValid()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                     
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                                                       };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                  {
                    return $cookieValue;
                   }
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, false, $cookieValidity, $cookieDomain, $secretKey);
  
            $this->assertTrue(!$testObject->hasValidState("event2", $secretKey));
    }
     function  test_HasValidState_NoCookie_StateIsNotValid()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                       
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                                                        };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
               return null;
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
              
            $this->assertTrue(!$testObject->hasValidState("event2", $secretKey));
    }
    function  test_HasValidState_InvalidCookie_StateIsNotValid()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
           
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                         
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                                                        };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
               return "Expires=odoododod&IsCookieExtendable=yes&jj=101";
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
              
            $this->assertTrue(!$testObject->hasValidState("event1", $secretKey));
    }
    function  test_CancelQueueCookie_WithoutCookie()
    {
            $getCookieIsCalled;         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                
                                            bool $httponly = false ) use(&$getCookieIsCalled)
                                            {
                                               $getCookieIsCalled= true;
                                            };
            $getCookieF  = function($cookieName) 
            {
               return null;
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            $testObject->cancelQueueCookie("event1");
              $this->assertTrue(!$getCookieIsCalled)  ;
    }
    function test_CancelQueueCookie()
    {
       
            $getCookieIsCalled;  
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                 
                                            bool $httponly = false ) use(&$getCookieIsCalled)
                                            {
                                                $this->assertTrue($name==="QueueITAccepted-SDFrts345E-V3_event1");

                                                $this->assertTrue($value===null);

                                                 $this->assertTrue($expire ===-1);
                                                 $getCookieIsCalled= true;
                                            };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
               return "cookievalue";
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            $testObject->cancelQueueCookie("event1");
            $this->assertTrue($getCookieIsCalled)  ;
    }

        function test_ExtendQueueCookie()
    {
       
            $eventId = "event1";
            $secretKey = "4e1deweb821-a82ew5-49da-acdqq0-5d3476f2068db";
            $cookieDomain = ".test.com";
            $cookieValidity = 10;
            $cookieValue="";
         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                        
                                            bool $httponly = false ) use(&$cookieValue)
                                            {
                                                 $cookieValue = $value;
                                                  $this->assertTrue($domain===".test.com");
                                               };
            $getCookieF  = function($cookieName) use(&$cookieValue)
            {
                if($cookieName==="QueueITAccepted-SDFrts345E-V3_event1")
                  {
                    return $cookieValue;
                   }
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            


            $testObject->store($eventId, false, $cookieValidityn, $cookieDomai, $secretKey);
            $testObject->extendQueueCookie($eventId, 10,$cookieDomain,$secretKey);
  
            $this->assertTrue($testObject->hasValidState($eventId, $secretKey));

            $cookieNameValueMap= array();
            $cookieNameValues = explode("&",$cookieValue);
            for($i=0;$i<3;++$i)
            {
                $arr = explode("=",$cookieNameValues[$i]);
                $cookieNameValueMap[$arr[0]]=$arr[1];
            }
           
             $this->assertTrue($cookieNameValueMap["Expires"]- time() -10*60<100);
    }
     function  test_ExtendQueueCookie_WithoutCookie()
    {
            $getCookieIsCalled;         
            $setCookieF = function (string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                     
                                            bool $httponly = false ) use(&$getCookieIsCalled)
                                            {
                                               $getCookieIsCalled= true;
                                            };
            $getCookieF  = function($cookieName) 
            {
               return null;
            };
            $testObject = new QueueIT\KnownUserV3\SDK\UserInQueueStateCookieRepository();

            $testObject->setCookieCallback=$setCookieF;
            $testObject->getCookieCallback=$getCookieF;
            $testObject->extendQueueCookie("event1",10,".domain","secretkey");
            $this->assertTrue(!$getCookieIsCalled)  ;
    }

}
?>