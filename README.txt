PHP Bounce Handler

INSTALL
-------
Upload to a website, and open testdriver1.php in a web browser



RELEASE HISTORY
---------------
v7.5.0 WP  May 18, 2019
 - Switch to curl from fopen.
 - Add -k (keep flag) for optionally keeping the original csv files.

v7.4.3 SB  Dec 2014
 - add a softlink for rfc1893.error.codes.php -> bounce_statuscodes.php
 - fix a fatal error with format_status_code returning the wrong datatype
 - remove zip from distro (github will do that automagically)
 - more autoresponse catchers
 - handler broken MTAs better

v7.4.2 SB  Oct 24, 2014
 - slightly less agressive bounce detection; only calls last chance if we
    see specific from/return-path
 - added a couple more autoreponder subject line checks
 - allows it to find email addresses new TLDs that are more than 4 chararters
 - more supression for php notifications on undefined data

v7.4.1 SB  Oct 22, 2014
- fix autoresponder detection.  A lot of bounces were incorrectly being
   caught as autoresponders
- added vacation/autoresponder examples
- minor fix in command line test tool

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


