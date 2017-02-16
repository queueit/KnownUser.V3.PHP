<?php 
namespace QueueIT\KnownUserV3\SDK;
interface IUserInQueueStateRepository
{
   public function store(string $eventId,
            bool $isStateExtendable,
             int $cookieValidityMinute,
            string $cookieDomain,
           
            string $customerSecretKey):void;

         public function hasValidState(string $eventId,
            string $customerSecretKey):bool;

          public function isStateExtendable(
              string $eventId,string $secretKey):bool;

          public function cancelQueueCookie(
           string $eventId):void;

         public function extendQueueCookie(
         
            string $eventId,
            int $cookieValidityMinute,
            string $cookieDomain,
            string $secretKey
            ):void; 

}
class UserInQueueStateCookieRepository implements IUserInQueueStateRepository
{
   
    
     const  _QueueITDataKey = "QueueITAccepted-SDFrts345E-V3";
     public $setCookieCallback;
     public $getCookieCallback;
     public function setCookieFunc(string $name , $value  ,
                                            int $expire = 0 , string $path = "" , 
                                            string $domain = "" ,
                                            bool $httponly = false )
     {
            if(isset($this->setCookieCallback))
                ($this->setCookieCallback)($name,$value,$expire,$path,$domain,$secure,$httponly);
            else
            {
                setcookie($name,$value,$expire,$path,$domain,false,$httponly);
            }
     }
     public function getCookieFunc(string $cookieName)
     {
             if(isset($this->getCookieCallback))
             {
                return ($this->getCookieCallback)($cookieName);
             }
            else
            {
                if(isset($_COOKIE[$cookieName]))
                    return  $_COOKIE[$cookieName];
                else
                    return null;
            }
     }

 
    public function cancelQueueCookie(
           string $eventId):void
    {
            $cookieKey =self::getCookieKey($eventId);
            if ($this->getCookieFunc($cookieKey)!==null)
            {
                $this->setCookieFunc($cookieKey,null,-1);
            }
    }
    private  static function  getCookieKey($eventId):string
    {
        return self::_QueueITDataKey.'_'.$eventId;
    }

    public function store(
            string $eventId,
            bool $isStateExtendable,
            int $cookieValidityMinute,
            string $cookieDomain,
          
            string $secretKey):void
        {
            
            $cookieKey =self::getCookieKey($eventId);
           
            $expirationTime = strval(time() + ( $cookieValidityMinute  * 60));
            $isStateExtendableString = ($isStateExtendable)?'true' : 'false';
            
            $cookieValue= $this->createCookieValue($isStateExtendableString, $expirationTime, $secretKey);
         
            $this->setCookieFunc($cookieKey,$cookieValue, time() + (24*60*60),"/", $cookieDomain,  true);
        }
        private function createCookieValue(string $isStateExtendable, string $expirationTime, string $secretKey):string
        {
            $hashValue=hash_hmac('sha256', $isStateExtendable .$expirationTime  , $secretKey);
            $cookieValue= "IsCookieExtendable=".$isStateExtendable ."&"."Expires=". $expirationTime."&"."Hash=".$hashValue;
            return $cookieValue;
        }
        private function getCookieNameValueMap(string $cookieValue):array
        {
            $result = array();
            $cookieNameValues = explode("&",$cookieValue);
 
            if(count($cookieNameValues)<3)
                return $result; 
            for($i=0;$i<3;++$i)
            {
               $arr = explode("=",$cookieNameValues[$i]);
               if(count($arr)==2)
                    $result[$arr[0]]=$arr[1];

            }

            return $result;
        }
        private function isCookieValid(array $cookieNameValueMap, string $secretKey)
        {
              
            if(!isset($cookieNameValueMap["IsCookieExtendable"]))
                return false;
            if(!isset($cookieNameValueMap["Expires"]))
                return false;
            if(!isset($cookieNameValueMap["Hash"]))
                return false;
         
            $hashValue=hash_hmac('sha256',$cookieNameValueMap["IsCookieExtendable"].$cookieNameValueMap["Expires"]  , $secretKey);
  
            if($hashValue!==$cookieNameValueMap["Hash"])
                return false;
                   
            if(intval($cookieNameValueMap["Expires"]) < time())
                return false;
                
            return true;
        }

        public function extendQueueCookie(
            string $eventId,
            int $cookieValidityMinute,
            string $cookieDomain,
            string $secretKey):void
        {
           $cookieKey =self::getCookieKey($eventId);
            if ($this->getCookieFunc($cookieKey)===null)
                return;
            $cookieNameValueMap = $this->getCookieNameValueMap($this->getCookieFunc($cookieKey));
            if (!$this->isCookieValid($cookieNameValueMap,$secretKey))
                return;

            $expirationTime = strval(time() + ( $cookieValidityMinute  * 60));
            $cookieValue= $this->createCookieValue($cookieNameValueMap["IsCookieExtendable"], $expirationTime, $secretKey);
            $this->setCookieFunc($cookieKey,$cookieValue, time() + (24*60*60),"/", $cookieDomain,  true);
        }


       public function hasValidState(string $eventId, string $secretKey):bool
        {
            $cookieKey =self::getCookieKey($eventId);
            if ($this->getCookieFunc($cookieKey)===null)
                return false;
            $cookieNameValueMap = $this->getCookieNameValueMap($this->getCookieFunc($cookieKey));
            if (!$this->isCookieValid($cookieNameValueMap,$secretKey))
                return false;
            return true;
        }
         public function isStateExtendable(
            string $eventId, string $secretKey):bool
        {
            $cookieKey =self::getCookieKey($eventId);
            if ($this->getCookieFunc($cookieKey)===null)
                return false;
            $cookieNameValueMap = $this->getCookieNameValueMap($this->getCookieFunc($cookieKey));
            if (!$this->isCookieValid($cookieNameValueMap,$secretKey))
                return false;
            return $cookieNameValueMap["IsCookieExtendable"]=== 'true';
        }

}
?>
