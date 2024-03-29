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

## Addons
- [CPlotClaimAddon](https://github.com/ColinHDev/CPlotClaimAddon): An addon for performing certain actions (changing the plot's biome, border and wall) once a plot is claimed.
- [CPlotRedstoneCircuitIntegration](https://github.com/ColinHDev/CPlotRedstoneCircuitIntegration): An addon for integrating the [RedstoneCircuit](https://github.com/tedo0627/RedstoneCircuit) plugin into plot worlds.

## For developers

### CPlot's events
CPlot provides a number of events that can be used to hook into the plugin's code.
Currently, the following events can be used:
- [ColinHDev\CPlot\event\PlayerEnteredPlotEvent](src/ColinHDev/CPlot/event/PlayerEnteredPlotEvent.php): **Always** called when a player *entered* a plot.
- [ColinHDev\CPlot\event\PlayerEnterPlotEvent](src/ColinHDev/CPlot/event/PlayerEnterPlotEvent.php): **Sometimes** called when a player *enters* a plot.
- [ColinHDev\CPlot\event\PlotKickFromPlotEvent](src/ColinHDev/CPlot/event/PlotKickFromPlotEvent.php): Called when a player is kicked from a plot by another player.
- [ColinHDev\CPlot\event\PlayerLeavePlotEvent](src/ColinHDev/CPlot/event/PlayerLeavePlotEvent.php): **Sometimes** called when a player *leaves* a plot.
- [ColinHDev\CPlot\event\PlayerLeftPlotEvent](src/ColinHDev/CPlot/event/PlayerLeftPlotEvent.php): **Always** called when a player *left* a plot.
- [ColinHDev\CPlot\event\PlotBiomeChangeAsyncEvent](src/ColinHDev/CPlot/event/PlotBiomeChangeAsyncEvent.php): Called when the biome of a plot is changed.
- [ColinHDev\CPlot\event\PlotBorderChangeAsyncEvent](src/ColinHDev/CPlot/event/PlotBorderChangeAsyncEvent.php): Called when the border of a plot is changed.
- [ColinHDev\CPlot\event\PlotClaimAsyncEvent](src/ColinHDev/CPlot/event/PlotClaimAsyncEvent.php): Called when a plot is claimed by a player.
- [ColinHDev\CPlot\event\PlotClearAsyncEvent](src/ColinHDev/CPlot/event/PlotClearAsyncEvent.php): Called when a plot is cleared.
- [ColinHDev\CPlot\event\PlotClearedAsyncEvent](src/ColinHDev/CPlot/event/PlotClearedAsyncEvent.php): Called when a plot was successfully cleared.
- [ColinHDev\CPlot\event\PlotMergeAsyncEvent](src/ColinHDev/CPlot/event/PlotMergeAsyncEvent.php): Called when two plots are merged.
- [ColinHDev\CPlot\event\PlotMergedAsyncEvent](src/ColinHDev/CPlot/event/PlotMergedAsyncEvent.php): Called when two plots were successfully merged into one single plot.
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