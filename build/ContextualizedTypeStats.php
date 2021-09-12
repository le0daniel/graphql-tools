<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>ContextualizedTypeStats</code>
 */
class ContextualizedTypeStats extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.StatsContext context = 1;</code>
     */
    protected $context = null;
    /**
     * Generated from protobuf field <code>map<string, .TypeStat> per_type_stat = 2;</code>
     */
    private $per_type_stat;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \StatsContext $context
     *     @type array|\Google\Protobuf\Internal\MapField $per_type_stat
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Report::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.StatsContext context = 1;</code>
     * @return \StatsContext|null
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
     * Generated from protobuf field <code>.StatsContext context = 1;</code>
     * @param \StatsContext $var
     * @return $this
     */
    public function setContext($var)
    {
        GPBUtil::checkMessage($var, \StatsContext::class);
        $this->context = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>map<string, .TypeStat> per_type_stat = 2;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getPerTypeStat()
    {
        return $this->per_type_stat;
    }

    /**
     * Generated from protobuf field <code>map<string, .TypeStat> per_type_stat = 2;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setPerTypeStat($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::MESSAGE, \TypeStat::class);
        $this->per_type_stat = $arr;

        return $this;
    }

}

