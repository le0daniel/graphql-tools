<?php
# Generated Namespace Prefix
namespace GraphQlTools\Protobuf;

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>ContextualizedQueryLatencyStats</code>
 */
class ContextualizedQueryLatencyStats extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.QueryLatencyStats query_latency_stats = 1;</code>
     */
    protected $query_latency_stats = null;
    /**
     * Generated from protobuf field <code>.StatsContext context = 2;</code>
     */
    protected $context = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \GraphQlTools\Protobuf\QueryLatencyStats $query_latency_stats
     *     @type \GraphQlTools\Protobuf\StatsContext $context
     * }
     */
    public function __construct($data = NULL) {
        \GraphQlTools\Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.QueryLatencyStats query_latency_stats = 1;</code>
     * @return \GraphQlTools\Protobuf\QueryLatencyStats|null
     */
    public function getQueryLatencyStats()
    {
        return $this->query_latency_stats;
    }

    public function hasQueryLatencyStats()
    {
        return isset($this->query_latency_stats);
    }

    public function clearQueryLatencyStats()
    {
        unset($this->query_latency_stats);
    }

    /**
     * Generated from protobuf field <code>.QueryLatencyStats query_latency_stats = 1;</code>
     * @param \GraphQlTools\Protobuf\QueryLatencyStats $var
     * @return $this
     */
    public function setQueryLatencyStats($var)
    {
        GPBUtil::checkMessage($var, \GraphQlTools\Protobuf\QueryLatencyStats::class);
        $this->query_latency_stats = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.StatsContext context = 2;</code>
     * @return \GraphQlTools\Protobuf\StatsContext|null
     */
    public function getContext()
    {
        return $this->context;
    }

    public function hasContext()
    {
        return isset($this->context);
    }

    public function clearContext()
    {
        unset($this->context);
    }

    /**
     * Generated from protobuf field <code>.StatsContext context = 2;</code>
     * @param \GraphQlTools\Protobuf\StatsContext $var
     * @return $this
     */
    public function setContext($var)
    {
        GPBUtil::checkMessage($var, \GraphQlTools\Protobuf\StatsContext::class);
        $this->context = $var;

        return $this;
    }

}
