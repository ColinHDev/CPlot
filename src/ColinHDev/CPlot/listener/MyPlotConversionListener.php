<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\generator\MyPlotGenerator;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use SOFe\AwaitGenerator\Await;
use function is_array;
use function json_decode;

class MyPlotConversionListener implements Listener{

    public function onWorldLoad(WorldLoadEvent $event) : void {
        $world = $event->getWorld();
        if ($world->getProvider()->getWorldData()->getGenerator() !== MyPlotGenerator::GENERATOR_NAME) {
            return;
        }
		Await::f2c(
			static function() use($world) : Generator {
				$worldName = $world->getFolderName();
				$worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
				if ($worldSettings !== false) {
					return;
				}
                $worldOptions = json_decode($world->getProvider()->getWorldData()->getGeneratorOptions(), true);
                if (!is_array($worldOptions)) {
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
					-1 * (ParseUtils::parseIntegerFromArray($worldOptions, "RoadWidth") ?? 7), // MyPlot generation offset is the negative road width
					ParseUtils::parseMyPlotBlock($worldOptions, "RoadBlock") ?? VanillaBlocks::OAK_PLANKS(),
					ParseUtils::parseMyPlotBlock($worldOptions, "WallBlock") ?? VanillaBlocks::STONE_SLAB(),
					ParseUtils::parseMyPlotBlock($worldOptions, "PlotFloorBlock") ?? VanillaBlocks::GRASS(),
					ParseUtils::parseMyPlotBlock($worldOptions, "PlotFillBlock") ?? VanillaBlocks::DIRT(),
					ParseUtils::parseMyPlotBlock($worldOptions, "BottomBlock") ?? VanillaBlocks::BEDROCK(),
				);
				yield from DataProvider::getInstance()->addWorld($worldName, $worldSettings);
				yield from DataProvider::getInstance()->importMyPlotData($worldName);
			}
		);
    }
}