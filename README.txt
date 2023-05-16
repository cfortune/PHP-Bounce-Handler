PHP Bounce Handler

INSTALL
-------
Download php.bouncehandler.vX.Y.zip (where X.Y match latest version)
Upload to a website, and open testdriver1.php in a web browser
for normal operation only bounce_driver_class.php and bounce_statuscodes.php are required



RELEASE HISTORY
---------------
v7.8 VP  August 10, 2015
- minor fixes to eliminate notices

v7.7 VP  April 10, 2015
- fixed incorrect original letter discovery in some cases

v7.6 VP  April 9, 2015
- added discovery of original letters from weird FBLs

v7.5 VP  April 6, 2015

- now it's stable
- fixed tons of errors - warnings, notices etc...
- some code optimizations
- get_the_facts() result now looks slightly like bouncehammer's one

v7.4 SB  June 19, 2014

- make auto-responder identification table driven
- make bounce_statuscodes.php (prev rfc1893_status_codes.php) generated from IANA list  
   php Make_statuscodes.php >bounce_statuscodes.php
- allow for rfc status codes with 2 digits in the 3rd paramater
- more supression for php notifications on undefined data
- better detection and field definition for FBL handling
- remove spaces in joined header lines
- remove invalid/redundant implode operations
- add two new sample emails 61,62
- add command line test tool (cmdlinetest.php)

v7.3 CF  July 4, 2013

- Replaced deprecated split() function.
- Added auto-responder identification filter.  
- Suppressed php Notice errors.


