php-windows-web-check
=====================

A simple script to tell if a dev/stage/prod server is mostly ready for our PHP use/talking to internal services at my employer.

This script uses some wsf stuff to check the windows registry for available ODBC drivers since WMI calls don't provide the needed information.

This script may change over time to make it pretty, and automate things, but probably not.

Most (sane) people don't have an iSeries in their infrastructure; I'm adding this script here just to show how I worked around `odbcinst` not being available on Windows.