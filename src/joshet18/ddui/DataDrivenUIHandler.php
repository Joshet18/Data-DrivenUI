<?php

declare(strict_types=1);

namespace joshet18\ddui;

use InvalidArgumentException;
use joshet18\ddui\operation\DataStoreChange;
use LogicException;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\ClientboundDataDrivenUIShowScreenPacket;
use pocketmine\network\mcpe\protocol\ClientboundDataStorePacket;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

final class DataDrivenUIHandler implements Listener
{
	private static ?Plugin $registrant = null;
	public const CLOSE_REASON_PROGRAMMATIC = 0;
	public const CLOSE_REASON_PROGRAMMATIC_ALL = 1;
	public const CLOSE_REASON_CLIENT_CLOSED = 2;
	public const CLOSE_REASON_BUSY = 3;
	public const CLOSE_REASON_INVALID = 4;

	/**
	 * @var array<string, array<int, array{ui: UI, formId: int, property: string, updateCount: int}>>
	 */
	public static array $active = [];

	private static int $nextId = 1;

	public static function register(Plugin $plugin) : void{
		!self::isRegistered() || throw new InvalidArgumentException("{$plugin->getName()} attempted to register " . self::class . " twice.");
		self::$registrant = $plugin;
		Server::getInstance()->getPluginManager()->registerEvents(new DataDrivenUIEventHandler(), $plugin);
	}

	public static function isRegistered() : bool{
		return self::$registrant instanceof Plugin;
	}

	public static function getRegistrant() : Plugin{
		return self::$registrant ?? throw new LogicException("Cannot obtain registrant before registration");
	}

	public static function sendDdui(Player $player, UI $ui): void
	{ 
		if(!self::isRegistered()) throw new LogicException("Cannot send ui before registration");
		$formId = self::$nextId++;
		$dataInstanceId = self::$nextId++;

		$property = self::deriveProperty($ui->getType(), $dataInstanceId);

		self::$active[$player->getName()][$dataInstanceId] = [
			"ui" => $ui,
			"formId" => $formId,
			"property" => $property,
			"updateCount" => 1,
		];

		$player->getNetworkSession()->sendDataPacket(
			ClientboundDataStorePacket::create([
				new DataStoreChange(
					"minecraft",
					$property,
					1,
					$ui->serialize()
				)
			])
		);

		$player->getNetworkSession()->sendDataPacket(
			ClientboundDataDrivenUIShowScreenPacket::create($ui->getType(), $formId, $dataInstanceId)
		);
	}

	private static function deriveProperty(string $screenId, int $dataInstanceId): string
	{
		$base = str_starts_with($screenId, "minecraft:") ? substr($screenId, strlen("minecraft:")) : $screenId;
		$base = str_replace(":", "_", $base);
		return $base . "_data_" . $dataInstanceId;
	}
}
