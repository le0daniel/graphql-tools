<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

enum GraphQlTypes
{
    case OBJECT_TYPE;
    case UNION;
    case INPUT_TYPE;
    case INTERFACE;
    case SCALAR;
}