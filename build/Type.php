<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Type</code>
 */
class Type extends \Google\Protobuf\Internal\Message
{
    /**
     * required; eg "User" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>repeated .Field field = 2;</code>
     */
    private $field;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *           required; eg "User" for User.email:String!
     *     @type \Field[]|\Google\Protobuf\Internal\RepeatedField $field
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * required; eg "User" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * required; eg "User" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .Field field = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Generated from protobuf field <code>repeated .Field field = 2;</code>
     * @param \Field[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setField($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Field::class);
        $this->field = $arr;

        return $this;
    }

}

