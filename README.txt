PHP Bounce Handler

INSTALL
-------
Upload to a website, and open testdriver1.php in a web browser
for normal operation only
 bounce_driver_class.php and bounce_statuscodes.php are required



RELEASE HISTORY
---------------
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


