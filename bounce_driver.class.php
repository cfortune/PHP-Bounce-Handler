<?php
/**
 * BOUNCE HANDLER Class, Version 7.4
 *
 * Tries to extract information about why an email bounced.
 *
 * PHP version 5.3
 *
 * The BSD License
 * Copyright (c) 2006-forever, Chris Fortune http://cfortune.kics.bc.ca
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * - Neither the name of the BounceHandler nor the names of its contributors may
 *   be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Email
 * @package   BounceHandler
 * @author    Chris Fortune <cfortune@users.noreply.github.com>
 *            Original development. http://cfortune.kics.bc.ca
 * @author    Richard Bairwell <rbairwell@users.noreply.github.com>
 *            Code cleanups and restructuring. http://blog.rac.me.uk
 * @author    "Kanon" <unknown@invalid.tld>
 * @author    Jamie McClelland <jm@mayfirst.org>
 *            https://mayfirst.org/jamie-mcclelland
 * @author    Michael Cooper <unknown@invalid.tld>
 * @author    Thomas Seifert <mysnip@users.noreply.github.com>
 * @author    Tim Petrowsky <setzen@neuecouch.de>
 *            http://neuecouch.de
 * @author    Willy T. Koch <willytk@users.noreply.github.com>
 *            http://apeland.no
 * @author    ganeshaspeaks.com <unknown@invalid.tld>
 *            FBL development
 * @author    Richard Catto <unknown@invalid.tld>
 *            FBL development
 * @author    Scott Brynen <sbrynen@users.noreply.github.com>
 *            FBL development http://visioncritical.com
 *            https://github.com/visioncritical/PHP-Bounce-Handler
 * @copyright 2006-2014 Chris Fortune and others.
 * @license   http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link      https://github.com/cfortune/PHP-Bounce-Handler/
 * @link      http://www.anti-spam-man.com/php_bouncehandler/v7.3/
 * @link      http://www.phpclasses.org/browse/file/11665.html
 */

/**
 * Class BounceHandler.
 *
 * Interprets email bounces.
 *
 * @category Email
 * @package  BounceHandler
 * @author   Multiple <cfortune@users.noreply.github.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link     https://github.com/cfortune/PHP-Bounce-Handler/
 */
class BounceHandler
{

    /**
     * Extracted header information.
     *
     * @var array
     */
    public $head_hash = array();
    /**
     * Extracted ARF/FBL data.
     *
     * @var array
     */
    public $fbl_hash = array();
    /**
     * Not necessary(?)
     *
     * @var array
     */
    public $body_hash = array();
    /**
     * Set if this looks like a bounce.
     *
     * @var bool
     */
    public $looks_like_a_bounce = false;
    /**
     * Set if this looks like an ARF/FBL "abuse" request.
     *
     * @var bool
     */
    public $looks_like_an_FBL = false;
    /**
     * Set if this looks like an autoresponder.
     *
     * @var bool
     */
    public $looks_like_an_autoresponse = false;
    /**
     * Set if this looks like a Hotmail FBL request.
     *
     * @var bool
     */
    public $is_hotmail_fbl = false;
    /**
     * Regular expression to look for web beacons from emails.
     *
     * E.g. <img src="http://mysite.com/track.php?u=userId12345">
     *
     * @var string
     */
    public $web_beacon_preg_1 = "";
    /**
     * Regular expression to look for web beacons from emails.
     *
     * E.g. <img src="http://mysite.com/track.php?u=userId12345">
     *
     * @var string
     */
    public $web_beacon_preg_2 = "";
    /**
     * String to look for beacons in the email headers.
     *
     * E.g. X-my-custom-header: userId12345
     *
     * @var string
     */
    public $x_header_search_1 = "";

    /**
     * String to look for beacons in the email headers.
     *
     * E.g. X-my-custom-header: userId12345
     *
     * @var string
     */
    public $x_header_search_2 = "";

    /**
     * Type of email - either autoresponse, fbl, bounce or "false"
     *
     * @var string|bool
     */
    public $type = false;

    /**
     * Result of the $web_beacon_preg_1 regular expression.
     *
     * @var string
     */
    public $web_beacon_1 = "";

    /**
     * Result of the $web_beacon_preg_2 regular expression.
     *
     * @var string
     */
    public $web_beacon_2 = "";

    /**
     * FBL feedback type if any.
     *
     * @var string
     */
    public $feedback_type = "";

    /**
     * Result of the x_header_search_1 search.
     *
     * @var string
     */
    public $x_header_beacon_1 = "";

    /**
     * Result of the x_header_search_2 search.
     *
     * @var string
     */
    public $x_header_beacon_2 = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['action']
     *
     * Recommendation action for the email.
     *
     * Only useful for FBL's or if the output array only has one index
     *
     * @var string
     */
    public $action = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['status'].
     *
     * Status of the email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $status = "";

    /**
     *  An accessor for $this->_bouncehandler->get_the_facts()[0]['subect'].
     *
     * Subject of the email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $subject = "";

    /**
     * An accessor for $this->_bouncehandler->get_the_facts()[0]['recipient'].
     *
     * Recipient of the original email.
     * Only useful for FBL's or if the output array only has one index.
     *
     * @var string
     */
    public $recipient = "";
    /**
     * @var array
     */
    public $output = array();

    /**
     * Our cached list of bounce strings to look out for.
     *
     * @var array
     */
    private $_bouncelist = array();

    /**
     * Cached list of auto-respond subjects to look out for.
     *
     * @var array
     */
    private $_autorespondlist = array();

    /**
     * Cached list of bounce subjects to look out for.
     *
     * @var array
     */
    private $_bouncesubj = array();

    /**
     * @var array
     */
    private $_first_body_hash = array();

    /**
     * If this is an auto-response, how did we detect it?
     *
     * @var string
     */
    private $_autoresponse = '';


    /**
     * Constructor.
     *
     * If bouncelist, autorespondlist or bouncesubj is empty, then load them
     * in from the bounce_responses.php file.
     *
     * @param array $bouncelist      text in messages from which to figure out
     *                          kind of bounce this is.
     * @param array $autorespondlist triggers for autoresponders
     * @param array $bouncesubj      trigger subject lines for bounces
     */
    public function __construct(
        $bouncelist = array(),
        $autorespondlist = array(),
        $bouncesubj = array()
    ) {
        $this->_bouncelist = $bouncelist;
        $this->_autorespondlist = $autorespondlist;
        $this->_bouncesubj = $bouncesubj;
        if (empty($bouncelist) || empty($autorespondlist)
            || empty($bouncesubj)
        ) {
            include 'bounce_responses.php';
            if (empty($this->_bouncelist)) {
                $this->_bouncelist = $bouncelist;
            }
            if (empty($this->_autorespondlist)) {
                $this->_autorespondlist = $autorespondlist;
            }
            if (empty($bouncesubj)) {
                $this->_bouncesubj = $bouncesubj;
            }
        }
        $this->init_bouncehandler();

    }

    /**
     * Most commonly used public method - quick and dirty email parsing.
     *
     * Usage: $multiArray = $this->get_the_facts($strEmail);
     *
     * @param string $eml Contents of the email.
     *
     * @return array
     */
    public function parse_email($eml)
    {
        return $this->get_the_facts($eml);
    }

    /**
     * Gets the facts about the email
     *
     * @param string $eml Contents of the email.
     *
     * @return array
     */
    public function get_the_facts($eml)
    {
        // fluff up the email
        $bounce = $this->init_bouncehandler($eml);
        if (strpos($bounce, "\r\n\r\n") !== false) {
            list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);
        } else {
            list($head, $body) = array($bounce, '');
        }
        $this->head_hash = $this->parse_head($head);

        // parse the email into data structures
        $boundary = isset($this->head_hash['Content-type']['boundary'])
            ? $this->head_hash['Content-type']['boundary'] : '';
        $mime_sections = $this->parse_body_into_mime_sections($body, $boundary);
        $this->body_hash = preg_split("/\r\n/", $body);
        $this->_first_body_hash = isset($mime_sections['first_body_part'])
            ? $this->parse_head($mime_sections['first_body_part']) : array();

        $this->looks_like_a_bounce
            = $this->is_RFC1892_multipart_report() || $this->is_a_bounce();
        $this->looks_like_an_FBL = $this->is_an_ARF();
        $this->looks_like_an_autoresponse = $this->is_an_autoresponse();

        /* now we try all our weird text parsing methods (E-mail is weird!) */

        /**
         * Is it a feedback loop in abuse feedback reporting format (ARF)?
         *
         * @link http://en.wikipedia.org/wiki/Abuse_Reporting_Format
         *       #Abuse_Feedback_Reporting_Format_.28ARF.29
         */
        if ($this->looks_like_an_FBL) {
            $this->output[0]['action'] = 'failed';
            $this->output[0]['status'] = "5.7.1";
            $this->subject = trim(
                str_ireplace("Fw:", "", $this->head_hash['Subject'])
            );
            if ($this->is_hotmail_fbl === true) {
                // fill in the fbl_hash with sensible values
                $this->fbl_hash['Source-ip'] = '';
                $this->fbl_hash['Original-mail-from'] = '';
                $this->fbl_hash['Original-rcpt-to'] = '';
                $this->fbl_hash['Feedback-type'] = 'abuse';
                $this->fbl_hash['Content-disposition'] = 'inline';
                $this->fbl_hash['Content-type'] = 'message/feedback-report';
                $this->fbl_hash['User-agent'] = 'Hotmail FBL';
                if (isset($this->_first_body_hash['Date'])) {
                    $this->fbl_hash['Received-date']
                        = $this->_first_body_hash['Date'];
                }
                if (isset($this->head_hash['Subject'])
                    && preg_match(
                        '/complaint about message from ([0-9.]+)/',
                        $this->head_hash['Subject'], $matches
                    )
                ) {
                    $this->fbl_hash['Source-ip'] = $matches[1];
                }
                if (!empty($this->recipient)) {
                    $this->fbl_hash['Original-rcpt-to'] = $this->recipient;
                }
                if (isset($this->_first_body_hash['X-sid-pra'])) {
                    $this->fbl_hash['Original-mail-from']
                        = $this->_first_body_hash['X-sid-pra'];
                }
            } else {
                $this->fbl_hash = $this->standard_parser(
                    $mime_sections['machine_parsable_body_part']
                );
                $returnedhash = $this->standard_parser(
                    $mime_sections['returned_message_body_part']
                );
                if (!empty($returnedhash['Return-path'])) {
                    $this->fbl_hash['Original-mail-from']
                        = $returnedhash['Return-path'];
                } elseif (empty($this->fbl_hash['Original-mail-from'])
                    && !empty($returnedhash['From'])
                ) {
                    $this->fbl_hash['Original-mail-from']
                        = $returnedhash['From'];
                }
                if (empty($this->fbl_hash['Original-rcpt-to'])
                    && !empty($this->fbl_hash['Removal-recipient'])
                ) {
                    $this->fbl_hash['Original-rcpt-to']
                        = $this->fbl_hash['Removal-recipient'];
                } elseif (isset($returnedhash['To'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $returnedhash['To'];
                } else {
                    $this->fbl_hash['Original-rcpt-to'] = '';
                }
                if (!isset($this->fbl_hash['Source-ip'])) {
                    if (!empty($returnedhash['X-originating-ip'])) {
                        $this->fbl_hash['Source-ip']
                            = $this->_strip_angle_brackets(
                                $returnedhash['X-originating-ip']
                            );
                    } else {
                        $this->fbl_hash['Source-ip'] = '';
                    }
                }
            }
            // warning, some servers will remove the name of the original
            // intended recipient from the FBL report,
            // replacing it with redacted@rcpt-hostname.com, making it utterly
            // useless, of course (unless you used a web-beacon).
            // here we try our best to give you the actual intended recipient,
            // if possible.
            if (preg_match(
                '/Undisclosed|redacted/i',
                $this->fbl_hash['Original-rcpt-to']
            )
                && isset($this->fbl_hash['Removal-recipient'])
            ) {
                $this->fbl_hash['Original-rcpt-to']
                    = @$this->fbl_hash['Removal-recipient'];
            }
            if (empty($this->fbl_hash['Received-date'])
                && !empty($this->fbl_hash[@'Arrival-date'])
            ) {
                $this->fbl_hash['Received-date']
                    = @$this->fbl_hash['Arrival-date'];
            }
            $this->fbl_hash['Original-mail-from'] = $this->_strip_angle_brackets(
                @$this->fbl_hash['Original-mail-from']
            );
            $this->fbl_hash['Original-rcpt-to'] = $this->_strip_angle_brackets(
                @$this->fbl_hash['Original-rcpt-to']
            );
            $this->output[0]['recipient'] = $this->fbl_hash['Original-rcpt-to'];
        } elseif ($this->looks_like_an_autoresponse) {
            // is this an autoresponse ?
            $this->output[0]['action'] = 'autoresponse';
            $this->output[0]['autoresponse'] = $this->_autoresponse;
            $this->output[0]['status'] = '2.0';
            // grab the first recipient and break
            $this->output[0]['recipient']
                = isset($this->head_hash['Return-path'])
                ? $this->_strip_angle_brackets($this->head_hash['Return-path'])
                : '';
            if (empty($this->output[0]['recipient'])) {
                $this->output[0]['recipient']
                    = isset($this->head_hash['From'])
                    ? $this->_strip_angle_brackets($this->head_hash['From'])
                    : '';
            }
            if (empty($this->output[0]['recipient'])) {
                $arrFailed = $this->find_email_addresses($body);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    break;
                }
            }
        } else if ($this->is_RFC1892_multipart_report() === true) {

            $rpt_hash = $this->parse_machine_parsable_body_part(
                $mime_sections['machine_parsable_body_part']
            );
            if (isset($rpt_hash['per_recipient'])) {
                for ($i = 0; $i < count($rpt_hash['per_recipient']); $i++) {
                    $this->output[$i]['recipient'] = $this->find_recipient(
                        $rpt_hash['per_recipient'][$i]
                    );
                    $mycode = @$this->format_status_code(
                        $rpt_hash['per_recipient'][$i]['Status']
                    );
                    $this->output[$i]['status'] = $mycode['code'];

                    $this->output[$i]['action']
                        = $this->get_action_from_status_code($mycode['code']);
                }
            } else {
                $arrFailed = $this->find_email_addresses(
                    $mime_sections['first_body_part']
                );
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            }
        } else if (isset($this->head_hash['X-failed-recipients'])) {
            //  Busted Exim MTA
            //  Up to 50 email addresses can be listed on each header.
            //  There can be multiple X-Failed-Recipients: headers. - (not supported)
            $arrFailed = explode(',', $this->head_hash['X-failed-recipients']);
            for ($j = 0; $j < count($arrFailed); $j++) {
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text(
                    $this->output[$j]['recipient'], 0
                );
                $this->output[$j]['action']
                    = $this->get_action_from_status_code(
                        $this->output[$j]['status']
                    );
                $this->looks_like_a_bounce = true;
            }
        } else {
            if (!empty($boundary)) {
                // oh god it could be anything, but at least it has mime parts,
                // so let's try anyway
                $arrFailed = $this->find_email_addresses(
                    $mime_sections['first_body_part']
                );
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            } else {
                // last ditch attempt
                // could possibly produce erroneous output, or be very resource
                // consuming, so be careful.  You should comment out this
                // section if you are very concerned
                // about 100% accuracy or if you want very fast performance.
                // Leave it turned on if you know that all messages to be
                // analyzed are bounces.
                $arrFailed = $this->find_email_addresses($body);
                for ($j = 0; $j < count($arrFailed); $j++) {
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    $this->output[$j]['status']
                        = $this->get_status_code_from_text(
                            $this->output[$j]['recipient'], 0
                        );
                    $this->output[$j]['action']
                        = $this->get_action_from_status_code(
                            $this->output[$j]['status']
                        );
                }
            }
        }
        // else if()..... add a parser for your busted-ass MTA here

        // remove empty array indices
        $tmp = array();
        foreach ($this->output as $arr) {
            if (empty($arr['recipient']) && empty($arr['status'])
                && empty($arr['action'])
            ) {
                continue;
            }
            $tmp[] = $arr;
        }
        $this->output = $tmp;
        // accessors
        /*if it is an FBL, you could use the class variables to access the
        data (Unlike Multipart-reports, FBL's report only one bounce)
        */
        $this->type = $this->find_type();
        $this->action = isset($this->output[0]['action'])
            ? $this->output[0]['action'] : '';
        $this->status = isset($this->output[0]['status'])
            ? $this->output[0]['status'] : '';
        $this->subject = ($this->subject) ? $this->subject
            : $this->head_hash['Subject'];
        $this->recipient = isset($this->output[0]['recipient'])
            ? $this->output[0]['recipient'] : '';
        $this->feedback_type = (isset($this->fbl_hash['Feedback-type']))
            ? $this->fbl_hash['Feedback-type'] : "";

        // sniff out any web beacons
        if ($this->web_beacon_preg_1) {
            $this->web_beacon_1 = $this->find_web_beacon(
                $body, $this->web_beacon_preg_1
            );
        }
        if ($this->web_beacon_preg_2) {
            $this->web_beacon_2 = $this->find_web_beacon(
                $body, $this->web_beacon_preg_2
            );
        }
        if ($this->x_header_search_1) {
            $this->x_header_beacon_1 = $this->find_x_header(
                $this->x_header_search_1
            );
        }
        if ($this->x_header_search_2) {
            $this->x_header_beacon_2 = $this->find_x_header(
                $this->x_header_search_2
            );
        }

        return $this->output;
    }


    /**
     * Setup/reset thge bounce handler.
     *
     * @param string $blob   Inbound email
     * @param string $format Not currently used
     *
     * @return string Contents of email
     */
    function init_bouncehandler($blob = '', $format = 'string')
    {
        $this->head_hash = array();
        $this->fbl_hash = array();
        $this->body_hash = array();
        $this->looks_like_a_bounce = false;
        $this->looks_like_an_FBL = false;
        $this->is_hotmail_fbl = false;
        $this->type = false;
        $this->feedback_type = "";
        $this->action = "";
        $this->status = "";
        $this->subject = "";
        $this->recipient = "";
        $this->output = array();
        $this->output[0]['action'] = '';
        $this->output[0]['status'] = '';
        $this->output[0]['recipient'] = '';
        $strEmail = '';
        if ('' !== $blob) {
            // TODO: accept several formats (XML, string, array)
            // currently accepts only string
            //if($format=='xml_array'){
            //    $strEmail = "";
            //    $out = "";
            //    for($i=0; $i<$blob; $i++){
            //        $out = preg_replace("/<HEADER>/i", "", $blob[$i]);
            //        $out = preg_replace("/</HEADER>/i", "", $out);
            //        $out = preg_replace("/<MESSAGE>/i", "", $out);
            //        $out = preg_replace("/</MESSAGE>/i", "", $out);
            //        $out = rtrim($out) . "\r\n";
            //        $strEmail .= $out;
            //    }
            //}
            //else if($format=='string'){

            $strEmail = str_replace("\r\n", "\n", $blob);    // line returns 1
            $strEmail = str_replace("\n", "\r\n", $strEmail);// line returns 2
            // $strEmail = str_replace("=\r\n", "", $strEmail);
            // remove MIME line breaks (would never exist as #1 above would have
            // have dealt with)
            // $strEmail = str_replace("=3D", "=", $strEmail);
            // equals sign - dealt with in the MIME decode section now
            // $strEmail = str_replace("=09", "  ", $strEmail); // tabs

            //}
            //else if($format=='array'){
            //    $strEmail = "";
            //    for($i=0; $i<$blob; $i++){
            //        $strEmail .= rtrim($blob[$i]) . "\r\n";
            //    }
            //}
        }
        return $strEmail;
    }


    /**
     * Try to extract useful info from the headers bounces produced by
     * busted MTAs.
     *
     * @param string|array $headers Headers of the email
     *
     * @return array
     */
    function parse_head($headers)
    {
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }
        $hash = $this->standard_parser($headers);
        if (isset($hash['Content-type'])) {
            //preg_match('/Multipart\/Report/i', $hash['Content-type'])){
            $multipart_report = explode(';', $hash['Content-type']);
            $hash['Content-type'] = '';
            $hash['Content-type']['type'] = strtolower($multipart_report[0]);
            foreach ($multipart_report as $mr) {
                if (preg_match('/([^=.]*?)=(.*)/i', $mr, $matches)) {
                    // didn't work when the content-type boundary ID contained
                    // an equal sign,
                    // that exists in bounces from many Exchange servers
                    //if(preg_match('/([a-z]*)=(.*)?/i', $mr, $matches)){
                    $hash['Content-type'][strtolower(trim($matches[1]))]
                        = str_replace('"', '', $matches[2]);
                }
            }
        }

        return $hash;
    }

    /**
     * Try and understand information from the headers of the email.
     *
     * @param string|array $content Header of the email
     *
     * @return array
     */
    function standard_parser($content)
    {
        // associative array orstr
        // receives email head as array of lines
        // simple parse (Entity: value\n)
        $hash = array('Received' => '');
        if (!is_array($content)) {
            $content = explode("\r\n", $content);
        }
        foreach ($content as $line) {
            if (preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $array)) {
                $entity = ucfirst(strtolower($array[1]));
                if (isset($array[2]) && strpos($array[2], '=?') !== false) {
                    // decode MIME Header encoding (subject lines etc)
                    $array[2] = iconv_mime_decode(
                        $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8"
                    );
                }
                if (empty($hash[$entity])) {
                    $hash[$entity] = trim($array[2]);
                } else if ($hash['Received']) {
                    // grab extra Received headers :(
                    // pile it on with pipe delimiters,
                    // oh well, SMTP is broken in this way
                    if ($entity and $array[2] and $array[2] != $hash[$entity]) {
                        $hash[$entity] .= "|" . trim($array[2]);
                    }
                }
            } elseif (isset($line) && isset($entity)
                && preg_match(
                    '/^\s+(.+)\s*/', $line, $array
                )
                && $entity
            ) {
                $line = trim($line);
                if (true === isset($array[1])
                    && strpos($array[1], '=?') !== false
                ) {
                    $line = iconv_mime_decode(
                        $array[2], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8"
                    );
                }
                $hash[$entity] .= ' ' . $line;
            }
        }
        // special formatting
        $hash['Received'] = @explode('|', $hash['Received']);
        $hash['Subject'] = isset($hash['Subject']) ? $hash['Subject'] : '';
        return $hash;
    }

    /**
     * Split an email into multiple mime sections
     *
     * @param string|array $body     Body of the email
     * @param string       $boundary The boundary MIME separator.
     *
     * @return array
     */
    function parse_body_into_mime_sections($body, $boundary)
    {

        if (!$boundary) {
            return array();
        }
        if (is_array($body)) {
            $body = implode("\r\n", $body);
        }
        $body = explode(rtrim($boundary, '='), $body);

        $mime_sections['first_body_part'] = isset($body[1])
            ? $this->contenttype_decode($body[1]) : ''; // proper MIME decode
        $mime_sections['machine_parsable_body_part'] = isset($body[2])
            ? $this->contenttype_decode($body[2]) : '';
        $mime_sections['returned_message_body_part'] = isset($body[3])
            ? $this->contenttype_decode($body[3]) : '';
        return $mime_sections;
    }

    /**
     * Decode a content transfer-encoded part of the email.
     *
     * @param string $mimepart MIME encoded email body
     *
     * @return string
     */
    function contenttype_decode($mimepart)
    {
        $encoding = '7bit';
        $decoded = '';
        foreach (explode("\r\n", $mimepart) as $line) {
            if (preg_match(
                "/^Content-Transfer-Encoding:\s*(\S+)/", $line, $match
            )) {
                $encoding = $match[1];
                $decoded .= $line . "\r\n";
            } else {
                switch ($encoding) {
                case 'quoted-printable':
                    if (substr($line, -1) == '=') {
                        $line = substr($line, 0, -1);
                    } else {
                        $line .= "\r\n";
                    }
                    $decoded .= preg_replace_callback(
                        "/=([0-9A-F][0-9A-F])/", function ($matches) {
                            return chr(hexdec($matches[0]));
                        }, $line
                    );
                    break;
                case 'base64':
                    $decoded .= base64_decode($line);
                    break;
                default:                                  // 7bit, 8bit, binary
                    $decoded .= $line . "\r\n";
                }
            }
        }

        return $decoded;
    }

    /**
     * Sees if this is an "obvious bounce".
     *
     * @return bool
     */
    function is_a_bounce()
    {
        if (true === isset($this->head_hash['From'])
            && preg_match(
                '/^(postmaster|mailer-daemon)\@?/i', $this->head_hash['From']
            )
        ) {
            return true;
        }
        foreach ($this->_bouncesubj as $s) {
            if (preg_match("/^$s/i", $this->head_hash['Subject'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sees if this is an obvious "Abuse reporting format" email.
     *
     * @return bool
     */
    function is_an_ARF()
    {
        if (isset($this->head_hash['Content-type']['report-type'])
            && preg_match(
                '/feedback-report/',
                $this->head_hash['Content-type']['report-type']
            )
        ) {
            return true;
        }
        if (isset($this->head_hash['X-loop'])
            && preg_match(
                '/scomp/', $this->head_hash['X-loop']
            )
        ) {
            return true;
        }
        if (isset($this->head_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient = $this->head_hash['X-hmxmroriginalrecipient'];

            return true;
        }
        if (isset($this->_first_body_hash['X-hmxmroriginalrecipient'])) {
            $this->is_hotmail_fbl = true;
            $this->recipient
                = $this->_first_body_hash['X-hmxmroriginalrecipient'];

            return true;
        }

        return false;
    }

    /**
     * Sees if this is an obvious autoresponder email.
     *
     * @return bool
     */
    function is_an_autoresponse()
    {
        if (true === isset($this->head_hash['Auto-submitted'])) {
            if (preg_match(
                '/auto-notified|vacation|away/i',
                $this->head_hash['Auto-submitted']
            )) {
                $this->_autoresponse
                    = 'Auto-submitted: ' . $this->head_hash['Auto-submitted'];
                return true;
            }
        }
        if (true === isset($this->head_hash['X-autorespond'])) {
            if (preg_match(
                '/auto-notified|vacation|away/i',
                $this->head_hash['X-autorespond']
            )) {
                $this->_autoresponse
                    = 'X-autorespond: ' . $this->head_hash['X-autorespond'];
                return true;
            }
        }
        if (true === isset($this->head_hash['Precedence'])
            && preg_match(
                '/^auto-reply/i', $this->head_hash['Precedence']
            )
        ) {
            $this->_autoresponse
                = 'Precedence: ' . $this->head_hash['Precedence'];
            return true;
        }
        if (true === isset($this->head_hash['X-Precedence'])
            && preg_match(
                '/^auto-reply/i', $this->head_hash['X-Precedence']
            )
        ) {
            $this->_autoresponse
                = 'X-Precedence: ' . $this->head_hash['X-Precedence'];
            return true;
        }
        if (true == isset($this->head_hash['Subject'])) {
            foreach ($this->_autorespondlist as $a) {
                $result = preg_match("/$a/i", $this->head_hash['Subject']);
                if (false === $result) {
                    die('Bad autoresponse regular expression ('
                        . preg_last_error() . ') while processing:' . $a);
                }
                if (1 === $result) {
                    $this->_autoresponse = $this->head_hash['Subject'];
                    return true;
                }
            }
        }


        return false;
    }

    /**
     * Strip angled brackets.
     *
     * @param string $recipient Removes angled brackets from an email address.
     *
     * @return string
     */
    private function _strip_angle_brackets($recipient)
    {
        if (preg_match('/[<[](.*)[>\]]/', $recipient, $matches)) {
            return trim($matches[1]);
        } else {
            return trim($recipient);
        }
    }

    /**
     * Finds email addresses in a body.
     *
     * @param string $first_body_part Body of the email
     *
     * @return array
     *
     * @TODO Appears that it should return multiple email addresses.
     */
    function find_email_addresses($first_body_part)
    {
        /**
         * Regular expression for searching for email addresses
         *
         * @link https://bitbucket.org/bairwell/emailcheck/src/
         *       81c6a1a25d28a8abda1673ae1fbec3ba55b72bce/emailcheck.php
         *      Doesn't currently do any "likely domain valid" or similar checks
         */
        $regExp
            = '/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^'.
            '_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-'.
            '\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z'.
            '0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25['.
            '0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|'.
            '[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-'.
            '\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/iS';

        $matched = preg_match($regExp, $first_body_part, $matches);
        if (1 === $matched) {
            return array($matches[0]);
        } else {
            return array();
        }
    }

    /**
     * Is this an RFC 1892 multiple return email?
     *
     * @param array $head_hash Associative array of headers. If not set, will
     *                         use $this->head_hash
     *
     * @return bool
     */
    public function is_RFC1892_multipart_report($head_hash = array())
    {
        if (empty($head_hash)) {
            $head_hash = $this->head_hash;
        }
        if (isset($head_hash['Content-type']['type'])
            && isset($head_hash['Content-type']['report-type'])
            && isset($head_hash['Content-type']['boundary'])
            && 'multipart/report' === $head_hash['Content-type']['type']
            && 'delivery-status' === $head_hash['Content-type']['report-type']
            && '' !== $head_hash['Content-type']['boundary']
        ) {
            return true;
        }
        return false;
    }

    /**
     * Parse delivery service notification sections.
     *
     * @param string $str Email
     *
     * @return array
     */
    function parse_machine_parsable_body_part($str)
    {
        //Per-Message DSN fields
        $hash = $this->parse_dsn_fields($str);
        $hash['mime_header'] = $this->standard_parser($hash['mime_header']);
        $hash['per_message'] = isset($hash['per_message'])
            ? $this->standard_parser($hash['per_message']) : array();
        if (isset($hash['per_message']['X-postfix-sender'])) {
            $arr = explode(';', $hash['per_message']['X-postfix-sender']);
            $hash['per_message']['X-postfix-sender'] = '';
            $hash['per_message']['X-postfix-sender']['type'] = @trim($arr[0]);
            $hash['per_message']['X-postfix-sender']['addr'] = @trim($arr[1]);
        }
        if (isset($hash['per_message']['Reporting-mta'])) {
            $arr = explode(';', $hash['per_message']['Reporting-mta']);
            $hash['per_message']['Reporting-mta'] = '';
            $hash['per_message']['Reporting-mta']['type'] = @trim($arr[0]);
            $hash['per_message']['Reporting-mta']['addr'] = @trim($arr[1]);
        }
        //Per-Recipient DSN fields
        if (isset($hash['per_recipient'])) {
            for ($i = 0; $i < count($hash['per_recipient']); $i++) {
                $temp = $this->standard_parser(
                    explode("\r\n", $hash['per_recipient'][$i])
                );
                $arr = isset($temp['Final-recipient']) ? explode(
                    ';', $temp['Final-recipient']
                ) : array();
                $temp['Final-recipient'] = $this->format_final_recipient_array(
                    $arr
                );
                //$temp['Final-recipient']['type'] = trim($arr[0]);
                //$temp['Final-recipient']['addr'] = trim($arr[1]);
                $temp['Original-recipient'] = array();
                $temp['Original-recipient']['type'] = isset($arr[0]) ? trim(
                    $arr[0]
                ) : '';
                $temp['Original-recipient']['addr'] = isset($arr[1]) ? trim(
                    $arr[1]
                ) : '';
                $arr = isset($temp['Diagnostic-code']) ? explode(
                    ';', $temp['Diagnostic-code']
                ) : array();
                $temp['Diagnostic-code'] = array();
                $temp['Diagnostic-code']['type'] = isset($arr[0]) ? trim(
                    $arr[0]
                ) : '';
                $temp['Diagnostic-code']['text'] = isset($arr[1]) ? trim(
                    $arr[1]
                ) : '';
                // now this is weird: plenty of times you see the status code
                // is a permanent failure,
                // but the diagnostic code is a temporary failure.  So we will
                // assert the most general
                // temporary failure in this case.
                $ddc = $this->decode_diagnostic_code(
                    $temp['Diagnostic-code']['text']
                );
                $judgement = $this->get_action_from_status_code($ddc);
                if ($judgement == 'transient') {
                    if (stristr($temp['Action'], 'failed') !== false) {
                        $temp['Action'] = 'transient';
                        $temp['Status'] = '4.3.0';
                    }
                }
                $hash['per_recipient'][$i] = '';
                $hash['per_recipient'][$i] = $temp;
            }
        }

        return $hash;
    }

    /**
     * Parse delivery service notification fields.
     *
     * @param array|string $dsn_fields List of fields.
     *
     * @return array
     */
    function parse_dsn_fields($dsn_fields)
    {
        if (!is_array($dsn_fields)) {
            $dsn_fields = explode("\r\n\r\n", $dsn_fields);
        }
        $hash = array();
        $j = 0;
        reset($dsn_fields);
        for ($i = 0; $i < count($dsn_fields); $i++) {
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if ($i == 0) {
                $hash['mime_header'] = $dsn_fields[0];
            } elseif ($i == 1
                && !preg_match(
                    '/(Final|Original)-Recipient/', $dsn_fields[1]
                )
            ) {
                // some mta's don't output the per_message part, which means
                // the second element in the array should really be
                // per_recipient - test with Final-Recipient - which should
                // always indicate that the part is a per_recipient part
                $hash['per_message'] = $dsn_fields[1];
            } else {
                if ($dsn_fields[$i] == '--') {
                    continue;
                }
                $hash['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }

        return $hash;
    }


    /**
     * Take a line like "4.2.12 This is an error" and return  "4.2.12" and
     * "This is an error".
     *
     * @param array $arr Input string.
     *
     * @return array
     */
    private function format_final_recipient_array($arr)
    {
        $output = array(
            'addr' => '',
            'type' => ''
        );
        if (isset($arr[1])) {
            if (strpos($arr[0], '@') !== false) {
                $output['addr'] = $this->_strip_angle_brackets($arr[0]);
                $output['type'] = (!empty($arr[1])) ? trim($arr[1]) : 'unknown';
            } else {
                $output['type'] = trim($arr[0]);
                $output['addr'] = $this->_strip_angle_brackets($arr[1]);
            }
        } elseif (isset($arr[0])) {
            if (strpos($arr[0], '@') !== false) {
                $output['addr'] = $this->_strip_angle_brackets($arr[0]);
                $output['type'] = 'unknown';
            }
        }

        return $output;
    }

    /**
     * Decode the diagnostic code into just the code number.
     *
     * @param string $dcode The diagnostic code
     *
     * @return string
     */
    function decode_diagnostic_code($dcode)
    {
        if (preg_match("/(\d\.\d\.\d)\s/", $dcode, $array)) {
            return $array[1];
        } else if (preg_match("/(\d\d\d)\s/", $dcode, $array)) {
            return $array[1];
        }

        return '';
    }

    /**
     * Get a brief status/recommend action from the status code.
     *
     * @param string $code The status code string supplied.
     *
     * @return string Either "success", "transient", "failed" or "" (unknown).
     */
    function get_action_from_status_code($code)
    {
        if ($code == '') {
            return '';
        }
        $ret = $this->format_status_code($code);
        /**
         * We weren't able to read the code
         */
        if ($ret['code'] === '') {
            return '';
        }
        /**
         * Work out the rough status from the first digit of the code
         */
        switch (substr($ret['code'], 0, 1)) {
        case(2):
            return 'success';
            break;
        case(4):
            return 'transient';
            break;
        case(5):
            return 'failed';
            break;
        default:
            return '';
            break;
        }
    }

    /**
     * Extract the code and text from a status code string.
     *
     * @param string $code   A status code string in the format 12.34.56 Reason or 123456 reason
     *                     Reason or 123456 reason
     * @param bool   $strict Only accept triplets (12.34.56) and not that
     *                     "breaks RFC" 12.34 format
     *
     * @return array Associative array containing code (two or three decimal
     * separated numbers) and text
     */
    function format_status_code($code, $strict = false)
    {
        $ret = array('code' => '', 'text' => '');
        $matches = array();
        if (preg_match(
            '/([245]\.[01234567]\.\d{1,2})\s*(.*)/', $code, $matches
        )) {
            $ret['code'] = $matches[1];
            $ret['text'] = $matches[2];
        } else if (preg_match(
            '/([245])([01234567])(\d{1,2})\s*(.*)/', $code, $matches
        )) {
            $ret['code'] = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
            $ret['text'] = $matches[4];
        } else if (false === $strict
            && preg_match(
                '/([245]\.[01234567])\s*(.*)/', $code, $matches
            )
        ) {
            /**
             * Handle major.minor code style (which is against RFCs - should
             * always be major.minor.sub)
             */
            $ret['code'] = $matches[1] . '.0';
            $ret['text'] = $matches[2];
        } else if (false === $strict
            && preg_match(
                '/([245])([01234567])\s*(.*)/', $code, $matches
            )
        ) {
            /**
             * Handle major.minor code style (which is against RFCs - should
             * always be major.minor.sub)
             */
            $ret['code'] = $matches[1] . '.' . $matches[2] . '.0';
            $ret['text'] = $matches[3];
        }
        return $ret;
    }

    /**
     * Find the recipient from either the original-recipient or
     * final-recipieint settings.
     *
     * @param array $per_rcpt Headers
     *
     * @return string Email address
     */
    function find_recipient($per_rcpt)
    {
        $recipient = '';
        if ($per_rcpt['Original-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Original-recipient']['addr'];
        } else if ($per_rcpt['Final-recipient']['addr'] !== '') {
            $recipient = $per_rcpt['Final-recipient']['addr'];
        }
        $recipient = $this->_strip_angle_brackets($recipient);

        return $recipient;
    }

    /**
     * @param $recipient
     * @param $index
     *
     * @return string
     */
    function get_status_code_from_text($recipient, $index)
    {
        for ($i = $index; $i < count($this->body_hash); $i++) {
            $line = trim($this->body_hash[$i]);

            //skip Message-ID lines
            if (stripos($line, 'Message-ID') !== false) {
                continue;
            }

            /* recurse into the email if you find the recipient **/
            if (stristr($line, $recipient) !== false) {
                // the status code MIGHT be in the next few lines after the
                // recipient line,
                // depending on the message from the foreign host...
                $status_code = $this->get_status_code_from_text(
                    $recipient, $i + 1
                );
                if ($status_code) {
                    return $status_code;
                }

            }

            /******** exit conditions ********/
            // if it's the end of the human readable part in this stupid bounce
            if (stristr($line, '------ This is a copy of the message') !== false
            ) {
                break;
            }
            //if we see an email address other than our current recipient's,
            if (count($this->find_email_addresses($line)) >= 1
                && stristr($line, $recipient) === false
                && strstr($line, 'FROM:<') === false
            ) {
                // Kanon added this line because Hotmail puts the e-mail
                //address too soon and there actually is error message stuff
                //after it.
                break;
            }

            //******** pattern matching ********/
            foreach ($this->_bouncelist as $bouncetext => $bouncecode) {
                if (preg_match("/$bouncetext/i", $line, $matches)) {
                    return (isset($matches[1])) ? $matches[1] : $bouncecode;
                }
            }

            // Search for a rfc3463 style return code
            if (preg_match(
                '/\W([245]\.[01234567]\.[0-9]{1,2})\W/', $line, $matches
            )) {
                return $matches[1];
                // ??? this seems somewhat redundant
                // $mycode = str_replace('.', '', $matches[1]);
                // $mycode = $this->format_status_code($mycode);
                // return implode('.', $mycode['code']);  #x.y.z format
            }

            // search for RFC2821 return code
            // thanks to mark.tolman@gmail.com
            // Maybe at some point it should have it's own place within the
            // main parsing scheme (at line 88)
            if (preg_match('/\]?: ([45][01257][012345]) /', $line, $matches)
                || preg_match(
                    '/^([45][01257][012345]) (?:.*?)(?:denied|inactive|'.
                    'deactivated|rejected|disabled|unknown|no such|'.
                    'not (?:our|activated|a valid))+/i',
                    $line, $matches
                )
            ) {
                $mycode = $matches[1];
                // map RFC2821 -> RFC3463 codes
                if ($mycode == '550' || $mycode == '551' || $mycode == '553'
                    || $mycode == '554'
                ) {
                    // perm error
                    return '5.1.1';
                } elseif ($mycode == '452' || $mycode == '552') {
                    // mailbox full
                    return '4.2.2';
                } elseif ($mycode == '450' || $mycode == '421') {
                    // temp unavailable
                    return '4.3.2';
                }
                // ???$mycode = $this->format_status_code($mycode);
                // ???return implode('.', $mycode['code']);
            }

        }

        return '5.5.0';  // other or unknown status
    }


    /**
     * Returns the type of email - either autoresponse, fbl, bounce or "false".
     *
     * @return bool|string
     */
    function find_type()
    {
        if ($this->looks_like_an_autoresponse) {
            return "autoresponse";
        } elseif ($this->looks_like_an_FBL) {
            return "fbl";
        } elseif ($this->looks_like_a_bounce) {
            return "bounce";
        } else {
            return false;
        }
    }

    // look for common auto-responders

    /**
     * Search for a web beacon in the email body.
     *
     * @param string $body Email body.
     * @param string $preg Regular expression to look for.
     *
     * @return string
     */
    public function find_web_beacon($body, $preg)
    {
        if (!isset($preg) || !$preg) {
            return '';
        }
        if (preg_match($preg, $body, $matches)) {
            return $matches[1];
        }

        return '';
    }


    /**
     * use a PECL regular expression to find the web beacon
     *
     * @param string $xheader Header string to look for.
     *
     * @return string
     */
    public function find_x_header($xheader)
    {
        $xheader = ucfirst(strtolower($xheader));
        // check the header
        if (isset($this->head_hash[$xheader])) {
            return $this->head_hash[$xheader];
        }
        // check the body too
        $tmp_body_hash = $this->standard_parser($this->body_hash);
        if (isset($tmp_body_hash[$xheader])) {
            return $tmp_body_hash[$xheader];
        }

        return '';
    }

    /**
     * @param $mime_sections
     *
     * @return array
     */
    function get_head_from_returned_message_body_part($mime_sections)
    {
        $temp = explode(
            "\r\n\r\n", $mime_sections['returned_message_body_part']
        );
        $head = $this->standard_parser($temp[1]);
        $head['From'] = $this->extract_address($head['From']);
        $head['To'] = $this->extract_address($head['To']);

        return $head;
    }

    /**
     * @param $str
     *
     * @return mixed
     */
    function extract_address($str)
    {
        $from = '';
        $from_stuff = preg_split('/[ \"\'\<\>:\(\)\[\]]/', $str);
        foreach ($from_stuff as $things) {
            if (strpos($things, '@') !== false) {
                $from = $things;
            }
        }

        return $from;
    }

    /**
     * Format a status code into a HTML marked up reason.
     *
     * @param string $code                   A status code line.
     * @param array  $status_code_classes    Rough description of status code.
     * @param array  $status_code_subclasses Details of each specific subcode.
     *
     * @return string HTML marked up reason
     */
    function fetch_status_messages(
        $code, $status_code_classes = array(), $status_code_subclasses = array()
    ) {
        $array = $this->fetch_status_message_as_array(
            $code, $status_code_classes, $status_code_subclasses
        );
        $str = '<p><b>' . $array['title'] . '</b> - ' . $array['description']
            . ' <b>' . $array['sub_title'] . '</b> - '
            . $array['sub_description'];
        return $str;
    }

    /**
     * Get human readable details of an SMTP status code.
     *
     * Loads in bounce_statuscodes if $status_code_classes or
     * $status_code_subclasses is empty.
     *
     * @param string $code                   A status code line or number.
     * @param array  $status_code_classes    Rough description of the code.
     * @param array  $status_code_subclasses Details of each specific subcode.
     *
     * @return array Human readable details of the code.
     */
    public function fetch_status_message_as_array(
        $code,
        $status_code_classes = array(),
        $status_code_subclasses = array()
    ) {
        $code_classes = $status_code_classes;
        $sub_classes = $status_code_subclasses;
        /**
         * Load from the provided bounce_statuscodes.php file if not set
         */
        if (empty($code_classes) || empty($sub_classes)) {
            include "bounce_statuscodes.php";
            if (empty($code_classes)) {
                $code_classes = $status_code_classes;
            }
            if (empty($sub_classes)) {
                $sub_classes = $status_code_subclasses;
            }
        }
        $return = array(
            'input_code' => $code,
            'formatted_code_code' => '',
            'formatted_code_text=' > '',
            'major_code' => '',
            'sub_code' => '',
            'title' => 'No major code found',
            'description' => '',
            'sub_title' => 'No sub code found',
            'sub_description' => ''
        );
        $formatted_code = $this->format_status_code($code);
        if ('' === $formatted_code['code']) {
            $return['title'] = 'Could not parse code';
            $return['sub_title'] = 'Could not parse code';
        } else {
            $arr = explode('.', $formatted_code['code']);
            $return['formatted_code_code'] = $formatted_code['code'];
            $return['formatted_code_text'] = $formatted_code['text'];
            if (true === isset($arr[0])) {
                $return['major_code'] = $arr[0];
                if (true === isset($code_classes[$arr[0]])) {
                    if (true === isset($code_classes[$arr[0]]['title'])) {
                        $return['title'] = $code_classes[$arr[0]]['title'];
                    } else {
                        $return['title']
                            = 'No title available for major code: ' . $arr[0];
                    }
                    if (true === isset($code_classes[$arr[0]]['descr'])) {
                        $return['description']
                            = $code_classes[$arr[0]]['descr'];
                    }
                } else {
                    $return['title']
                        = 'Unrecognised major code: ' . $arr[0] . 'xxx';
                }
            }
            $sub_label = '';
            if (true === isset($arr[1]) && true === isset($arr[2])) {
                $sub_label = $arr[1] . '.' . $arr[2];
            } elseif (true === isset($arr[1])) {
                $sub_label = $arr[1];
            }
            if ('' !== $sub_label) {
                $return['sub_code'] = $sub_label;
                if (true === isset($sub_classes[$sub_label])) {
                    if (true === isset($sub_classes[$sub_label]['title'])) {
                        $return['sub_title']
                            = $sub_classes[$sub_label]['title'];
                    } else {
                        $return['sub_title']
                            = 'No sub title available for sub code: '
                            . $sub_label;
                    }
                    if (true === isset($sub_classes[$sub_label]['descr'])) {
                        $return['sub_description']
                            = $sub_classes[$sub_label]['descr'];
                    }
                } else {
                    $return['sub_title']
                        = 'Unrecognised sub code: ' . $sub_label;
                }
            }
        }
        return $return;
    }
}
