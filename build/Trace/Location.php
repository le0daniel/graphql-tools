<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

# Modified Namespace Prefix
namespace Protobuf\Trace;


use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Trace.Location</code>
 */
class Location extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>uint32 line = 1;</code>
     */
    protected $line = 0;
    /**
     * Generated from protobuf field <code>uint32 column = 2;</code>
     */
    protected $column = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $line
     *     @type int $column
     * }
     */
    public function __construct($data = NULL) {
        \Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>uint32 line = 1;</code>
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Generated from protobuf field <code>uint32 line = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setLine($var)
    {
        GPBUtil::checkUint32($var);
        $this->line = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 column = 2;</code>
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Generated from protobuf field <code>uint32 column = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setColumn($var)
    {
        GPBUtil::checkUint32($var);
        $this->column = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
# Removed class alias

