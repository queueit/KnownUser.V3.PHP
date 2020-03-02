<?php
#has already been included in TestSuite.php
#require __DIR__ . '/vendor/simpletest/simpletest/autorun.php';
#require_once( __DIR__ . '/vendor/simpletest/simpletest/autorun.php');

require_once( __DIR__ .'/../QueueITHelpers.php');
error_reporting(E_ALL);

class TestOfQueueUrlParamsTest extends UnitTestCase 
{
    function test_ExtractQueueParams() {
        $queueITToken = "e_testevent1~q_6cf23f10-aca7-4fa2-840e-e10f56aecb44~ts_1486645251~ce_True~cv_3~rt_Queue~h_cb7b7b53fa20e708cb59a5a2696f248cba3b2905d92e12ee5523c298adbef298";
        $result = QueueIT\KnownUserV3\SDK\QueueUrlParams::extractQueueParams($queueITToken);
        $this->assertTrue($result->eventId==="testevent1");
        $this->assertTrue($result->timeStamp===1486645251);
        $this->assertTrue($result->extendableCookie===true);
        $this->assertTrue($result->queueITToken===$queueITToken);
        $this->assertTrue($result->cookieValidityMinutes===3);
        $this->assertTrue($result->queueId==="6cf23f10-aca7-4fa2-840e-e10f56aecb44");
        $this->assertTrue($result->hashCode==="cb7b7b53fa20e708cb59a5a2696f248cba3b2905d92e12ee5523c298adbef298");
        $this->assertTrue($result->queueITTokenWithoutHash==="e_testevent1~q_6cf23f10-aca7-4fa2-840e-e10f56aecb44~ts_1486645251~ce_True~cv_3~rt_Queue");
    }

    function test_TryExtractQueueParams_NotValidFormat_Test() {
        $queueITToken =  "ts_sasa~cv_adsasa~ce_falwwwse~q_944c1f44-60dd-4e37-aabc-f3e4bb1c8895~h_218b734e-d5be-4b60-ad66-9b1b326266e2";
        $queueitTokenWithoutHash = "ts_sasa~cv_adsasa~ce_falwwwse~q_944c1f44-60dd-4e37-aabc-f3e4bb1c8895";   
        $result = QueueIT\KnownUserV3\SDK\QueueUrlParams::extractQueueParams($queueITToken);
        $this->assertTrue($result->timeStamp===0);
        $this->assertTrue($result->eventId==="");
        $this->assertTrue($result->extendableCookie===false);
        $this->assertTrue($result->cookieValidityMinutes===null);
        $this->assertTrue($result->hashCode==="218b734e-d5be-4b60-ad66-9b1b326266e2");
        $this->assertTrue($result->queueITToken===$queueITToken);
        $this->assertTrue($result->queueITTokenWithoutHash===$queueitTokenWithoutHash);
    }

    function test_tryExtractQueueParams_Using_QueueitToken_With_No_Values() {
        $queueITToken =  "e~q~ts~ce~rt~h";   
        $result = QueueIT\KnownUserV3\SDK\QueueUrlParams::extractQueueParams($queueITToken);
        $this->assertTrue($result->timeStamp===0);
        $this->assertFalse($result->eventId===null);
        $this->assertTrue($result->cookieValidityMinutes===null);
        $this->assertTrue($result->extendableCookie===false);
        $this->assertTrue($result->hashCode==="");
        $this->assertTrue($result->queueITToken===$queueITToken);
        $this->assertTrue($result->queueITTokenWithoutHash===$queueITToken);
    }

    function test_TryExtractQueueParams_Using_No_QueueitToken_Expect_Null() {
        $result = QueueIT\KnownUserV3\SDK\QueueUrlParams::extractQueueParams(null);
        $this->assertFalse($result === "");
    }
}
?>