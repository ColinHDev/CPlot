<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use SOFe\AwaitGenerator\Await;

class MyPlotConversionListener implements Listener{

    public function onWorldLoad(WorldLoadEvent $event) : void {
		Await::f2c(
			static function() use($event) : \Generator {
				$world = $event->getWorld();
				$worldName = $world->getFolderName();
				$worldData = $world->getProvider()->getWorldData();
				$worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
				if (!($worldSettings instanceof NonWorldSettings)) {
					return;
				}
				if($worldData->getGenerator() !== "myplot"){
					return;
				}
				try{
					$worldOptions = \json_decode($worldData->getGeneratorOptions(), true, flags: \JSON_THROW_ON_ERROR);
				}catch(\JsonException $e){
					$worldOptions = [];
				}

				$worldSettings = new WorldSettings(
					WorldSettings::TYPE_MYPLOT,
					BiomeIds::PLAINS, // MyPlot always uses the plains biome
					"default",
					"default",
					"default",
					ParseUtils::parseIntegerFromArray($worldOptions, "RoadWidth") ?? 7,
					ParseUtils::parseIntegerFromArray($worldOptions, "PlotSize") ?? 32,
					ParseUtils::parseIntegerFromArray($worldOptions, "GroundHeight") ?? 64,
					-ParseUtils::parseIntegerFromArray($worldOptions, "RoadWidth") ?? -7, // MyPlot generation offset is the negative road width
					ParseUtils::parseMyPlotBlock($worldOptions, "RoadBlock") ?? VanillaBlocks::OAK_PLANKS(),
					ParseUtils::parseMyPlotBlock($worldOptions, "WallBlock") ?? VanillaBlocks::STONE_SLAB(),
					ParseUtils::parseMyPlotBlock($worldOptions, "PlotFloorBlock") ?? VanillaBlocks::GRASS(),
					ParseUtils::parseMyPlotBlock($worldOptions, "PlotFillBlock") ?? VanillaBlocks::DIRT(),
					ParseUtils::parseMyPlotBlock($worldOptions, "BottomBlock") ?? VanillaBlocks::BEDROCK(),
				);
				yield from DataProvider::getInstance()->addWorld($worldName, $worldSettings);
			}
		);
    }
}