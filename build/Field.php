<?php
# Generated Namespace Prefix
namespace Protobuf;

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>Field</code>
 */
class Field extends \Google\Protobuf\Internal\Message
{
    /**
     * required; eg "email" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 2;</code>
     */
    protected $name = '';
    /**
     * required; eg "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string return_type = 3;</code>
     */
    protected $return_type = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *           required; eg "email" for User.email:String!
     *     @type string $return_type
     *           required; eg "String!" for User.email:String!
     * }
     */
    public function __construct($data = NULL) {
        \Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * required; eg "email" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 2;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * required; eg "email" for User.email:String!
     *
     * Generated from protobuf field <code>string name = 2;</code>
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
     * required; eg "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string return_type = 3;</code>
     * @return string
     */
    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * required; eg "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string return_type = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setReturnType($var)
    {
        GPBUtil::checkString($var, True);
        $this->return_type = $var;

        return $this;
    }

}

