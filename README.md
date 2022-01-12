## CPlot

### TODO
- remove `Plot::load...()` logic and load this data in `DataProvider::getPlot()`
    - It's nothing more than premature optimization. I thought it would be less load on the database if only the things that are needed were fetched from it. (For example not needing to get the plot rates when trying to walk on the plot.) But especially since plot owners are stored as plot players as well, most of the code now consists of this load methods and try catch statements to prevent crashes when something fails to load.
- remove all the unnecessary Flag and Setting classes
    - Although it would be nice to use `new PvpFlag(true)` to create a new flag, it's never used in the plugin itself. So if anyone else would need to create flags or settings on their own, they should just rely on the Flag- / SettingManager as CPlot does it.
- use libasynql for database storage
    - This would actually address three TODOs at once: First, I wanted to switch from executing database queries in the main thread to doing that asynchronous. Second, I wanted to implement MySQL as a provider type which could easily be done with libasynql. And last, if this plugin would ever become open source, we would need to use libasynql (or smth similar) to release it on poggit.
- chunk locking
- store plot area in plots and remove calculation from AsyncTasks
- more Economy providers
- make entities that are allowed to cross plot borders modifiable OR remake that complete Task