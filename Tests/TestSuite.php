<?php
require_once( __DIR__ . '/vendor/simpletest/simpletest/autorun.php');

class AllTests extends TestSuite {
    function AllTests() {
        
        // $this->TestSuite('All tests');
       $this->addFile('Tests/IntegrationConfigHelpersTest.php');
       $this->addFile('Tests/KnownUserTest.php');
       $this->addFile('Tests/QueueUrlParamsTest.php');
       $this->addFile('Tests/UserInQueueServiceTest.php');
       $this->addFile('Tests/UserInQueueStateCookieRepositoryTest.php');
    }
}
?>