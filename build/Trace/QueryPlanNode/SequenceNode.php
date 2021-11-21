<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

# Modified Namespace Prefix
namespace Protobuf\Trace\QueryPlanNode;


use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * This represents a set of nodes to be executed sequentially by the Gateway executor
 *
 * Generated from protobuf message <code>Trace.QueryPlanNode.SequenceNode</code>
 */
class SequenceNode extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .Trace.QueryPlanNode nodes = 1;</code>
     */
    private $nodes;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Protobuf\Trace\QueryPlanNode[]|\Google\Protobuf\Internal\RepeatedField $nodes
     * }
     */
    public function __construct($data = NULL) {
        \Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.QueryPlanNode nodes = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.QueryPlanNode nodes = 1;</code>
     * @param \Protobuf\Trace\QueryPlanNode[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setNodes($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Protobuf\Trace\QueryPlanNode::class);
        $this->nodes = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.


