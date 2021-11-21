<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

# Modified Namespace Prefix
namespace Protobuf\Trace;


use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * represents a node in the query plan, under which there is a trace tree for that service fetch.
 * In particular, each fetch node represents a call to an implementing service, and calls to implementing
 * services may not be unique. See https://github.com/apollographql/apollo-server/blob/main/packages/apollo-gateway/src/QueryPlan.ts
 * for more information and details.
 *
 * Generated from protobuf message <code>Trace.QueryPlanNode</code>
 */
class QueryPlanNode extends \Google\Protobuf\Internal\Message
{
    protected $node;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Protobuf\Trace\QueryPlanNode\SequenceNode $sequence
     *     @type \Protobuf\Trace\QueryPlanNode\ParallelNode $parallel
     *     @type \Protobuf\Trace\QueryPlanNode\FetchNode $fetch
     *     @type \Protobuf\Trace\QueryPlanNode\FlattenNode $flatten
     * }
     */
    public function __construct($data = NULL) {
        \Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.SequenceNode sequence = 1;</code>
     * @return \Protobuf\Trace\QueryPlanNode\SequenceNode|null
     */
    public function getSequence()
    {
        return $this->readOneof(1);
    }

    public function hasSequence()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.SequenceNode sequence = 1;</code>
     * @param \Protobuf\Trace\QueryPlanNode\SequenceNode $var
     * @return $this
     */
    public function setSequence($var)
    {
        GPBUtil::checkMessage($var, \Protobuf\Trace\QueryPlanNode\SequenceNode::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.ParallelNode parallel = 2;</code>
     * @return \Protobuf\Trace\QueryPlanNode\ParallelNode|null
     */
    public function getParallel()
    {
        return $this->readOneof(2);
    }

    public function hasParallel()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.ParallelNode parallel = 2;</code>
     * @param \Protobuf\Trace\QueryPlanNode\ParallelNode $var
     * @return $this
     */
    public function setParallel($var)
    {
        GPBUtil::checkMessage($var, \Protobuf\Trace\QueryPlanNode\ParallelNode::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.FetchNode fetch = 3;</code>
     * @return \Protobuf\Trace\QueryPlanNode\FetchNode|null
     */
    public function getFetch()
    {
        return $this->readOneof(3);
    }

    public function hasFetch()
    {
        return $this->hasOneof(3);
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.FetchNode fetch = 3;</code>
     * @param \Protobuf\Trace\QueryPlanNode\FetchNode $var
     * @return $this
     */
    public function setFetch($var)
    {
        GPBUtil::checkMessage($var, \Protobuf\Trace\QueryPlanNode\FetchNode::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.FlattenNode flatten = 4;</code>
     * @return \Protobuf\Trace\QueryPlanNode\FlattenNode|null
     */
    public function getFlatten()
    {
        return $this->readOneof(4);
    }

    public function hasFlatten()
    {
        return $this->hasOneof(4);
    }

    /**
     * Generated from protobuf field <code>.Trace.QueryPlanNode.FlattenNode flatten = 4;</code>
     * @param \Protobuf\Trace\QueryPlanNode\FlattenNode $var
     * @return $this
     */
    public function setFlatten($var)
    {
        GPBUtil::checkMessage($var, \Protobuf\Trace\QueryPlanNode\FlattenNode::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->whichOneof("node");
    }

}

// Adding a class alias for backwards compatibility with the previous class name.


