<?php
require_once( __DIR__ . '/../KnownUser.php');

class HttpRequestProviderMock implements QueueIT\KnownUserV3\SDK\IHttpRequestProvider
{
    public $userAgent;
	public $userHostAddress;
    public $cookieManager;
    public $absoluteUri;
	public $headerArray;

    public function getUserAgent() {
        return $this->userAgent;
    }
	public function getUserHostAddress() {
		return $this->userHostAddress;
	}
    public function getCookieManager() {
        return $this->cookieManager;
    }
    public function getAbsoluteUri() {
        return $this->absoluteUri;
    }
    public function getHeaderArray() {
        if($this->headerArray==NULL)
            return array();
        return $this->headerArray;
    }
}
?>