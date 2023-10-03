<?php

namespace viskaraze\ArmorEffect;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Living;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\{
    ArmorInventory,
    CallbackInventoryListener,
    Inventory
};
use pocketmine\item\{
    Armor,
    Item,
    StringToItemParser,
    VanillaItems
};
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

class ArmorEffect extends PluginBase implements Listener {
    private const EFFECT_MAX_DURATION = 2147483647;
    private static Config $config;

    public static function getData(): Config {
        return self::$config;
    }

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource('config.yml');
        }
        self::$config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        foreach ($player->getArmorInventory()->getContents() as $targetItem) {
            if ($targetItem instanceof Armor) {
                $slot = $targetItem->getArmorSlot();
                $sourceItem = $player->getArmorInventory()->getItem($slot);

                $this->addEffects($player, $sourceItem, $targetItem);
            } else {
                if ($targetItem->getTypeId() == VanillaItems::AIR()->getTypeId()) {
                    $this->addEffects($player, VanillaItems::AIR(), $targetItem);
                }
            }
        }

        $player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem): void {
            if ($inventory instanceof ArmorInventory) {
                $targetItem = $inventory->getItem($slot);
                $this->addEffects($inventory->getHolder(), $oldItem, $targetItem);
            }
        }, null));
    }

    private function addEffects(Living $player, Item $sourceItem, Item $targetItem): void {
        $configs = self::$config->getAll();
        $ids = array_keys($configs);

        $nameItem = StringToItemParser::getInstance()->lookupAliases($sourceItem)[0];
        if (in_array($nameItem, $ids)) {
            $array = $configs[$nameItem];
            $effects = $array["effect"];

            foreach ($effects as $effectId => $arrayEffect) {
                $player->getEffects()->remove(EffectIdMap::getInstance()->fromId($effectId));
            }
        }

        $nameOfTargetItem = StringToItemParser::getInstance()->lookupAliases($targetItem)[0];
        if (in_array($nameOfTargetItem, $ids)) {
            $array = $configs[$nameOfTargetItem];
            $effects = $array["effect"];

            foreach ($effects as $effectId => $arrayEffect) {
                $effect = new EffectInstance(
                    EffectIdMap::getInstance()->fromId($effectId),
                    self::EFFECT_MAX_DURATION,
                    (int)$arrayEffect["amplifier"],
                    (bool)$arrayEffect["visible"]
                );
                $player->getEffects()->add($effect);
            }
        }
    }
}

