# CPlot

---

CPlot is a land and world management plugin for the Minecraft: Bedrock Edition server software [PocketMine-MP](https://github.com/pmmp/PocketMine-MP).

I, [ColinHDev](https://github.com/ColinHDev), started the development of CPlot way back in 2019 after using [MyPlot](https://github.com/jasonwynn10/MyPlot) on my server for a few years. Originally, CPlot was meant to be an expanded version of MyPlot that should support many more features. But after running into problems with the old codebase, I started working on my own thing from the start, although my previous codebase was heavily influenced by MyPlot's code style. Over the years, CPlot underwent many rewrites, so it is no longer comparable with its version from 2019.

While CPlot was meant to be closed-source to provide a good plot system for my server back then, I now decided to make it open-source so that other plugin developers, server owners and players have the possibility to benefit from CPlot.

While it was always the goal to provide with CPlot an alternative with a whole load of unique features to MyPlot, the biggest focus is to provide a stable, lag-free and smooth experience for both server owners and players.

## TODO

- improve async/sync mess in plot classes
- chunk locking
- store plot area in plots and remove calculation from AsyncTasks
- make entities that are allowed to cross plot borders modifiable OR remake that complete Task