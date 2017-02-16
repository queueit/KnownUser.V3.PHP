<?php 
namespace QueueIT\KnownUserV3\SDK;
    interface IIntegrationEvaluator
    {
      public function getMatchedIntegrationConfig(array $customerIntegration,
            string $currentPageUrl);
    }

    class IntegrationEvaluator implements  IIntegrationEvaluator
    {
        public function getMatchedIntegrationConfig(array $customerIntegration,
            string $currentPageUrl)
        {
            $matchedIntegration = null;
            if( !array_key_exists("Integrations",$customerIntegration) || !is_array($customerIntegration["Integrations"]) )
                return null;
               foreach ($customerIntegration["Integrations"] as $integrationConfig)
               {

                   if(!is_array($integrationConfig) 
                      || !array_key_exists("Triggers",$integrationConfig)
                      || !is_array($integrationConfig["Triggers"] ))
                        continue;
                          
                    foreach ($integrationConfig["Triggers"] as $trigger)
                    {
                     
                        if(!is_array($trigger ) )
                            return false;
                        if ($this->evaluateTrigger($trigger, $currentPageUrl))
                        {
                            $integrationConfig["Version"] = $customerIntegration["Version"];
                            return $integrationConfig;
                        }
                    }
               }
            return null;
        }

        private function evaluateTrigger(array $trigger, string $currentPageUrl):bool
        {
            if( !array_key_exists("LogicalOperator", $trigger)
                || !array_key_exists("TriggerParts", $trigger)
                || !is_array($trigger["TriggerParts"] )  )
            {
                 return false;
            }
            if ($trigger["LogicalOperator"] === "Or")
            {

                foreach ($trigger["TriggerParts"] as $triggerPart)
                {
                       if(!is_array($triggerPart ))
                         return false;
                    if ($this->evaluateTriggerPart($triggerPart, $currentPageUrl))
                        return true;
                }
                return false;
            }
            else
            {
   
               foreach ($trigger["TriggerParts"] as $triggerPart)
                {
                    if(!is_array($triggerPart ))
                         return false;
                    if (!$this->evaluateTriggerPart($triggerPart, $currentPageUrl))
                        return false;
                }
                return true;
            }
        }

        private function evaluateTriggerPart(array $triggerPart, string $currentPageUrl):bool
        {
            if(!array_key_exists("ValidatorType", $triggerPart)  )
            {
                 return false;
            }

            switch ($triggerPart["ValidatorType"])
            {
                case "UrlValidator":
                    return UrlValidatorHelper::evaluate($triggerPart, $currentPageUrl);
                case "CookieValidator":
                    return CookieValidatorHelper::evaluate($triggerPart);
                default:
                    return false;
            }
        }
    }


    class UrlValidatorHelper
    {
        public static function evaluate(array $triggerPart, string $url):bool
        {
            if( 
                 !array_key_exists("Operator", $triggerPart)|| 
                 !array_key_exists("IsNegative", $triggerPart) ||
                 !array_key_exists("IsIgnoreCase", $triggerPart) ||
                 !array_key_exists("ValueToCompare", $triggerPart)|| 
                !array_key_exists("UrlPart",$triggerPart))
                {
                return false;
                }
            return ComparisonOperatorHelper::Evaluate(
                $triggerPart["Operator"],
                $triggerPart["IsNegative"]  ,
                $triggerPart["IsIgnoreCase"],
                UrlValidatorHelper::GetUrlPart($triggerPart["UrlPart"], $url),
                $triggerPart["ValueToCompare"]);
        }

        private static function GetUrlPart(string $urlPart, string $url):string
        {
            $urlParts = parse_url($url);
            switch ($urlPart)
            {
                case "PagePath":
                    return $urlParts['path'];
                case "PageUrl":
                    return $url;
                case "HostName":
                    return $urlParts['host'];
                default :
                return "";
            }
          
        }
    }

     class CookieValidatorHelper
    {
        public static $getCookieCallback=null;
        public static function getCookieFunc(string $cookieName)
        {
            if(CookieValidatorHelper::$getCookieCallback!==null)
            {
                 return (CookieValidatorHelper::$getCookieCallback)($cookieName);
            }
            else
            {
                if(isset($_COOKIE[$cookieName]))
                    return  $_COOKIE[$cookieName];
                else
                    return null;
            }
        }
        public static function evaluate(array $triggerPart):bool
        {
               if(  
                   !array_key_exists("Operator", $triggerPart)|| 
                 !array_key_exists("IsNegative", $triggerPart) ||
                 !array_key_exists("IsIgnoreCase", $triggerPart) ||
                 !array_key_exists("ValueToCompare", $triggerPart)|| 
                !array_key_exists("CookieName",$triggerPart))
                {
                return false;
                }

            $cookieValue= "";
            if(CookieValidatorHelper::getCookieFunc($triggerPart["CookieName"])!==null)
                $cookieValue = CookieValidatorHelper::getCookieFunc($triggerPart["CookieName"]);

            return ComparisonOperatorHelper::evaluate(
                $triggerPart["Operator"],
                $triggerPart["IsNegative"],
                $triggerPart["IsIgnoreCase"] ,
                $cookieValue,
                $triggerPart["ValueToCompare"]);
        }


    }

    class ComparisonOperatorHelper
    {
        public static function evaluate(string $opt, bool $isNegative, bool $isIgnoreCase, string $left, string $right):bool
        {
            $left = !is_null($left)?$left:"";
            $right = !is_null($right)?$right:"";
            
            switch ($opt)
            {
                case "EqualS":
                    return ComparisonOperatorHelper::equalS($left, $right, $isNegative, $isIgnoreCase);
                case "Contains":
                    return ComparisonOperatorHelper::contains($left, $right, $isNegative, $isIgnoreCase);
                case "StartsWith":
                    return ComparisonOperatorHelper::startsWith($left, $right, $isNegative, $isIgnoreCase);
                case "EndsWith":
                    return ComparisonOperatorHelper::endsWith($left, $right, $isNegative, $isIgnoreCase);
                case "MatchesWith":
                    return ComparisonOperatorHelper::matchesWith($left, $right, $isNegative, $isIgnoreCase);
                default:
                    return false;
            }
     }

        private static function contains(string $left, string $right, bool $isNegative, bool $ignoreCase):bool
        {
            if ($right === "*")
                return true;

            if ($ignoreCase)
            {
                $left= strtoupper($left);
                $right = strtoupper($right);
            }
            $evaluation = strpos($left,$right) !==false;
            if ($isNegative)
                return !$evaluation;
            else
                return $evaluation;
        }

        private static function equalS(string $left, string $right, bool $isNegative, bool $ignoreCase):bool
        {
            if ($ignoreCase)
            {
                $left= strtoupper($left);
                $right = strtoupper($right);
            }
            $evaluation = $left === $right;

            if ($isNegative)
                return !$evaluation;
            else
                return $evaluation;
        }

        private static function endsWith(string $left, string $right, bool $isNegative, bool $ignoreCase):bool
        {
            if ($ignoreCase)
            {
                $left= strtoupper($left);
                $right = strtoupper($right);
            }
            $evaluation = false;
            $rLength = strlen($right);
            if ($rLength=== 0) {
                $evaluation= true;
            }
            else
            {
                    $evaluation=substr($left, -$rLength) === $right;
            }

            if ($isNegative)
                return !$evaluation;
            else
                return $evaluation;
        }

        private static function startsWith(string $left, string $right, bool $isNegative, bool $ignoreCase):bool
        {
            if ($ignoreCase)
            {
                $left= strtoupper($left);
                $right = strtoupper($right);
            }
            $evaluation = false;

            $rLength = strlen($right);
            $evaluation = (substr($left, 0, $rLength) === $right);

            if ($isNegative)
                return !$evaluation;
            else
                return $evaluation;
        }

        private static function matchesWith(string $left, string $right, bool $isNegative, bool $ignoreCase):bool
        {
            if ($ignoreCase)
            {
                $left= strtoupper($left);
                $right = strtoupper($right);
            }

            if(preg_match($right,$left))
                $evaluation= true;
            else
                $evaluation= false;

            if ($isNegative)
                return !$evaluation;
            else
                return $evaluation;
        }
    }
    ?>