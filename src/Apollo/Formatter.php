<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use DateTime;
use DateTimeImmutable;
use Exception;
use Google\Protobuf\Timestamp;
use GraphQlTools\Data\Models\AdditionalExecutionInformation;
use GraphQlTools\Data\Models\ExecutionTrace;
use GraphQlTools\Data\Models\SchemaInformation;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Http;
use Protobuf\Trace;

final class Formatter
{
    public const BLACKLISTED_HEADERS = ['authorization', 'cookie', 'set-cookie'];
    public const BLACKLISTED_VARIABLE_VALUES = ['secret', 'token'];

    public static function singleTraceToProtobuf(ExecutionTrace $executionTrace, ?AdditionalExecutionInformation $information = null): array
    {
        $trace = new Trace();
        $trace->setDurationNs($executionTrace->durationNs);
        $trace->setStartTime(self::toTimestamp($executionTrace->startTime));
        $trace->setEndTime(self::toTimestamp($executionTrace->endTime));

        if ($information) {
            self::setClientInformation($trace, $information);
            self::setDetails($trace, $information);
        }

        self::setHttp($trace, $information);

        // Create the Protobuf tree
        $trace->setRoot(RootNode::createFromFieldTraces($executionTrace->executionResolvers)->toProtobuf());

        return [
            $executionTrace->queryId, $trace
        ];
    }

    private static function setHttp(Trace $trace, ?AdditionalExecutionInformation $information): void {
        $http = new Trace\HTTP();
        $http->setMethod(Trace\HTTP\Method::POST);

        if ($information?->requestHeaders) {
            $filteredHeaders = $information->allHeaders(self::BLACKLISTED_HEADERS);
            $http->setRequestHeaders(
                array_map(fn($headerValue) => (new Trace\HTTP\Values())->setValue(Http::headerValues($headerValue)), $filteredHeaders)
            );
        }

        $trace->setHttp($http);
    }

    private static function setDetails(Trace $trace, AdditionalExecutionInformation $information): void
    {
        if ($information?->variables) {
            try {
                $filteredVariables = Arrays::blacklistKeys($information->variables, self::BLACKLISTED_VARIABLE_VALUES);
                $trace->setDetails(new Trace\Details([
                    'variables_json' => array_map(fn(mixed $value): string => (string) json_encode($value), $filteredVariables),
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

    public static function createReport(SchemaInformation $schemaInformation, array $tracesWithAdditionalInformation): Report {
        $report = new Report();

        $report->setHeader(
            (new ReportHeader())->setGraphRef($schemaInformation->graphReference)
        );

        $tracesAndStats = [];
        foreach ($tracesWithAdditionalInformation as $traceData) {
            [$id, $trace] = self::singleTraceToProtobuf(
                $traceData instanceof ExecutionTrace ? $traceData : $traceData[0],
                $traceData instanceof ExecutionTrace? null : ($traceData[1] ?? null),
            );
            $tracesAndStats[$id][] = $trace;
        }

        $report->setTracesPerQuery(
            array_map(fn($traces) => (new TracesAndStats())->setTrace($traces), $tracesAndStats)
        );

        return $report;
    }

    public static function fullTraceReport(): Report {
        $report = new Report();
        $report->setHeader(new ReportHeader());
    }

}