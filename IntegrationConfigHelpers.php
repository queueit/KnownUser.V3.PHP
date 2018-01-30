<?php
namespace QueueIT\KnownUserV3\SDK;

interface IIntegrationEvaluator 
{
    public function getMatchedIntegrationConfig(array $customerIntegration, $currentPageUrl, $request);
}

class IntegrationEvaluator implements IIntegrationEvaluator 
{
    public function getMatchedIntegrationConfig(array $customerIntegration, $currentPageUrl, $request) {
        if (!array_key_exists("Integrations", $customerIntegration) || !is_array($customerIntegration["Integrations"])) {
            return null;
        }
        foreach ($customerIntegration["Integrations"] as $integrationConfig) {
            if (!is_array($integrationConfig) || !array_key_exists("Triggers", $integrationConfig) || !is_array($integrationConfig["Triggers"])) {
                continue;
            }

            foreach ($integrationConfig["Triggers"] as $trigger) {
                if (!is_array($trigger)) {
                    return false;
                }
                if ($this->evaluateTrigger($trigger, $currentPageUrl, $request)) {
					return $integrationConfig;
                }
            }
        }
        return null;
    }

    private function evaluateTrigger(array $trigger, $currentPageUrl, $request) {
        if (!array_key_exists("LogicalOperator", $trigger) || !array_key_exists("TriggerParts", $trigger) || !is_array($trigger["TriggerParts"])) {
            return false;
        }
        if ($trigger["LogicalOperator"] === "Or") {
            foreach ($trigger["TriggerParts"] as $triggerPart) {
                if (!is_array($triggerPart)) {
                    return false;
                }
                if ($this->evaluateTriggerPart($triggerPart, $currentPageUrl, $request)) {
                    return true;
                }
            }
            return false;
        } else {
            foreach ($trigger["TriggerParts"] as $triggerPart) {
                if (!is_array($triggerPart)) {
                    return false;
                }
                if (!$this->evaluateTriggerPart($triggerPart, $currentPageUrl, $request)) {
                    return false;
                }
            }
            return true;
        }
    }

    private function evaluateTriggerPart(array $triggerPart, $currentPageUrl, $request) {
        if (!array_key_exists("ValidatorType", $triggerPart)) {
            return false;
        }

        switch ($triggerPart["ValidatorType"]) {
            case "UrlValidator":
                return UrlValidatorHelper::evaluate($triggerPart, $currentPageUrl);
            case "CookieValidator":
                return CookieValidatorHelper::evaluate($triggerPart, $request->getCookieManager()->getCookieArray());
            case "UserAgentValidator":
                return UserAgentValidatorHelper::evaluate($triggerPart, $request->getUserAgent());
            case "HttpHeaderValidator":
                return HttpHeaderValidatorHelper::evaluate($triggerPart, $request->getHeaderArray());
            default:
                return false;
        }
    }
}

class UrlValidatorHelper 
{
    public static function evaluate(array $triggerPart, $url) {
        if (
                !array_key_exists("Operator", $triggerPart) ||
                !array_key_exists("IsNegative", $triggerPart) ||
                !array_key_exists("IsIgnoreCase", $triggerPart) ||
                !array_key_exists("UrlPart", $triggerPart)) {
            return false;
        }

        return ComparisonOperatorHelper::Evaluate(
            $triggerPart["Operator"], 
            $triggerPart["IsNegative"], 
            $triggerPart["IsIgnoreCase"], 
            UrlValidatorHelper::GetUrlPart($triggerPart["UrlPart"], $url), 
            array_key_exists("ValueToCompare",$triggerPart)? $triggerPart["ValueToCompare"]: null,
            array_key_exists("ValuesToCompare",$triggerPart)? $triggerPart["ValuesToCompare"]: null);
    }

    private static function GetUrlPart($urlPart, $url) {
        $urlParts = parse_url($url);
        switch ($urlPart) {
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
    public static function evaluate(array $triggerPart, array $cookieList) {
        if (!array_key_exists("Operator", $triggerPart) ||
            !array_key_exists("IsNegative", $triggerPart) ||
            !array_key_exists("IsIgnoreCase", $triggerPart) ||
            !array_key_exists("CookieName", $triggerPart)) {
            return false;
        }

        $cookieValue = "";
        $cookieName = $triggerPart["CookieName"];
        if ($cookieName !== null && array_key_exists($cookieName, $cookieList) && $cookieList[$cookieName] !== null) {
            $cookieValue = $cookieList[$cookieName];
        }

        return ComparisonOperatorHelper::evaluate(
            $triggerPart["Operator"], 
            $triggerPart["IsNegative"], 
            $triggerPart["IsIgnoreCase"], 
            $cookieValue, 
            array_key_exists("ValueToCompare",$triggerPart)? $triggerPart["ValueToCompare"]: null,
            array_key_exists("ValuesToCompare",$triggerPart)? $triggerPart["ValuesToCompare"]: null);
    }
}

class UserAgentValidatorHelper 
{
    public static function evaluate(array $triggerPart, $userAgent) {
        if (!array_key_exists("Operator", $triggerPart) ||
            !array_key_exists("IsNegative", $triggerPart) ||
            !array_key_exists("IsIgnoreCase", $triggerPart) ) {
            return false;
        }

        return ComparisonOperatorHelper::evaluate(
            $triggerPart["Operator"], 
            $triggerPart["IsNegative"], 
            $triggerPart["IsIgnoreCase"], 
            $userAgent, 
            array_key_exists("ValueToCompare",$triggerPart)? $triggerPart["ValueToCompare"]: null,
            array_key_exists("ValuesToCompare",$triggerPart)? $triggerPart["ValuesToCompare"]: null);
    }
}

class HttpHeaderValidatorHelper 
{
    public static function evaluate(array $triggerPart, array $headerList) {
        if (!array_key_exists("Operator", $triggerPart) ||
            !array_key_exists("IsNegative", $triggerPart) ||
            !array_key_exists("IsIgnoreCase", $triggerPart) ||
            !array_key_exists("HttpHeaderName", $triggerPart)) {
            return false;
        }

        $headerValue = "";
        $headerName = $triggerPart["HttpHeaderName"];
        if ($headerName !== null && array_key_exists(strtolower($headerName), $headerList) && $headerList[strtolower($headerName)] !== null) {
            $headerValue = $headerList[strtolower($headerName)];
        }

        return ComparisonOperatorHelper::evaluate(
            $triggerPart["Operator"], 
            $triggerPart["IsNegative"], 
            $triggerPart["IsIgnoreCase"], 
            $headerValue, 
            array_key_exists("ValueToCompare",$triggerPart)? $triggerPart["ValueToCompare"]: null,
            array_key_exists("ValuesToCompare",$triggerPart)? $triggerPart["ValuesToCompare"]: null);
    }
}

class ComparisonOperatorHelper 
{
    public static function evaluate($opt, $isNegative, $isIgnoreCase, $value, $valueToCompare, $valuesToCompare) {
        $value = !is_null($value) ? $value : "";
        $valueToCompare = !is_null($valueToCompare) ? $valueToCompare : "";
        $valuesToCompare = is_array($valuesToCompare) ? $valuesToCompare : array();
        switch ($opt) {
            case "Equals":
                return ComparisonOperatorHelper::equals($value, $valueToCompare, $isNegative, $isIgnoreCase);
            case "Contains":
                return ComparisonOperatorHelper::contains($value, $valueToCompare, $isNegative, $isIgnoreCase);
            case "StartsWith":
                return ComparisonOperatorHelper::startsWith($value, $valueToCompare, $isNegative, $isIgnoreCase);
            case "EndsWith":
                return ComparisonOperatorHelper::endsWith($value, $valueToCompare, $isNegative, $isIgnoreCase);
            case "MatchesWith":
                return ComparisonOperatorHelper::matchesWith($value, $valueToCompare, $isNegative, $isIgnoreCase);
            case "EqualsAny":
                return ComparisonOperatorHelper::equalsAny($value, $valuesToCompare, $isNegative, $isIgnoreCase);
            case "ContainsAny":
                return ComparisonOperatorHelper::containsAny($value, $valuesToCompare, $isNegative, $isIgnoreCase);
            default:
                return false;
        }
    }

    private static function contains($value, $valueToCompare, $isNegative, $ignoreCase) {
        if ($valueToCompare === "*") {
            return true;
        }

        if ($ignoreCase) {
            $value = strtoupper($value);
            $valueToCompare = strtoupper($valueToCompare);
        }
        $evaluation = strpos($value, $valueToCompare) !== false;
        if ($isNegative) {
            return !$evaluation;
        } else {
            return $evaluation;
        }
    }

    private static function equals($value, $valueToCompare, $isNegative, $ignoreCase) {
        if ($ignoreCase) {
            $value = strtoupper($value);
            $valueToCompare = strtoupper($valueToCompare);
        }
        $evaluation = $value === $valueToCompare;

        if ($isNegative) {
            return !$evaluation;
        } else {
            return $evaluation;
        }
    }
    
    private static function equalsAny($value,array $valuesToCompare, $isNegative, $ignoreCase) {
        foreach($valuesToCompare as $vToCompare)
        {
            if(ComparisonOperatorHelper::equals($value,$vToCompare,false,$ignoreCase))
                return !$isNegative;
        }
        return $isNegative;
    }

    private static function containsAny($value,array $valuesToCompare, $isNegative, $ignoreCase) {
        foreach($valuesToCompare as $vToCompare)
        {
            if(ComparisonOperatorHelper::contains($value,$vToCompare,false,$ignoreCase))
                return !$isNegative;
        }
        return $isNegative;
    }

    private static function endsWith($value, $valueToCompare, $isNegative, $ignoreCase) {
        if ($ignoreCase) {
            $value = strtoupper($value);
            $valueToCompare = strtoupper($valueToCompare);
        }
        $evaluation = false;
        $rLength = strlen($valueToCompare);
        if ($rLength === 0) {
            $evaluation = true;
        } else {
            $evaluation = substr($value, -$rLength) === $valueToCompare;
        }

        if ($isNegative) {
            return !$evaluation;
        } else {
            return $evaluation;
        }
    }

    private static function startsWith($value, $valueToCompare, $isNegative, $ignoreCase) {
        if ($ignoreCase) {
            $value = strtoupper($value);
            $valueToCompare = strtoupper($valueToCompare);
        }
        $evaluation = false;

        $rLength = strlen($valueToCompare);
        $evaluation = (substr($value, 0, $rLength) === $valueToCompare);

        if ($isNegative) {
            return !$evaluation;
        } else {
            return $evaluation;
        }
    }

    private static function matchesWith($value, $valueToCompare, $isNegative, $ignoreCase) {
        if ($ignoreCase) {
            $value = strtoupper($value);
            $valueToCompare = strtoupper($valueToCompare);
        }

        if (preg_match($valueToCompare, $value)) {
            $evaluation = true;
        } else {
            $evaluation = false;
        }

        if ($isNegative) {
            return !$evaluation;
        } else {
            return $evaluation;
        }
    }
}
