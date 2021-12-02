<?php

namespace QueueIT\KnownUserV3\SDK;

class Utils
{
    public static function isNullOrEmptyString($value)
    {
        return (!isset($value) || trim($value) === '');
    }

    public static function boolToString($value)
    {
        if(is_null($value)) {
            return "null";
        }

        return $value ? "true" : "false";
    }
}

class QueueUrlParams
{
    const TimeStampKey = "ts";
    const ExtendableCookieKey = "ce";
    const CookieValidityMinutesKey = "cv";
    const HashKey = "h";
    const EventIdKey = "e";
    const QueueIdKey = "q";
    const RedirectTypeKey = "rt";
    const KeyValueSeparatorChar = '_';
    const KeyValueSeparatorGroupChar = '~';

    public $timeStamp = 0;
    public $eventId = "";
    public $hashCode = "";
    public $extendableCookie = false;
    public $cookieValidityMinutes = null;
    public $queueITToken = "";
    public $queueITTokenWithoutHash = "";
    public $queueId = "";
    public $redirectType = "";

    public static function extractQueueParams($queueitToken) 
    {
        if (Utils::isNullOrEmptyString($queueitToken)) {
            return null;
        }

        $result = new QueueUrlParams();
        $result->queueITToken = $queueitToken;
        $paramsNameValueList = explode(QueueUrlParams::KeyValueSeparatorGroupChar, $result->queueITToken);

        foreach ($paramsNameValueList as $pNameValue) {
            $paramNameValueArr = explode(QueueUrlParams::KeyValueSeparatorChar, $pNameValue);

            if (count($paramNameValueArr) != 2) {
                continue;
            }

            switch ($paramNameValueArr[0]) {
                case QueueUrlParams::TimeStampKey: {
                        if (is_numeric($paramNameValueArr[1])) {
                            $result->timeStamp = intval($paramNameValueArr[1]);
                        } else {
                            $result->timeStamp = 0;
                        }
                        break;
                    }
                case QueueUrlParams::CookieValidityMinutesKey: {
                        if (is_numeric($paramNameValueArr[1])) {
                            $result->cookieValidityMinutes = intval($paramNameValueArr[1]);
                        }
                        break;
                    }
                case QueueUrlParams::EventIdKey: {
                        $result->eventId = $paramNameValueArr[1];
                        break;
                    }
                case QueueUrlParams::ExtendableCookieKey: {
                        $result->extendableCookie = $paramNameValueArr[1] === 'True' || $paramNameValueArr[1] === 'true';
                        break;
                    }
                case QueueUrlParams::HashKey: {
                        $result->hashCode = $paramNameValueArr[1];
                        break;
                    }
                case QueueUrlParams::QueueIdKey: {
                        $result->queueId = $paramNameValueArr[1];
                        break;
                    }
                case QueueUrlParams::RedirectTypeKey: {
                        $result->redirectType = $paramNameValueArr[1];
                        break;
                    }
            }
        }

        $result->queueITTokenWithoutHash = str_replace(
            QueueUrlParams::KeyValueSeparatorGroupChar
                . QueueUrlParams::HashKey
                . QueueUrlParams::KeyValueSeparatorChar
                . $result->hashCode,
            "",
            $result->queueITToken
        );

        return $result;
    }
}

class ConnectorDiagnostics
{
    public $isEnabled = false;
    public $hasError = false;
    public $validationResult = null;

    private function setStateWithSetupError() 
    {
        $this->hasError = true;
        $this->validationResult = new RequestValidationResult(
            "ConnectorDiagnosticsRedirect",
            null,
            null,
            "https://api2.queue-it.net/diagnostics/connector/error/?code=setup",
            null,
            null
        );
    }

    private function setStateWithTokenError($customerId, $errorCode) 
    {
        $this->hasError = true;
        $this->validationResult = new RequestValidationResult(
            "ConnectorDiagnosticsRedirect",
            null,
            null,
            "https://" . $customerId . ".api2.queue-it.net/" . $customerId . "/diagnostics/connector/error/?code=" . $errorCode,
            null,
            null
        );
    }

    public static function verify($customerId, $secretKey, $queueitToken)
    {
        $diagnostics = new ConnectorDiagnostics();

        $queueParams = QueueUrlParams::extractQueueParams($queueitToken);

        if ($queueParams == null)
            return $diagnostics;

        if (Utils::isNullOrEmptyString($queueParams->redirectType))
            return $diagnostics;

        if (strtolower($queueParams->redirectType) != "debug")
            return $diagnostics;

        if (Utils::isNullOrEmptyString($customerId) || Utils::isNullOrEmptyString($secretKey)) {
            $diagnostics->setStateWithSetupError();
            return $diagnostics;
        }

        $calculatedHash = hash_hmac('sha256', $queueParams->queueITTokenWithoutHash, $secretKey);
        if (strtoupper($calculatedHash) != strtoupper($queueParams->hashCode)) {
            $diagnostics->setStateWithTokenError($customerId, "hash");
            return $diagnostics;
        }

        if ($queueParams->timeStamp < time()) {
            $diagnostics->setStateWithTokenError($customerId, "timestamp");
            return $diagnostics;
        }

        $diagnostics->isEnabled = true;

        return $diagnostics;
    }
}
