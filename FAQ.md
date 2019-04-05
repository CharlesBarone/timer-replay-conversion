# Timer Replay Conversion - FAQ

## What timers does this support?

Currently supported timers include: bTimes 1.8.3, bTimes 2.0, shavit's bhoptimer, and Ofir's Timer.

## What timers do you plan to add?

I currently plan to also support the following timers: Influx, ckSurf, Dr. API's updated Zipcore Timer, Zipcore's SM-OpenTimer, and Nairda's Timer(Older bots from when he used ofir's bots are already supported!).

## Do I need to enter my database settings?

If you plan to convert to or from either version of bTimes, yes you will need to enter your database credentials do the script can convert the PlayerID stored in the bot into a SteamID.

## I am getting php errors about Type g. What should I do?

You need to update to php version 7.2.0 or greater.

## Are there any php dependencies?

Yes, the [GMP module](http://php.net/manual/en/book.gmp.php) must be installed for [SteamID.php](https://github.com/xPaw/SteamID.php) to work properly.
