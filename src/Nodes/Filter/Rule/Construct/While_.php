<?php

namespace CodingAvenue\Proof\Nodes\Filter\Rule\Construct;

use CodingAvenue\Proof\Nodes\Filter\Rule\Rule;
use CodingAvenue\Proof\Nodes\Filter\Rule\RuleInterface;

class While_ extends Rule implements RuleInterface
{
    const CLASS_ = '\PhpParser\Node\Stmt\While_';

    public function getRule(): callable
    {
        $class = self::CLASS_;

        return function($node) use ($class) {
            return $node instanceof $class;
        };
    }

    public function allowedOptionalFilter()
    {
        return array();
    }
}
