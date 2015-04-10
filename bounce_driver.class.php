<?php
/* BOUNCE HANDLER Class, Version 7.5
 * Description: "chops up the bounce into associative arrays"
 *     ~ https://github.com/mcvogel/PHP-Bounce-Handler
 *     ~ http://www.anti-spam-man.com/php_bouncehandler/v7.3/	- site is dead
 *     ~ https://github.com/cfortune/PHP-Bounce-Handler/		- original repo. looks discontinued
 *     ~ http://www.phpclasses.org/browse/file/11665.html
 */

/* Debugging / Contributers:
    * MC Vogel
    * "Kanon"
    * Jamie McClelland http://mayfirst.org
    * Michael Cooper
    * Thomas Seifert
    * Tim Petrowsky http://neuecouch.de
    * Willy T. Koch http://apeland.no
    * ganeshaspeaks.com - FBL development
    * Richard Catto - FBL development
    * Scott Brynen - FBL development  http://visioncritical.com
*/


/*
 The BSD License
 Copyright (c) 2006-forever, Chris Fortune http://cfortune.kics.bc.ca
 All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
    * Neither the name of the BounceHandler nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class BounceHandler {

    /**** VARS ****************************************************************/
    public $head_hash = array();
    public $fbl_hash = array();
    public $body_hash = array(); // not necessary
    private $first_body_hash = [];
    private $bouncelist = array(); // from bounce_responses
    private $autorespondlist = array(); // from bounce_responses
    private $bouncesubj = array(); // from bounce_responses

    public $looks_like_a_bounce = false;
    public $looks_like_an_FBL = false;
    public $looks_like_an_autoresponse = false;
    public $autoresponse = '';
    public $is_hotmail_fbl = false;

    // these are for feedback reports, so you can extract uids from the emails
    // eg X-my-custom-header: userId12345
    // eg <img src="http://mysite.com/track.php?u=userId12345">
    public $web_beacon_preg_1 = "";
    public $web_beacon_preg_2 = "";
    public $x_header_search_1 = "";
    public $x_header_search_2 = "";

    // accessors
    public $type = "";
    public $web_beacon_1 = "";
    public $web_beacon_2 = "";
    public $feedback_type = "";
    public $x_header_beacon_1 = "";
    public $x_header_beacon_2 = "";

    // these accessors are useful only for FBL's
    // or if the output array has only one index
    public $action = "";
    public $status = "";
    public $subject = "";
    public $recipient = "";
    public $original_letter = '';
    public $original_letter_header = [];
    public $original_letter_body = '';

    // the raw data set, a multiArray
    public $output = array();


    /**** INSTANTIATION *******************************************************/
    public function __construct() {
        require('bounce_responses.php');
        $this->bouncelist = $bouncelist;
        $this->autorespondlist = $autorespondlist;
        $this->bouncesubj = $bouncesubj;
    }


    /**** METHODS *************************************************************/
    // this is the most commonly used public method
    // quick and dirty
    // useage: $multiArray = $this->get_the_facts($strEmail);
    public function parse_email($eml) {
        return $this->get_the_facts($eml);
    }

    public function get_the_facts($eml) {
        // fluff up the email
        $bounce = $this->init_bouncehandler($eml);
        list($head, $body) = $this->splitHeadAndBody($bounce);

        $this->head_hash = $this->parse_head($head);

        // parse the email into data structures
        $boundary = isset($this->head_hash['Content-type']['boundary']) ? $this->head_hash['Content-type']['boundary'] : '';
        $mime_sections = $this->parse_body_into_mime_sections($body, $boundary);

        // recover the original letter
        if (!empty($mime_sections['returned_message_body_part'])) {
            list($ct, $this->original_letter) = $this->splitHeadAndBody($mime_sections['returned_message_body_part']);
        } elseif (strpos($body, '------ This is a copy of your message, including all the headers. ------') !== false) {
            list($_, $this->original_letter) = preg_split("/\r\n\r\n------ This is a copy of your message, including all the headers. ------\r\n\r\n/", $body, 2);
        } else {
            $letters = preg_split("/\nReturn-path:[^\n]*\n/i", $bounce, -1, PREG_SPLIT_NO_EMPTY);
            if (!empty($letters[2])) {
                $this->original_letter = $letters[2];
            }
        }
        if (!empty($this->original_letter)) {
            list($original_head, $this->original_letter_body) = $this->splitHeadAndBody($this->original_letter);
            $this->original_letter_header = $this->parse_head($original_head);
        }

        $this->body_hash = explode("\r\n", $body);
        $this->first_body_hash = isset($mime_sections['first_body_part']) ? $this->parse_head($mime_sections['first_body_part']) : [];

        $this->looks_like_a_bounce = $this->is_a_bounce();
        $this->looks_like_an_FBL = $this->is_an_ARF();
        $this->looks_like_an_autoresponse = !$this->looks_like_a_bounce && !$this->looks_like_an_FBL && $this->is_an_autoresponse();

        /*** now we try all our weird text parsing methods (E-mail is weird!) ******************************/

        // is it a Feedback Loop, in Abuse Feedback Reporting Format (ARF)?
        // http://en.wikipedia.org/wiki/Abuse_Reporting_Format#Abuse_Feedback_Reporting_Format_.28ARF.29
        if ($this->looks_like_an_FBL) {
            $this->output[0]['action'] = 'failed';
            $this->output[0]['deliverystatus'] = "5.7.1";
            $this->subject = trim(str_ireplace("Fw:", "", $this->head_hash['Subject']));

            // Sometimes one mail server uses senderscore.net for fbl
            // So original message may be hidden in attachment
            if (!empty($this->original_letter) && !empty($this->original_letter_header)) {
                $ol_boundary = isset($this->original_letter_header['Content-type']['boundary']) ? $this->original_letter_header['Content-type']['boundary'] : '';
                if (!empty($ol_boundary)) {
                    $ol_mime_sections = $this->parse_body_into_mime_sections($this->original_letter_body, $ol_boundary);
                    $ol_mpbp = isset($ol_mime_sections['machine_parsable_body_part']) ? $this->parse_head($ol_mime_sections['machine_parsable_body_part']) : [];
                    if (!empty($ol_mpbp['Content-type']['type']) && $ol_mpbp['Content-type']['type'] == 'message/feedback-report' && !empty($ol_mime_sections['returned_message_body_part'])) {
                        list($ct, $this->original_letter) = $this->splitHeadAndBody($ol_mime_sections['returned_message_body_part']);
                        list($original_head, $this->original_letter_body) = $this->splitHeadAndBody($this->original_letter);
                        $this->original_letter_header = $this->parse_head($original_head);
                    }
                }
            }

            if ($this->is_hotmail_fbl === true) {
                // fill in the fbl_hash with sensible values
                $this->fbl_hash['Source-ip'] = '';
                $this->fbl_hash['Original-mail-from'] = '';
                $this->fbl_hash['Original-rcpt-to'] = '';
                $this->fbl_hash['Feedback-type'] = 'abuse';
                $this->fbl_hash['Content-disposition'] = 'inline';
                $this->fbl_hash['Content-type'] = 'message/feedback-report';
                $this->fbl_hash['User-agent'] = 'Hotmail FBL';

                if (isset($this->first_body_hash['Date'])) 	$this->fbl_hash['Received-date'] = $this->first_body_hash['Date'];
                if (isset($this->head_hash['Subject']) && preg_match('/complaint about message from ([0-9.]+)/', $this->head_hash['Subject'], $matches)) $this->fbl_hash['Source-ip'] = $matches[1];
                if (!empty($this->recipient)) $this->fbl_hash['Original-rcpt-to'] = $this->recipient;
                if (isset($this->first_body_hash['X-sid-pra'])) $this->fbl_hash['Original-mail-from'] = $this->first_body_hash['X-sid-pra'];
            } else {
                $this->fbl_hash = $this->standard_parser($mime_sections['machine_parsable_body_part']);
                $returnedhash = $this->standard_parser($mime_sections['returned_message_body_part']);

                if (!empty($returnedhash['Return-path'])) {
                    $this->fbl_hash['Original-mail-from'] = $returnedhash['Return-path'];
                } elseif (empty($this->fbl_hash['Original-mail-from']) && !empty($returnedhash['From'])) {
                    $this->fbl_hash['Original-mail-from'] = $returnedhash['From'];
                }

                if (empty($this->fbl_hash['Original-rcpt-to']) && !empty($this->fbl_hash['Removal-recipient'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $this->fbl_hash['Removal-recipient'];
                } elseif (isset($returnedhash['To'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $returnedhash['To'];
                } else {
                    $this->fbl_hash['Original-rcpt-to'] = '';
                }

                if (!isset($this->fbl_hash['Source-ip'])) {
                    if (!empty($returnedhash['X-originating-ip'])) {
                        $this->fbl_hash['Source-ip'] = $this->strip_angle_brackets($returnedhash['X-originating-ip']);
                    } else {
                        $this->fbl_hash['Source-ip'] = '';
                    }
                }
            }

            // warning, some servers will remove the name of the original intended recipient from the FBL report,
            // replacing it with redacted@rcpt-hostname.com, making it utterly useless, of course (unless you used a web-beacon).
            // here we try our best to give you the actual intended recipient, if possible.
            if (preg_match('/Undisclosed|redacted/i', $this->fbl_hash['Original-rcpt-to']) && isset($this->fbl_hash['Removal-recipient'])) {
                $this->fbl_hash['Original-rcpt-to'] = @$this->fbl_hash['Removal-recipient'];
            }

            if (empty($this->fbl_hash['Received-date']) && !empty($this->fbl_hash['Arrival-date'])) {
                $this->fbl_hash['Received-date'] = $this->fbl_hash['Arrival-date'];
            }
            $this->fbl_hash['Original-mail-from'] = $this->strip_angle_brackets(@$this->fbl_hash['Original-mail-from']);
            $this->fbl_hash['Original-rcpt-to'] = $this->strip_angle_brackets(@$this->fbl_hash['Original-rcpt-to']);
            $this->output[0]['recipient'] = $this->fbl_hash['Original-rcpt-to'];
        } else if ($this->looks_like_an_autoresponse) {
            // is this an autoresponse ?
            $this->output[0]['action'] = 'autoresponse';     #??? 'transient'  'autoreply' ??
            $this->output[0]['autoresponse'] = $this->autoresponse; #??? 4.3.2
            // grab the first recipient and break
            $this->output[0]['recipient'] = isset($this->head_hash['Return-path']) ? $this->strip_angle_brackets($this->head_hash['Return-path']) : '';
            if (empty($this->output[0]['recipient'])) {
                $arrFailed = $this->find_email_addresses($body);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    break;
                }
            }
        } elseif ($this->is_RFC1892_multipart_report() === TRUE) {
            $rpt_hash = $this->parse_machine_parsable_body_part($mime_sections['machine_parsable_body_part']);
            if (isset($rpt_hash['per_recipient'])) {
                for ($i = 0; $i < count($rpt_hash['per_recipient']); $i++) {
                    $this->output[$i]['recipient'] = $this->find_recipient($rpt_hash['per_recipient'][$i]);
                    $mycode = @$this->format_status_code($rpt_hash['per_recipient'][$i]['Status']);
                    $this->output[$i]['deliverystatus'] = @$mycode['code'];
                    $this->output[$i]['action'] = @$rpt_hash['per_recipient'][$i]['Action'];
                }
            } else {
                $arrFailed = $this->find_email_addresses($mime_sections['first_body_part']);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['deliverystatus'] = $this->get_status_code_from_text($this->output[$j]['recipient'], 0);
                    $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['deliverystatus']);
                }
            }
        } elseif (isset($this->head_hash['X-failed-recipients'])) {
            //  Busted Exim MTA
            //  Up to 50 email addresses can be listed on each header.
            //  There can be multiple X-Failed-Recipients: headers. - (not supported)
            $arrFailed = explode(',', $this->head_hash['X-failed-recipients']);
            for ($j = 0; $j < count($arrFailed); $j++) {
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['deliverystatus'] = $this->get_status_code_from_text($this->output[$j]['recipient'], 0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['deliverystatus']);
            }
        } else if (!empty($boundary) && $this->looks_like_a_bounce) {
            // oh god it could be anything, but at least it has mime parts, so let's try anyway
            $arrFailed = $this->find_email_addresses($mime_sections['first_body_part']);
            for ($j = 0; $j < count($arrFailed); $j++) {
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['deliverystatus'] = $this->get_status_code_from_text($this->output[$j]['recipient'], 0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['deliverystatus']);
            }
        } else if ($this->looks_like_a_bounce) {
            // last ditch attempt
            // could possibly produce erroneous output, or be very resource consuming,
            // so be careful.  You should comment out this section if you are very concerned
            // about 100% accuracy or if you want very fast performance.
            // Leave it turned on if you know that all messages to be analyzed are bounces.
            $arrFailed = $this->find_email_addresses($body);
            for ($j = 0; $j < count($arrFailed); $j++) {
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['deliverystatus'] = $this->get_status_code_from_text($this->output[$j]['recipient'], 0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['deliverystatus']);
            }
        }
        // else if()..... add a parser for your busted-ass MTA here

        // fill additional and dependant fields
        $this->output[0]['reason'] = $this->get_reason_by_statuscode($this->output[0]['deliverystatus']);
        $this->output[0]['messageid'] = empty($this->original_letter_header) ? '' : $this->original_letter_header['Message-id'];
        $this->output[0]['subject'] = empty($this->original_letter_header) ? '' : $this->original_letter_header['Subject'];

        // remove empty array indices
        $tmp = array();
        foreach ($this->output as $arr) {
            if (empty($arr['recipient']) && empty($arr['status']) && empty($arr['action'])) continue;
            $tmp[] = $arr;
        }
        $this->output = $tmp;

        // accessors
        /*if it is an FBL, you could use the class variables to access the
		data (Unlike Multipart-reports, FBL's report only one bounce)
		*/
        $this->type = $this->find_type();
        $this->action = isset($this->output[0]['action']) ? $this->output[0]['action'] : '';
        $this->status = isset($this->output[0]['status']) ? $this->output[0]['status'] : '';
        $this->subject = ($this->subject) ? $this->subject : $this->head_hash['Subject'];
        $this->recipient = isset($this->output[0]['recipient']) ? $this->output[0]['recipient'] : '';
        $this->feedback_type = (isset($this->fbl_hash['Feedback-type'])) ? $this->fbl_hash['Feedback-type'] : "";

        // sniff out any web beacons
        if ($this->web_beacon_preg_1) $this->web_beacon_1 = $this->find_web_beacon($body, $this->web_beacon_preg_1);
        if ($this->web_beacon_preg_2) $this->web_beacon_2 = $this->find_web_beacon($body, $this->web_beacon_preg_2);
        if ($this->x_header_search_1) $this->x_header_beacon_1 = $this->find_x_header($this->x_header_search_1);
        if ($this->x_header_search_2) $this->x_header_beacon_2 = $this->find_x_header($this->x_header_search_2);

        return $this->output;
    }

    private function splitHeadAndBody($letter) {
        return strpos($letter, "\r\n\r\n") !== false ? preg_split("/\r\n\r\n/", $letter, 2) : [$letter, ''];
    }

    function init_bouncehandler($blob) {
        $this->head_hash = array();
        $this->fbl_hash = array();
        $this->body_hash = array();
        $this->looks_like_a_bounce = false;
        $this->looks_like_an_FBL = false;
        $this->is_hotmail_fbl = false;
        $this->type = "";
        $this->feedback_type = "";
        $this->action = "";
        $this->status = "";
        $this->subject = "";
        $this->recipient = "";
        $this->output[0] = [
            'action' => '',
            'deliverystatus' => '',
            'recipient' => '',
            'reason' => '',
            'messageid' => '',
            'subject' => '',
        ];

        $strEmail = str_replace("\r\n", "\n", $blob);    // line returns 1
        $strEmail = str_replace("\n", "\r\n", $strEmail);// line returns 2

        return $strEmail;
    }

    // general purpose recursive heuristic function
    // to try to extract useful info from the bounces produced by busted MTAs
    function get_status_code_from_text($recipient, $index) {
        for ($i = $index; $i < count($this->body_hash); $i++) {
            $line = trim($this->body_hash[$i]);

            // skip empty and Message-ID lines
            if (empty($line)) continue;
            if (stripos($line, 'Message-ID') !== false) continue;

            /******** recurse into the email if you find the recipient ********/
            /*
						if (stristr($line, $recipient) !== false) {
							// the status code MIGHT be in the next few lines after the recipient line,
							// depending on the message from the foreign host... What a laugh riot!
							$status_code = $this->get_status_code_from_text($recipient, $i + 1);
							if ($status_code) return $status_code;
						}
			*/
            /******** exit conditions ********/
            // if it's the end of the human readable part in this stupid bounce
            if (stristr($line, '------ This is a copy of the message') !== false) break;
            if (stristr($line, 'Mensaje original adjunto') !== false) break;

            // if we see an email address other than our current recipient's,
            // Kanon added this line because Hotmail puts the e-mail address too soon and there actually is error message stuff after it.
            if (count($this->find_email_addresses($line)) >= 1 && stristr($line, $recipient) === false && strstr($line, 'FROM:<') === false) break;

            //******** pattern matching ********/
            foreach ($this->bouncelist as $bouncetext => $bouncecode) {
                if (preg_match("/$bouncetext/i", $line, $matches)) return (isset($matches[1])) ? $matches[1] : $bouncecode;
            }

            // Search for a rfc3463 style return code
            if (preg_match('/\W([245]\.[01234567]\.[0-9]{1,2})\W/', $line, $matches)) return $matches[1];

            // search for RFC2821 return code
            // thanks to mark.tolman@gmail.com
            // Maybe at some point it should have it's own place within the main parsing scheme (at line 88)
            if (preg_match('/\]?: ([45][01257][012345]) /', $line, $matches) ||
                preg_match('/^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+/i', $line, $matches)
            ) {
                $mycode = $matches[1];
                // map RFC2821 -> RFC3463 codes
                if ($mycode == '550' || $mycode == '551' || $mycode == '553' || $mycode == '554') {
                    return '5.1.1'; #perm error
                } elseif ($mycode == '452' || $mycode == '552') {
                    return '4.2.2'; #mailbox full
                } elseif ($mycode == '450' || $mycode == '421') {
                    return '4.3.2'; #temp unavailable
                }
            }
        }
        return '5.5.0';  #other or unknown status
    }

    function is_RFC1892_multipart_report() {
        return @$this->head_hash['Content-type']['type'] == 'multipart/report'
        && @$this->head_hash['Content-type']['report-type'] == 'delivery-status'
        && @$this->head_hash['Content-type']['boundary'] !== '';
    }

    function parse_head($headers) {
        if (!is_array($headers)) $headers = explode("\r\n", $headers);
        $hash = $this->standard_parser($headers);
        if (isset($hash['Content-type'])) {
            $multipart_report = explode(';', $hash['Content-type']);
            $hash['Content-type'] = [];
            $hash['Content-type']['type'] = strtolower($multipart_report[0]);
            foreach ($multipart_report as $mr) {
                if (preg_match('/([^=.]*?)=(.*)/i', $mr, $matches)) {
                    // didn't work when the content-type boundary ID contained an equal sign,
                    // that exists in bounces from many Exchange servers
                    //if(preg_match('/([a-z]*)=(.*)?/i', $mr, $matches)){
                    $hash['Content-type'][strtolower(trim($matches[1]))] = str_replace('"', '', $matches[2]);
                }
            }
        }
        return $hash;
    }

    function contenttype_decode($mimepart) {
        $encoding = '7bit';
        $decoded = '';
        foreach (explode("\r\n", $mimepart) as $line) {
            if (preg_match("/^Content-Transfer-Encoding:\s*(\S+)/", $line, $match)) {
                $encoding = $match[1];
                $decoded .= $line . "\r\n";
            } else switch ($encoding) {
                case 'quoted-printable': {
                    if (substr($line, -1) == '=')
                        $line = substr($line, 0, -1);
                    else
                        $line .= "\r\n";
                    $decoded .= quoted_printable_decode($line);
                    break;
                }
                case 'base64': {
                    $decoded .= base64_decode($line);
                    break;
                }
                default:                                  # 7bit, 8bit, binary
                    $decoded .= $line . "\r\n";
            }
        }
        return $decoded;
    }

    function parse_body_into_mime_sections($body, $boundary) {
        if (!$boundary) return array();
        if (is_array($body)) $body = implode("\r\n", $body);
        $body = explode($boundary, $body);
        $mime_sections['first_body_part'] = isset($body[1]) ? $this->contenttype_decode($body[1]) : ''; #proper MIME decode
        $mime_sections['machine_parsable_body_part'] = isset($body[2]) ? $this->contenttype_decode($body[2]) : '';
        $mime_sections['returned_message_body_part'] = isset($body[3]) ? $this->contenttype_decode($body[3]) : '';
        return $mime_sections;
    }

    function standard_parser($content) { // associative array orstr
        $hash = [];
        if (!is_array($content)) $content = explode("\r\n", $content);
        foreach ($content as $line) {
            if (empty($line)) continue;
            if (preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $array)) {
                // Important data in Key: Value format
                $entity = ucfirst(strtolower($array[1]));
                if (isset($array[2]) && strpos($array[2], '=?') !== FALSE) {
                    // decode MIME Header encoding (subject lines etc)
                    $array[2] = @iconv_mime_decode($array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");
                }

                if (empty($hash[$entity])) {
                    $hash[$entity] = trim($array[2]);
                } elseif (isset($hash['Received'])) {
                    // grab extra Received headers :(
                    // pile it on with pipe delimiters,
                    // oh well, SMTP is broken in this way
                    if ($entity and $array[2] and $array[2] != $hash[$entity]) {
                        $hash[$entity] .= "|" . trim($array[2]);
                    }
                }
            } else {
                // ordinary line
                if (preg_match('/^\s+(.+)\s*/', $line, $array) && !empty($entity)) {
                    // but if it starts with space or tab and we have previousely discovered entity
                    // it should be ending of the sentence
                    if (strpos($array[1], '=?') !== FALSE) {
                        // decode MIME Header encoding (subject lines etc)
                        $array[1] = @iconv_mime_decode($array[1], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");
                    }
                    $hash[$entity] .= ' ' . $array[1];
                } else {
                    unset($entity);
                }
            }
        }
        // special formatting
        if (isset($hash['Received'])) $hash['Received'] = explode('|', $hash['Received']);
        return $hash;
    }

    function parse_machine_parsable_body_part($str) {
        $hash = $this->parse_dsn_fields($str);

        $hash['mime_header'] = $this->standard_parser($hash['mime_header']);

        // Per-Message DSN fields
        $hash['per_message'] = isset($hash['per_message']) ? $this->standard_parser($hash['per_message']) : array();
        foreach(['X-postfix-sender', 'Reporting-mta', 'Received-from-mta'] as $key) {
            if (isset($hash['per_message'][$key])) {
                $arr = explode(';', $hash['per_message'][$key]);
                $hash['per_message'][$key] = [
                    'type' => @trim($arr[0]),
                    'addr' =>  @trim($arr[1]),
                ];
            }
        }

        // Per-Recipient DSN fields
        if (isset($hash['per_recipient'])) {
            foreach($hash['per_recipient'] as $i => $rcpt) {
                $temp = $this->standard_parser(explode("\r\n", $rcpt));

                $arr = isset($temp['Final-recipient']) ? explode(';', $temp['Final-recipient']) : array();
                $temp['Final-recipient'] = $this->format_final_recipient_array($arr);

                foreach(['Original-recipient', 'Diagnostic-code', 'Remote-mta'] as $key) {
                    if (isset($temp[$key])) {
                        $arr = explode(';', $temp[$key]);
                        $temp[$key] = [
                            'type' => @trim($arr[0]),
                            'addr' =>  @trim($arr[1]),
                        ];
                    }
                }

                // now this is wierd: plenty of times you see the status code is a permanent failure,
                // but the diagnostic code is a temporary failure.  So we will assert the most general
                // temporary failure in this case.
                if (isset($temp['Diagnostic-code']['text'])) {
                    $ddc = $this->decode_diagnostic_code($temp['Diagnostic-code']['text']);
                    $judgement = $this->get_action_from_status_code($ddc);
                    if ($judgement == 'transient') {
                        if (stristr($temp['Action'], 'failed') !== FALSE) {
                            $temp['Action'] = 'transient';
                            $temp['Status'] = '4.3.0';
                        }
                    }
                }
                $hash['per_recipient'][$i] = $temp;
            }
        }
        return $hash;
    }

    function get_head_from_returned_message_body_part($mime_sections) {
        $temp = explode("\r\n\r\n", $mime_sections['returned_message_body_part']);
        $head = $this->standard_parser($temp[1]);
        $head['From'] = $this->extract_address($head['From']);
        $head['To'] = $this->extract_address($head['To']);
        return $head;
    }

    function extract_address($str) {
        $from = '';
        $from_stuff = preg_split('/[ \"\'\<\>:\(\)\[\]]/', $str);
        foreach ($from_stuff as $things) {
            if (strpos($things, '@') !== false) {
                $from = $things;
            }
        }
        return $from;
    }

    function find_recipient($per_rcpt) {
        $recipient = '';
        if (isset($per_rcpt['Original-recipient']['addr']) && $per_rcpt['Original-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Original-recipient']['addr'];
        } else if (isset($per_rcpt['Final-recipient']['addr']) && $per_rcpt['Final-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Final-recipient']['addr'];
        }
        $recipient = $this->strip_angle_brackets($recipient);
        return $recipient;
    }

    function find_type() {
        if ($this->looks_like_a_bounce) {
            return "bounce";
        } else if ($this->looks_like_an_FBL) {
            return "fbl";
        } elseif ($this->looks_like_an_autoresponse) {
            return "autoresponse";
        } else {
            return false;
        }
    }

    function parse_dsn_fields($dsn_fields) {
        if (!is_array($dsn_fields)) $dsn_fields = explode("\r\n\r\n", $dsn_fields);
        $j = 0;
        $hash = [];
        for ($i = 0; $i < count($dsn_fields); $i++) {
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if ($i == 0) {
                $hash['mime_header'] = $dsn_fields[0];
            } elseif ($i == 1 && !preg_match('/(Final|Original)-Recipient/', $dsn_fields[1])) {
                // some mta's don't output the per_message part, which means
                // the second element in the array should really be
                // per_recipient - test with Final-Recipient - which should always
                // indicate that the part is a per_recipient part
                $hash['per_message'] = $dsn_fields[1];
            } else {
                if ($dsn_fields[$i] == '--') continue;
                $hash['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }
        return $hash;
    }

    // Take a line like "4.2.12 This is an error" and return  "4.2.12" and "This is an error"
    function format_status_code($code) {
        $ret = "";
        if (preg_match('/([245]\.[01234567]\.\d{1,2})\s*(.*)/', $code, $matches)) {
            $ret['code'] = $matches[1];
            $ret['text'] = $matches[2];
        } else if (preg_match('/([245])([01234567])(\d{1,2})\s*(.*)/', $code, $matches)) {
            $ret['code'] = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
            $ret['text'] = $matches[4];
        }
        return $ret;
    }

    function fetch_status_messages($code) {
        include("bounce_statuscodes.php");
        $ret = $this->format_status_code($code);
        $arr = explode('.', $ret['code']);
        $str = "<P><B>" . $status_code_classes[$arr[0]]['title'] . "</B> - " . $status_code_classes[$arr[0]]['descr'] . "  <B>" . $status_code_subclasses[$arr[1] . "." . $arr[2]]['title'] . "</B> - " . $status_code_subclasses[$arr[1] . "." . $arr[2]]['descr'] . "</P>";
        return $str;
    }

    function get_action_from_status_code($code) {
        if ($code == '') return '';
        $ret = $this->format_status_code($code);
        switch (isset($ret['code']) ? $ret['code'][0] : '') {
            case 2 : return 'success'; break;
            case 4 : return 'transient'; break;
            case 5 : return 'failed'; break;
            default: return ''; break;
        }
    }

    function decode_diagnostic_code($dcode) {
        if (preg_match('/(\d\.\d\.\d)\s/', $dcode, $array)) {
            return $array[1];
        } else if (preg_match('/(\d\d\d)\s/', $dcode, $array)) {
            return $array[1];
        }
        return '';
    }

    function is_a_bounce() {
        foreach ($this->bouncesubj as $s) {
            if (preg_match("/^$s/ui", $this->head_hash['Subject'])) return true;
        }
        if (isset($this->head_hash['From']) && preg_match("/^(postmaster|mailer-daemon)\@?/i", $this->head_hash['From'])) return true;
        return false;
    }

    function find_email_addresses($first_body_part) {
        // not finished yet.  This finds only one address.
        return preg_match('/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i', $first_body_part, $matches) ? [$matches[1]] : [];
    }

    // these functions are for feedback loops
    function is_an_ARF() {
        if (isset($this->head_hash['Content-type']['report-type']) && preg_match('/feedback-report/', $this->head_hash['Content-type']['report-type'])) return true;
        if (isset($this->head_hash['X-loop']) && preg_match('/scomp/', $this->head_hash['X-loop'])) return true;
        if (isset($this->head_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient = $this->head_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        if (isset($this->first_body_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient = $this->first_body_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        return false;
    }

    // look for common auto-responders
    function is_an_autoresponse() {
        foreach (array('Auto-submitted', 'X-autorespond') as $a) {
            if (isset($this->head_hash[$a])) {
                $this->autoresponse = "$a: " . $this->head_hash[$a];
                return TRUE;
            }
        }
        foreach (array('Precedence', 'X-precedence') as $a) {
            if (isset($this->head_hash[$a]) && preg_match('/^(auto|junk)/i', $this->head_hash[$a])) {
                $this->autoresponse = "$a: " . $this->head_hash[$a];
                return TRUE;
            }
        }

        $subj = isset($this->head_hash['Subject']) ? $this->head_hash['Subject'] : '';
        foreach ($this->autorespondlist as $a) {
            if (preg_match("/$a/ui", $subj)) {
                $this->autoresponse = $this->head_hash['Subject'];
                return TRUE;
            }
        }
        return FALSE;
    }

    // use a perl regular expression to find the web beacon
    public function find_web_beacon($body, $preg) {
        if (!isset($preg) || !$preg) return "";
        return preg_match($preg, $body, $matches) ? $matches[1] : '';
    }

    public function find_x_header($xheader) {
        $xheader = ucfirst(strtolower($xheader));

        // check the header
        if (isset($this->head_hash[$xheader])) return $this->head_hash[$xheader];

        // check the body too
        $tmp_body_hash = $this->standard_parser($this->body_hash);
        if (isset($tmp_body_hash[$xheader])) return $tmp_body_hash[$xheader];

        return "";
    }

    private function find_fbl_recipients($fbl) {
        if (isset($fbl['Original-rcpt-to'])) {
            return $fbl['Original-rcpt-to'];
        } elseif (isset($fbl['Removal-recipient'])) {
            return trim(str_replace('--', '', $fbl['Removal-recipient']));
        }
        return '';
    }

    private function strip_angle_brackets($recipient) {
        return preg_match('/[<[](.*)[>\]]/', $recipient, $matches) ? trim($matches[1]) : trim($recipient);
    }

    /*
	 * The syntax of the final-recipient field is as follows:
	 * "Final-Recipient" ":" address-type ";" generic-address
	 */
    private function format_final_recipient_array($arr) {
        $output = array('addr' => '', 'type' => '');
        if (count($arr) > 1) {
            $output['type'] = trim($arr[0]);
            $output['addr'] = $this->strip_angle_brackets($arr[1]);
        } else {
            $output['addr'] = $this->strip_angle_brackets($arr[0]);
            $output['type'] = 'unknown';
        }
        return $output;
    }

    public function get_reason_by_statuscode($code) {
        if ($code == '5.7.1') return 'filtered';
        if (in_array($code, ['4.2.0', '4.2.2', '4.3.2'])) return 'notaccept';
        return 'userunknown';
    }
}