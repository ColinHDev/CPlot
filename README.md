# CPlot

CPlot is a land and world management plugin for the Minecraft: Bedrock Edition server software [PocketMine-MP](https://github.com/pmmp/PocketMine-MP).

I, [ColinHDev](https://github.com/ColinHDev), started the development of CPlot way back in 2019 after using [MyPlot](https://github.com/jasonwynn10/MyPlot) on my server for a few years. Originally, CPlot was meant to be an expanded version of MyPlot that should support many more features. But after running into problems with the old codebase, I started working on my own thing from the start, although my previous codebase was heavily influenced by MyPlot's code style. Over the years, CPlot underwent many rewrites, so it is no longer comparable with its version from 2019.

While CPlot was meant to be closed-source to provide a good plot system for my server back then, I now decided to make it open-source so that other plugin developers, server owners and players have the possibility to benefit from CPlot.

While it was always the goal to provide with CPlot an alternative with a whole load of unique features to MyPlot, the biggest focus is to provide a stable, lag-free and smooth experience for both server owners and players.

## Features

### Economy Support
By default, CPlot hast built-in support for the most commonly used economy plugins within the PocketMine-MP plugin ecosystem. <br/>
Currently, this includes the following plugins: [BedrockEconomy](https://github.com/cooldogedev/BedrockEconomy), [Capital](https://github.com/SOF3/Capital) <br/>
If you use one of these plugins and want to charge users for certain actions regarding plots, you can head over to the `economy` configuration in the plugin's `config.yml` file.

## For developers

### CPlot's events
CPlot provides a number of events that can be used to hook into the plugin's code.
Currently, the following events can be used:
- [ColinHDev\CPlot\event\PlotBiomeChangeAsyncEvent](src/ColinHDev/CPlot/event/PlotBiomeChangeAsyncEvent.php): Called when the biome of a plot is changed.
- [ColinHDev\CPlot\event\PlotBorderChangeAsyncEvent](src/ColinHDev/CPlot/event/PlotBorderChangeAsyncEvent.php): Called when the border of a plot is changed.
- [ColinHDev\CPlot\event\PlotClaimAsyncEvent](src/ColinHDev/CPlot/event/PlotClaimAsyncEvent.php): Called when a plot is claimed by a player.
- [ColinHDev\CPlot\event\PlotClearAsyncEvent](src/ColinHDev/CPlot/event/PlotClearAsyncEvent.php): Called when a plot is cleared.
- [ColinHDev\CPlot\event\PlotMergeAsyncEvent](src/ColinHDev/CPlot/event/PlotMergeAsyncEvent.php): Called when two plots are merged by a player.
- [ColinHDev\CPlot\event\PlotPlayerAddAsyncEvent](src/ColinHDev/CPlot/event/PlotPlayerAddAsyncEvent.php): Called when a plot player (e.g. helper) is added to a plot by a player.
- [ColinHDev\CPlot\event\PlotPlayerRemoveAsyncEvent](src/ColinHDev/CPlot/event/PlotPlayerRemoveAsyncEvent.php): Called when a plot player (e.g. helper) is removed from a plot by a player.
- [ColinHDev\CPlot\event\PlotResetAsyncEvent](src/ColinHDev/CPlot/event/PlotResetAsyncEvent.php): Called when a plot is reset.
- [ColinHDev\CPlot\event\PlotWallChangeAsyncEvent](src/ColinHDev/CPlot/event/PlotWallChangeAsyncEvent.php): Called when the wall of a plot is changed.
- [ColinHDev\CPlot\event\PlotWorldGenerateAsyncEvent](src/ColinHDev/CPlot/event/PlotWorldGenerateAsyncEvent.php): Called when a new plot world is generated.

But, be aware that every event with the `AsyncEvent` suffix is created with the help of the [libAsyncEvent](https://github.com/ColinHDev/libAsyncEvent) virion, which allows the creation of asynchronous event execution. To understand how those events need to be handled, look at [libAsyncEvent's](https://github.com/ColinHDev/libAsyncEvent) documentation.

### Support for third-party economy plugins
The requirement for an economy plugin to be supported by default by CPlot is basically only to be open-source, be commonly used and have a relatively stable API. <br/>
But maybe your plugin is not well-known or closed-source and only for your own server, which would make it impossible for CPlot to support your plugin by default. Nonetheless, you can still make your economy plugin compatible with CPlot by using its internal API. <br/>
The `EconomyManager` class (namespace: `ColinHDev\CPlot\provider`) allows you to set your own economy provider class. Just create a class within your plugin that extends CPlot's `EconomyProvider` class (namespace: `ColinHDev\CPlot\provider`). Then, simply connect your class's `getCurrency()`, `removeMoney()`, etc. methods to your economy plugin's API. If you did this, just make sure your plugin loads after CPlot and during its enabling, set CPlot's economy provider to your provider class by calling `EconomyManager::getInstance()->setProvider(new YourEconomyProviderClass());` in your plugins `onEnable()` method.

## TODO

### Schematics
While Schematics are a great addition to CPlot and an advantage over other plot plugins, they are far from finished. The way they are stored is very limited. Although I like the idea of having a single file, which can be easily shared across folders and other servers, it currently is not be an optimised way of storing.

At the moment, each coordinate hash holds the numerical ID, meta value and string representation of the block, while air blocks are excluded. Although this is not bad, it is not good either. Eventually, when PM5 is released and schematics would need to be adopted to support NBT block states and the blocks from Minecraft: Bedrock Edition version 1.13+, the file size could drastically increase when storing every block state for each coordinate.

We could do it the same way PM5 will probably do it, by just storing a list of all used block states and giving those IDs, while the coordinate hashes would only store the ID of their block state. We might be able to use PMMP's [PalettedBlockArrays](https://github.com/pmmp/ext-chunkutils2) for that. But there weren't any tests done regarding that solution, so this is purely speculating and could be complete rubbish.

- improve async/sync mess in plot classes
- chunk locking
- make entities that are allowed to cross plot borders modifiable OR remake that complete Task