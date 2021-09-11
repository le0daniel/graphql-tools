<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use DateTime;
use DateTimeImmutable;
use Exception;
use Google\Protobuf\Timestamp;
use GraphQlTools\Immutable\AdditionalExecutionInformation;
use GraphQlTools\Immutable\ExecutionTrace;
use GraphQlTools\Utility\Arrays;
use Mdg\Trace;

final class Formatter
{
    public static function singleTraceToProtobuf(ExecutionTrace $executionTrace, ?AdditionalExecutionInformation $information = null): Trace
    {
        $result = new Trace();
        $result->setDurationNs($executionTrace->durationNs);
        $result->setStartTime(self::toTimestamp($executionTrace->startTime));
        $result->setEndTime(self::toTimestamp($executionTrace->endTime));

        if ($information) {
            self::setClientInformation($result, $information);
            self::setDetails($result, $information);
        }

        self::setHttp($result, $information);

        // Create the Protobuf tree
        $result->setRoot(RootNode::createFromResolverTrace($executionTrace->executionResolvers)->toProtobuf());
        return $result;
    }

    private static function setHttp(Trace $trace, ?AdditionalExecutionInformation $information): void {
        $http = new Trace\HTTP();
        $http->setMethod(Trace\HTTP\Method::POST);
        $trace->setHttp($http);
    }

    private static function setDetails(Trace $trace, AdditionalExecutionInformation $information): void
    {
        if ($information?->variables) {
            try {
                $trace->setDetails(new Trace\Details([
                    'variables_json' => Arrays::blacklistKeys($information->variables, ['secret', 'token'])
                ]));
            } catch (Exception) {
            }
        }
    }

    private static function setClientInformation(Trace $trace, AdditionalExecutionInformation $information): void
    {
        if ($information->clientName) {
            $trace->setClientName($information->clientName);
        }

        if ($information->clientVersion) {
            $trace->setClientVersion($information->clientVersion);
        }
    }

    private static function toTimestamp(DateTimeImmutable $dateTime): Timestamp
    {
        $timestamp = new Timestamp();
        $timestamp->fromDateTime(DateTime::createFromImmutable($dateTime));
        return $timestamp;
    }

    //public static function fullTraceReport(): Report {
    //    $report = new Report();
    //    $report->setHeader();

    //    $message = new Message

    //    //$mapField = new MapField();
    //    //$mapField->

    //    $report->setTracesPerQuery();
    //}

}