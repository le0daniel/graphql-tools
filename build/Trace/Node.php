<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

# Modified Namespace Prefix
namespace Protobuf\Trace;


use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * We store information on each resolver execution as a Node on a tree.
 * The structure of the tree corresponds to the structure of the GraphQL
 * response; it does not indicate the order in which resolvers were
 * invoked.  Note that nodes representing indexes (and the root node)
 * don't contain all Node fields (eg types and times).
 *
 * Generated from protobuf message <code>Trace.Node</code>
 */
class Node extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string original_field_name = 14;</code>
     */
    protected $original_field_name = '';
    /**
     * The field's return type; e.g. "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string type = 3;</code>
     */
    protected $type = '';
    /**
     * The field's parent type; e.g. "User" for User.email:String!
     *
     * Generated from protobuf field <code>string parent_type = 13;</code>
     */
    protected $parent_type = '';
    /**
     * Generated from protobuf field <code>.Trace.CachePolicy cache_policy = 5;</code>
     */
    protected $cache_policy = null;
    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 start_time = 8;</code>
     */
    protected $start_time = 0;
    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 end_time = 9;</code>
     */
    protected $end_time = 0;
    /**
     * Generated from protobuf field <code>repeated .Trace.Error error = 11;</code>
     */
    private $error;
    /**
     * Generated from protobuf field <code>repeated .Trace.Node child = 12;</code>
     */
    private $child;
    protected $id;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $response_name
     *     @type int $index
     *     @type string $original_field_name
     *     @type string $type
     *           The field's return type; e.g. "String!" for User.email:String!
     *     @type string $parent_type
     *           The field's parent type; e.g. "User" for User.email:String!
     *     @type \Protobuf\Trace\CachePolicy $cache_policy
     *     @type int|string $start_time
     *           relative to the trace's start_time, in ns
     *     @type int|string $end_time
     *           relative to the trace's start_time, in ns
     *     @type \Protobuf\Trace\Error[]|\Google\Protobuf\Internal\RepeatedField $error
     *     @type \Protobuf\Trace\Node[]|\Google\Protobuf\Internal\RepeatedField $child
     * }
     */
    public function __construct($data = NULL) {
        \Protobuf\GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string response_name = 1;</code>
     * @return string
     */
    public function getResponseName()
    {
        return $this->readOneof(1);
    }

    public function hasResponseName()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>string response_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setResponseName($var)
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
     * Generated from protobuf field <code>string original_field_name = 14;</code>
     * @return string
     */
    public function getOriginalFieldName()
    {
        return $this->original_field_name;
    }

    /**
     * Generated from protobuf field <code>string original_field_name = 14;</code>
     * @param string $var
     * @return $this
     */
    public function setOriginalFieldName($var)
    {
        GPBUtil::checkString($var, True);
        $this->original_field_name = $var;

        return $this;
    }

    /**
     * The field's return type; e.g. "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string type = 3;</code>
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * The field's return type; e.g. "String!" for User.email:String!
     *
     * Generated from protobuf field <code>string type = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkString($var, True);
        $this->type = $var;

        return $this;
    }

    /**
     * The field's parent type; e.g. "User" for User.email:String!
     *
     * Generated from protobuf field <code>string parent_type = 13;</code>
     * @return string
     */
    public function getParentType()
    {
        return $this->parent_type;
    }

    /**
     * The field's parent type; e.g. "User" for User.email:String!
     *
     * Generated from protobuf field <code>string parent_type = 13;</code>
     * @param string $var
     * @return $this
     */
    public function setParentType($var)
    {
        GPBUtil::checkString($var, True);
        $this->parent_type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Trace.CachePolicy cache_policy = 5;</code>
     * @return \Protobuf\Trace\CachePolicy|null
     */
    public function getCachePolicy()
    {
        return $this->cache_policy;
    }

    public function hasCachePolicy()
    {
        return isset($this->cache_policy);
    }

    public function clearCachePolicy()
    {
        unset($this->cache_policy);
    }

    /**
     * Generated from protobuf field <code>.Trace.CachePolicy cache_policy = 5;</code>
     * @param \Protobuf\Trace\CachePolicy $var
     * @return $this
     */
    public function setCachePolicy($var)
    {
        GPBUtil::checkMessage($var, \Protobuf\Trace\CachePolicy::class);
        $this->cache_policy = $var;

        return $this;
    }

    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 start_time = 8;</code>
     * @return int|string
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 start_time = 8;</code>
     * @param int|string $var
     * @return $this
     */
    public function setStartTime($var)
    {
        GPBUtil::checkUint64($var);
        $this->start_time = $var;

        return $this;
    }

    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 end_time = 9;</code>
     * @return int|string
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * relative to the trace's start_time, in ns
     *
     * Generated from protobuf field <code>uint64 end_time = 9;</code>
     * @param int|string $var
     * @return $this
     */
    public function setEndTime($var)
    {
        GPBUtil::checkUint64($var);
        $this->end_time = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.Error error = 11;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.Error error = 11;</code>
     * @param \Protobuf\Trace\Error[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setError($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Protobuf\Trace\Error::class);
        $this->error = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.Node child = 12;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * Generated from protobuf field <code>repeated .Trace.Node child = 12;</code>
     * @param \Protobuf\Trace\Node[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setChild($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Protobuf\Trace\Node::class);
        $this->child = $arr;

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

// Adding a class alias for backwards compatibility with the previous class name.
# Removed class alias

