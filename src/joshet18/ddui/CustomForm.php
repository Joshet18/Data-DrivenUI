<?php

declare(strict_types=1);

namespace joshet18\ddui;

use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueMap;
use Closure;
use pocketmine\player\Player;

final class CustomForm implements UI
{

	/**
	 * @var array<int, array{value: DynamicValueMap, onUpdate: ?Closure(string, bool|string|float) : bool}>
	 */
	private array $elements = [];

	private bool $hasCloseButton = false;

	/** @var (Closure(int) : void)|null */
	private ?Closure $closeHandler = null;

	/** @var (Closure(bool|string|float) : bool)|null */
	private ?Closure $closeButtonHandler = null;

	public function __construct(
		private string $title
	) {}

	public static function create(string $title): self
	{
		return new self($title);
	}

	public function send(Player $player): void
	{
		DataDrivenUIHandler::sendDdui($player, $this);
	}

	public function withCloseButton(): self
	{
		$this->hasCloseButton = true;
		return $this;
	}

	public function label(string $text): self
	{
		$this->elements[] = ["value" => Elements::label($text), "onUpdate" => null];
		return $this;
	}

	public function divider(): self
	{
		$this->elements[] = ["value" => Elements::divider(), "onUpdate" => null];
		return $this;
	}

	public function spacer(): self
	{
		$this->elements[] = ["value" => Elements::spacer(), "onUpdate" => null];
		return $this;
	}

	/**
	 * @param Closure(string) : void $onChange
	 */
	public function textField(string $label, string $default, Closure $onChange, string $description = ""): self
	{
		$this->elements[] = [
			"value" => Elements::textField($label, $default, $description),
			"onUpdate" => function (string $property, bool|string|float $value) use ($onChange): bool {
				if ($property === "text" && is_string($value)) $onChange($value);
				return false;
			}
		];
		return $this;
	}

	/**
	 * @param Closure(bool) : void $onToggle
	 */
	public function toggle(string $label, bool $default, Closure $onToggle): self
	{
		$this->elements[] = [
			"value" => Elements::toggle($label, $default),
			"onUpdate" => function (string $property, bool|string|float $value) use ($onToggle): bool {
				if ($property === "toggled" && is_bool($value)) $onToggle($value);
				return false;
			}
		];
		return $this;
	}

	/**
	 * @param list<string> $options
	 * @param Closure(int) : void $onSelect
	 */
	public function dropdown(string $label, array $options, int $defaultIndex, Closure $onSelect): self
	{
		$this->elements[] = [
			"value" => Elements::dropdown($label, $options, $defaultIndex),
			"onUpdate" => function (string $property, bool|string|float $value) use ($onSelect): bool {
				if ($property === "value") $onSelect((int) $value);
				return false;
			}
		];
		return $this;
	}

	/**
	 * @param Closure(float) : void $onChange
	 */
	public function slider(string $label, float $default, float $min, float $max, float $step, Closure $onChange, string $description = ""): self
	{
		$this->elements[] = [
			"value" => Elements::slider($label, $default, $min, $max, $step, $description),
			"onUpdate" => function (string $property, bool|string|float $value) use ($onChange): bool {
				if ($property === "value") $onChange((float) $value);
				return false;
			}
		];
		return $this;
	}

	/**
	 * @param Closure() : void $onClick
	 * @param bool $closeOnClick").
	 */
	public function button(string $label, Closure $onClick, bool $closeOnClick = true): self
	{
		$this->elements[] = [
			"value" => Elements::button($label),
			"onUpdate" => function (string $property, bool|string|float $value) use ($onClick, $closeOnClick): bool {
				if ($property === "onClick") {
					$onClick();
					return $closeOnClick;
				}
				return false;
			}
		];
		return $this;
	}

	/**
	 * @param (Closure(bool|string|float) : bool)|null $onClick
	 */
	public function onCloseButtonClicked(?Closure $onClick): self
	{
		$this->closeButtonHandler = $onClick;
		return $this;
	}

	/**
	 * @param Closure(int) : void $handler (@see DduiManager::CLOSE_REASON_*).
	 */
	public function whenClosed(Closure $handler): self
	{
		$this->closeHandler = $handler;
		return $this;
	}

	public function getType(): string
	{
		return "minecraft:custom_form";
	}

	public function serialize(): DynamicValueMap
	{
		$layout = [];
		foreach ($this->elements as $i => $element) $layout[(string) $i] = $element["value"];
		$layout["length"] = Dv::long(count($this->elements));
		$top = [];
		if ($this->hasCloseButton) $top["closeButton"] = Elements::button("Close");
		$top["layout"] = Dv::map($layout);
		$top["title"] = Dv::str($this->title);
		return Dv::map($top);
	}

	public function handleUpdate(string $path, bool|string|float $value): bool
	{
		if (preg_match('/^layout\[(\d+)]\.(.+)$/', $path, $matches) === 1) {
			$index = (int) $matches[1];
			$property = $matches[2];
			$onUpdate = $this->elements[$index]["onUpdate"] ?? null;
			if ($onUpdate !== null) return $onUpdate($property, $value);
		} elseif ($path === "closeButton.onClick") {
			if ($this->closeButtonHandler !== null) return ($this->closeButtonHandler)($value);
			return true;
		}
		return false;
	}

	public function onClose(int $reason): void
	{
		if ($this->closeHandler !== null) ($this->closeHandler)($reason);
	}
}
