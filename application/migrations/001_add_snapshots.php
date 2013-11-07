<?php

//create a table for snapshots:
//id | userId | url | date | snapshot
//use date to compare for cache time. Basically that's the date it was rendered, and cachetime is that date + cache time, compare to current date!