<?php

declare(strict_types=1);

namespace joshet18\ddui;

use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueMap;

interface UI{

    public function getType() : string;

    public function serialize() : DynamicValueMap;

    public function handleUpdate(string $path, bool|string|float $value) : bool;

    public function onClose(int $reason) : void;
}