<?php

namespace joshet18\ddui;

use joshet18\ddui\operation\DataStoreChange;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ClientboundDataDrivenUICloseScreenPacket;
use pocketmine\network\mcpe\protocol\ClientboundDataStorePacket;
use pocketmine\network\mcpe\protocol\ServerboundDataDrivenScreenClosedPacket;
use pocketmine\network\mcpe\protocol\ServerboundDataStorePacket;
use pocketmine\network\mcpe\protocol\types\ddui\update\BoolDataStoreUpdateValue;
use pocketmine\network\mcpe\protocol\types\ddui\update\DoubleDataStoreUpdateValue;
use pocketmine\network\mcpe\protocol\types\ddui\update\StringDataStoreUpdateValue;
use pocketmine\player\Player;

class DataDrivenUIEventHandler implements Listener
{



    /**
     * @handleCancelled
     */
    public function onDecode(DataPacketDecodeEvent $event): void
    {
        if ($event->getPacketId() === ServerboundDataDrivenScreenClosedPacket::NETWORK_ID or $event->getPacketId() === ServerboundDataStorePacket::NETWORK_ID) $event->uncancel();
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        unset(DataDrivenUIHandler::$active[$event->getPlayer()->getName()]);
    }

    public function onReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if ($player === null) return;
        if ($packet instanceof ServerboundDataDrivenScreenClosedPacket) $this->handleClosed($player, $packet);
        elseif ($packet instanceof ServerboundDataStorePacket) $this->handleUpdate($player, $packet);
    }

    private function handleClosed(Player $player, ServerboundDataDrivenScreenClosedPacket $packet): void
    {
        $name = $player->getName();
        foreach (DataDrivenUIHandler::$active[$name] ?? [] as $instanceId => $entry) {
            if ($entry["formId"] === $packet->getFormId()) {
                unset(DataDrivenUIHandler::$active[$name][$instanceId]);
                $entry["ui"]->onClose($this->mapCloseReason($packet->getCloseReason()));
                $player->getNetworkSession()->sendDataPacket(
                    ClientboundDataDrivenUICloseScreenPacket::create($entry["formId"])
                );
                $this->cleanupProperty($player, $entry);
                return;
            }
        }
    }

    private function handleUpdate(Player $player, ServerboundDataStorePacket $packet): void
    {
        $update = $packet->getUpdate();
        $name = $player->getName();

        $sep = strrpos($update->getProperty(), "_data_");
        if ($sep === false) {
            return;
        }
        $instanceId = (int) substr($update->getProperty(), $sep + 6);

        $entry = DataDrivenUIHandler::$active[$name][$instanceId] ?? null;
        if ($entry === null) {
            return;
        }

        $data = $update->getData();
        $value = match (true) {
            $data instanceof BoolDataStoreUpdateValue => $data->getValue(),
            $data instanceof StringDataStoreUpdateValue => $data->getValue(),
            $data instanceof DoubleDataStoreUpdateValue => $data->getValue(),
            default => null,
        };
        if ($value === null) {
            return;
        }

        $shouldClose = $entry["ui"]->handleUpdate($update->getPath(), $value);
        if ($shouldClose) {
            unset(DataDrivenUIHandler::$active[$name][$instanceId]);
            $entry["ui"]->onClose(DataDrivenUIHandler::CLOSE_REASON_PROGRAMMATIC);
            $player->getNetworkSession()->sendDataPacket(
                ClientboundDataDrivenUICloseScreenPacket::create($entry["formId"])
            );
            $this->cleanupProperty($player, $entry);
        }
    }

    /**
     * @param array{ui: UI, formId: int, property: string, updateCount: int} $entry
     */
    private function cleanupProperty(Player $player, array $entry): void
    {
        $player->getNetworkSession()->sendDataPacket(
            ClientboundDataStorePacket::create([
                new DataStoreChange(
                    "minecraft",
                    $entry["property"],
                    $entry["updateCount"] + 1,
                    null
                )
            ])
        );
    }

    private function mapCloseReason(string $reason): int
    {
        return match (true) {
            str_contains($reason, "rogrammaticCloseAll"), str_contains($reason, "ProgrammaticCloseAll") => DataDrivenUIHandler::CLOSE_REASON_PROGRAMMATIC_ALL,
            str_contains($reason, "rogrammaticclose"), str_contains($reason, "ProgrammaticClose") => DataDrivenUIHandler::CLOSE_REASON_PROGRAMMATIC,
            str_contains($reason, "usy") => DataDrivenUIHandler::CLOSE_REASON_BUSY,
            str_contains($reason, "nvalid") => DataDrivenUIHandler::CLOSE_REASON_INVALID,
            default => DataDrivenUIHandler::CLOSE_REASON_CLIENT_CLOSED,
        };
    }
}
