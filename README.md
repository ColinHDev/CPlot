# CPlot

CPlot is a land and world management plugin for the Minecraft: Bedrock Edition server software [PocketMine-MP](https://github.com/pmmp/PocketMine-MP).

I, [ColinHDev](https://github.com/ColinHDev), started the development of CPlot way back in 2019 after using [MyPlot](https://github.com/jasonwynn10/MyPlot) on my server for a few years. Originally, CPlot was meant to be an expanded version of MyPlot that should support many more features. But after running into problems with the old codebase, I started working on my own thing from the start, although my previous codebase was heavily influenced by MyPlot's code style. Over the years, CPlot underwent many rewrites, so it is no longer comparable with its version from 2019.

While CPlot was meant to be closed-source to provide a good plot system for my server back then, I now decided to make it open-source so that other plugin developers, server owners and players have the possibility to benefit from CPlot.

While it was always the goal to provide with CPlot an alternative with a whole load of unique features to MyPlot, the biggest focus is to provide a stable, lag-free and smooth experience for both server owners and players.

## TODO

- [] Schematics <br/>
  While Schematics are a great addition to CPlot and an advantage over other plot plugins, they are far from finished. The way they are stored is very limited. Although I like the idea of having a single file, which can be easily shared across folders and other servers, it currently is not be an optimised way of storing. <br/>
  At the moment, each coordinate hash holds the numerical ID, meta value and string representation of the block, while air blocks are excluded. Although this is not bad, it is not good either. Eventually, when PM5 is released and schematics would need to be adopted to support NBT block states and the blocks from Minecraft: Bedrock Edition version 1.13+, the file size could drastically increase when storing every block state for each coordinate. <br/>
  We could do it the same way PM5 will probably do it, by just storing a list of all used block states and giving those IDs, while the coordinate hashes would only store the ID of their block state. We might be able to use PMMP's [PalettedBlockArrays](https://github.com/pmmp/ext-chunkutils2) for that. But there weren't any tests done regarding that solution, so this is purely speculating and could be complete rubbish.
- improve async/sync mess in plot classes
- chunk locking
- make entities that are allowed to cross plot borders modifiable OR remake that complete Task