<?php

namespace QueueIT\KnownUserV3\SDK;

class QueueUrlParams {

    const TimeStampKey = "ts";
    const ExtendableCookieKey = "ce";
    const CookieValidityMinuteKey = "cv";
    const HashKey = "h";
    const EventIdKey = "e";
    const QueueIdKey = "q";
    const KeyValueSeparatorChar = '_';
    const KeyValueSeparatorGroupChar = '~';

    public $timeStamp = 0;
    public $eventId = "";
    public $hashCode = "";
    public $extendableCookie = false;
    public $cookieValidityMinute = null;
    public $queueITToken = "";
    public $queueITTokenWithoutHash = "";
    public $queueId = "";

    public static function extractQueueParams($queueitToken) {

        $result = new QueueUrlParams();
        $result->queueITToken = $queueitToken;
        $paramsNameValueList = explode(QueueUrlParams::KeyValueSeparatorGroupChar, $result->queueITToken);

        foreach ($paramsNameValueList as $pNameValue) {
            $paramNameValueArr = explode(QueueUrlParams::KeyValueSeparatorChar, $pNameValue);

            switch ($paramNameValueArr[0]) {
                case QueueUrlParams::TimeStampKey: {
                        if (is_numeric($paramNameValueArr[1])) {
                            $result->timeStamp = intval($paramNameValueArr[1]);
                        } else {
                            $result->timeStamp = 0;
                        }
                        break;
                    }
                case QueueUrlParams::CookieValidityMinuteKey: {
                        if (is_numeric($paramNameValueArr[1])) {
                            $result->cookieValidityMinute = intval($paramNameValueArr[1]);
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
            }
        }
        $result->queueITTokenWithoutHash = str_replace(
                QueueUrlParams::KeyValueSeparatorGroupChar
                . QueueUrlParams::HashKey
                . QueueUrlParams::KeyValueSeparatorChar
                . $result->hashCode, "", $result->queueITToken);



        return $result;
    }

}
