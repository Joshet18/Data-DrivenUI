<?php

declare(strict_types=1);

namespace joshet18\ddui;

use pocketmine\network\mcpe\protocol\types\cereal\DynamicValue;
use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueBool;
use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueLong;
use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueMap;
use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueString;
use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueDouble;

final class Dv{

    private function __construct(){}

    /**
     * @param array<string, DynamicValue|null> $entries
     */
    public static function map(array $entries) : DynamicValueMap{
        return new DynamicValueMap($entries);
    }

    public static function bool(bool $v) : DynamicValueBool{
        return new DynamicValueBool($v);
    }

    public static function str(string $v) : DynamicValueString{
        return new DynamicValueString($v);
    }

    public static function long(int $v) : DynamicValueLong{
        return new DynamicValueLong($v);
    }

    public static function double(float $v) : DynamicValueDouble{
        return new DynamicValueDouble($v);
    }
}