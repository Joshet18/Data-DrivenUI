<?php

declare(strict_types=1);

namespace joshet18\ddui;

use pocketmine\network\mcpe\protocol\types\cereal\DynamicValueMap;

/**
 * Dragonfly reference (https://github.com/df-mc/dragonfly/pull/1274),
 */
final class Elements{

	private function __construct(){}

	public static function label(string $text) : DynamicValueMap{
		return Dv::map([
			"label_visible" => Dv::bool(true),
			"text" => Dv::str($text),
			"visible" => Dv::bool(true),
		]);
	}

	public static function divider() : DynamicValueMap{
		return Dv::map([
			"divider_visible" => Dv::bool(true),
			"visible" => Dv::bool(true),
		]);
	}

	public static function spacer() : DynamicValueMap{
		return Dv::map([
			"spacer_visible" => Dv::bool(true),
			"visible" => Dv::bool(true),
		]);
	}

	public static function textField(string $label, string $value, string $description = "") : DynamicValueMap{
		return Dv::map([
			"visible" => Dv::bool(true),
			"description" => Dv::str($description),
			"label" => Dv::str($label),
			"text" => Dv::str($value),
			"textfield_visible" => Dv::bool(true),
		]);
	}

	public static function toggle(string $label, bool $value) : DynamicValueMap{
		return Dv::map([
			"label" => Dv::str($label),
			"toggle_visible" => Dv::bool(true),
			"toggled" => Dv::bool($value),
			"visible" => Dv::bool(true),
		]);
	}

	/**
	 * @param list<string> $options
	 */
	public static function dropdown(string $label, array $options, int $defaultIndex = 0) : DynamicValueMap{
		$items = [];
		foreach($options as $i => $optionLabel){
			$items[(string) $i] = Dv::map([
				"label" => Dv::str($optionLabel),
				"value" => Dv::long($i),
			]);
		}
		$items["length"] = Dv::long(count($options));

		return Dv::map([
			"visible" => Dv::bool(true),
			"dropdown_visible" => Dv::bool(true),
			"items" => Dv::map($items),
			"label" => Dv::str($label),
			"value" => Dv::long($defaultIndex),
		]);
	}

	public static function slider(string $label, float $value, float $min, float $max, float $step = 1.0, string $description = "") : DynamicValueMap{
		return Dv::map([
			"visible" => Dv::bool(true),
			"description" => Dv::str($description),
			"slider_visible" => Dv::bool(true),
			"label" => Dv::str($label),
			"maxValue" => Dv::double($max),
			"minValue" => Dv::double($min),
			"step" => Dv::double($step),
			"value" => Dv::double($value),
		]);
	}

	public static function button(string $label) : DynamicValueMap{
		return Dv::map([
			"button_visible" => Dv::bool(true),
			"label" => Dv::str($label),
			"onClick" => Dv::long(0),
			"visible" => Dv::bool(true),
		]);
	}
}