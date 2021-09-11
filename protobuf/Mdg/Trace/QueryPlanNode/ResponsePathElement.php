<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: reports.proto

namespace Mdg\Trace\QueryPlanNode;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>mdg.engine.proto.Trace.QueryPlanNode.ResponsePathElement</code>
 */
class ResponsePathElement extends \Google\Protobuf\Internal\Message
{
    protected $id;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $field_name
     *     @type int $index
     * }
     */
    public function __construct($data = NULL) {
        \Metadata\Reports::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string field_name = 1;</code>
     * @return string
     */
    public function getFieldName()
    {
        return $this->readOneof(1);
    }

    public function hasFieldName()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>string field_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setFieldName($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 index = 2;</code>
     * @return int
     */
    public function getIndex()
    {
        return $this->readOneof(2);
    }

    public function hasIndex()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>uint32 index = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setIndex($var)
    {
        GPBUtil::checkUint32($var);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->whichOneof("id");
    }

}
