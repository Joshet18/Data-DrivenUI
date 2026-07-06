<?php

declare(strict_types=1);

namespace joshet18\ddui;

use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueMap;
use Closure;
use pocketmine\player\Player;

final class MessageBox implements UI
{

    private ?string $button1Label = null;
    private ?string $button1Tooltip = null;

    private ?string $button2Label = null;
    private ?string $button2Tooltip = null;

    private int $selection = 0;

    /** @var (Closure(int) : void)|null */
    private ?Closure $handler = null;

    public function __construct(
        private string $title,
        private string $body = "",
    ) {}

    public static function create(string $title, string $body): self
    {
        return new self($title, $body);
    }

    public function send(Player $player): void
	{
		DataDrivenUIHandler::sendDdui($player, $this);
	}

    public function button1(string $label, ?string $tooltip = null): self
    {
        $this->button1Label = $label;
        $this->button1Tooltip = $tooltip;
        return $this;
    }

    public function button2(string $label, ?string $tooltip = null): self
    {
        $this->button2Label = $label;
        $this->button2Tooltip = $tooltip;
        return $this;
    }

    /**
     * @param Closure(int) : void $handler
     */
    public function whenClosed(Closure $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function getType(): string
    {
        return "minecraft:message_box";
    }

    public function serialize(): DynamicValueMap
    {
        $top = [];
        $top["title"] = Dv::str($this->title);
        $top["body"] = Dv::str($this->body);

        if ($this->button1Label !== null) {
            $top["button1"] = self::buildButton($this->button1Label, $this->button1Tooltip);
        }

        if ($this->button2Label !== null) {
            $top["button2"] = self::buildButton($this->button2Label, $this->button2Tooltip);
        }

        return Dv::map($top);
    }
    private static function buildButton(string $label, ?string $tooltip): DynamicValueMap
    {
        $data = [
            "button_visible" => Dv::bool(true),
            "label" => Dv::str($label),
            "onClick" => Dv::long(0),
            "visible" => Dv::bool(true),
        ];

        if ($tooltip !== null) {
            $data["tooltip"] = Dv::str($tooltip);
            $data["tooltip_visible"] = Dv::bool(true);
        }

        return Dv::map($data);
    }

    public function handleUpdate(string $path, bool|string|float $value): bool
    {
        switch ($path) {
            case "button1.onClick":
                $this->selection = 1;
                return true;
            case "button2.onClick":
                $this->selection = 2;
                return true;
        }
        return false;
    }

    public function onClose(int $reason): void
    {
        if ($this->handler !== null) ($this->handler)($this->selection);
    }
}
