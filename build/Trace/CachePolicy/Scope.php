<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: report.proto

# Modified Namespace Prefix
namespace Protobuf\Trace\CachePolicy;


use UnexpectedValueException;

/**
 * Protobuf type <code>Trace.CachePolicy.Scope</code>
 */
class Scope
{
    /**
     * Generated from protobuf enum <code>UNKNOWN = 0;</code>
     */
    const UNKNOWN = 0;
    /**
     * Generated from protobuf enum <code>PUBLIC = 1;</code>
     */
    const PBPUBLIC = 1;
    /**
     * Generated from protobuf enum <code>PRIVATE = 2;</code>
     */
    const PBPRIVATE = 2;

    private static $valueToName = [
        self::UNKNOWN => 'UNKNOWN',
        self::PBPUBLIC => 'PBPUBLIC',
        self::PBPRIVATE => 'PBPRIVATE',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
# Removed class alias

