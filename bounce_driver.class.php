<?php
//error_reporting(E_ALL);

/* BOUNCE HANDLER Class, Version 7.3
 * Description: "chops up the bounce into associative arrays"
 *     ~ http://www.anti-spam-man.com/php_bouncehandler/v7.3/
 *     ~ https://github.com/cfortune/PHP-Bounce-Handler/
 *     ~ http://www.phpclasses.org/browse/file/11665.html
 */

/* Debugging / Contributers:
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
class BounceHandler{
    
    /**** VARS ****************************************************************/
    public $head_hash = array();
    public $fbl_hash = array();
    public $body_hash = array(); // not necessary
    public $bouncelist = array(); // from bounce_responses.txt
    public $autorespondlist = array(); // from bounce_responses.txt

    public $looks_like_a_bounce = false;
    public $looks_like_an_FBL = false;
    public $looks_like_an_autoresponse = false;
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

    // the raw data set, a multiArray
    public $output = array();

    
    /**** INSTANTIATION *******************************************************/
    public function __construct(){
        $this->output[0]['action']  = "";
        $this->output[0]['status']  = "";
        $this->output[0]['recipient'] = "";
        include_once('bounce_responses.php');
        $this->bouncelist = $bouncelist;
        $this->autorespondlist = $autorespondlist;
    }
    

    /**** METHODS *************************************************************/
    // this is the most commonly used public method
    // quick and dirty
    // useage: $multiArray = $this->get_the_facts($strEmail);
    public function parse_email($eml){
        return $this->get_the_facts($eml);
    }
    public function get_the_facts($eml){
        // fluff up the email
        $bounce = $this->init_bouncehandler($eml);
        list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);
        $this->head_hash = $this->parse_head($head);

        // parse the email into data structures
        $boundary = @$this->head_hash['Content-type']['boundary'];
        $mime_sections = $this->parse_body_into_mime_sections($body, $boundary);
        $this->body_hash = preg_split("/\r\n/", $body);
        $this->first_body_hash = $this->parse_head(@$mime_sections['first_body_part']);

        $this->looks_like_a_bounce = $this->is_a_bounce();
        $this->looks_like_an_FBL = $this->is_an_ARF();
        $this->looks_like_an_autoresponse = $this->is_an_autoresponse();
        

        /* If you are trying to save processing power, and don't care much
         * about accuracy then uncomment this statement in order to skip the
         * heroic text parsing below. 
         */
        //if(!$this->looks_like_a_bounce && !$this->looks_like_an_FBL && !$this->looks_like_an_autoresponse){
        //    return "unknown";
        //}


        /*** now we try all our weird text parsing methods (E-mail is weird!) ******************************/

        // is it a Feedback Loop, in Abuse Feedback Reporting Format (ARF)?
        // http://en.wikipedia.org/wiki/Abuse_Reporting_Format#Abuse_Feedback_Reporting_Format_.28ARF.29
        if($this->looks_like_an_FBL){
            $this->output[0]['action'] = 'failed';
            $this->output[0]['status'] = "5.7.1";
            $this->subject = trim(str_ireplace("Fw:", "", $this->head_hash['Subject']));
            if($this->is_hotmail_fbl === true){
                // fill in the fbl_hash with sensible values
                $this->fbl_hash['Content-disposition'] = 'inline';
                $this->fbl_hash['Content-type'] = 'message/feedback-report';
                $this->fbl_hash['Feedback-type'] = 'abuse';
                $this->fbl_hash['User-agent'] = 'Hotmail FBL';
                if (isset($this->first_body_hash['Date'])) {
                    $this->fbl_hash['Received-date'] = $this->first_body_hash['Date'];
                }
                if (!empty($this->recipient)){
                    $this->fbl_hash['Original-rcpt-to'] = $this->recipient;
                }
                if(isset($this->first_body_hash['X-sid-pra'])){
                    $this->fbl_hash['Original-mail-from'] = $this->first_body_hash['X-sid-pra'];
                }
            }
            else{
                $this->fbl_hash = $this->standard_parser($mime_sections['machine_parsable_body_part']);
                $returnedhash = $this->standard_parser($mime_sections['returned_message_body_part']);
                if (empty($this->fbl_hash['Original-mail-from']) && !empty($returnedhash['From'])) {
                    $this->fbl_hash['Original-mail-from'] = $returnedhash['From'];
                }
                if (empty($this->fbl_hash['Original-rcpt-to']) && !empty($this->fbl_hash['Removal-recipient']) ) {
                    $this->fbl_hash['Original-rcpt-to'] = $this->fbl_hash['Removal-recipient'];
                }
                elseif (!empty($returnedhash['To'])) {
                    $this->fbl_hash['Original-rcpt-to'] = $returnedhash['To'];
                }
            }
            // warning, some servers will remove the name of the original intended recipient from the FBL report,
            // replacing it with redacted@rcpt-hostname.com, making it utterly useless, of course (unless you used a web-beacon).
            // here we try our best to give you the actual intended recipient, if possible.
            if (preg_match('/Undisclosed|redacted/i', $this->fbl_hash['Original-rcpt-to']) && isset($this->fbl_hash['Removal-recipient']) ) {
                $this->fbl_hash['Original-rcpt-to'] = @$this->fbl_hash['Removal-recipient'];
            }
            if (empty($this->fbl_hash['Received-date']) && !empty($this->fbl_hash[@'Arrival-date']) ) {
                $this->fbl_hash['Received-date'] = @$this->fbl_hash['Arrival-date'];
            }
            $this->fbl_hash['Original-mail-from'] = $this->strip_angle_brackets(@$this->fbl_hash['Original-mail-from']);
            $this->fbl_hash['Original-rcpt-to']   = $this->strip_angle_brackets(@$this->fbl_hash['Original-rcpt-to']);
            $this->output[0]['recipient'] = $this->fbl_hash['Original-rcpt-to'];
        }

        else if (preg_match("/auto.{0,20}reply|vacation|(out|away|on holiday).*office/i", $this->head_hash['Subject'])){
            // looks like a vacation autoreply, ignoring
            $this->output[0]['action'] = 'autoreply';
        } 

        // is this an autoresponse ?
        else if ($this->looks_like_an_autoresponse) {
            $this->output[0]['action'] = 'transient';
            $this->output[0]['status'] = '4.3.2';
            // grab the first recipient and break
            $this->output[0]['recipient'] = isset($this->head_hash['Return-path']) ? $this->strip_angle_brackets($this->head_hash['Return-path']) : '';
            if(empty($this->output[0]['recipient'])){
                $arrFailed = $this->find_email_addresses($body);
                for($j=0; $j<count($arrFailed); $j++){
                    $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                    break; 
                }
            }
        }

        else if ($this->is_RFC1892_multipart_report() === TRUE){
            $rpt_hash = $this->parse_machine_parsable_body_part($mime_sections['machine_parsable_body_part']);
            for($i=0; $i<count($rpt_hash['per_recipient']); $i++){
                $this->output[$i]['recipient'] = $this->find_recipient($rpt_hash['per_recipient'][$i]);
                $mycode = @$this->format_status_code($rpt_hash['per_recipient'][$i]['Status']);
                $this->output[$i]['status'] = @$mycode['code'];
                $this->output[$i]['action'] = @$rpt_hash['per_recipient'][$i]['Action'];
            }
        }

        else if(isset($this->head_hash['X-failed-recipients'])) {
            //  Busted Exim MTA
            //  Up to 50 email addresses can be listed on each header.
            //  There can be multiple X-Failed-Recipients: headers. - (not supported)
            $arrFailed = preg_split("/\,/", $this->head_hash['X-failed-recipients']);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }

        else if(!empty($boundary) && $this->looks_like_a_bounce){
            // oh god it could be anything, but at least it has mime parts, so let's try anyway
            $arrFailed = $this->find_email_addresses($mime_sections['first_body_part']);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }

        else if($this->looks_like_a_bounce){
            // last ditch attempt
            // could possibly produce erroneous output, or be very resource consuming,
            // so be careful.  You should comment out this section if you are very concerned
            // about 100% accuracy or if you want very fast performance.
            // Leave it turned on if you know that all messages to be analyzed are bounces.
            $arrFailed = $this->find_email_addresses($body);
            for($j=0; $j<count($arrFailed); $j++){
                $this->output[$j]['recipient'] = trim($arrFailed[$j]);
                $this->output[$j]['status'] = $this->get_status_code_from_text($this->output[$j]['recipient'],0);
                $this->output[$j]['action'] = $this->get_action_from_status_code($this->output[$j]['status']);
            }
        }
        // else if()..... add a parser for your busted-ass MTA here
        
        // remove empty array indices
        $tmp = array();
        foreach($this->output as $arr){
            if(empty($arr['recipient']) && empty($arr['status']) && empty($arr['action']) ){
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
        $this->action = $this->output[0]['action'];
        $this->status = $this->output[0]['status'];
        $this->subject = ($this->subject) ? $this->subject : $this->head_hash['Subject'];
        $this->recipient = $this->output[0]['recipient'];
        $this->feedback_type = @($this->fbl_hash['Feedback-type']) ? $this->fbl_hash['Feedback-type'] : "";

        // sniff out any web beacons
        if($this->web_beacon_preg_1)
            $this->web_beacon_1 = $this->find_web_beacon($body, $this->web_beacon_preg_1);
        if($this->web_beacon_preg_2)
            $this->web_beacon_2 = $this->find_web_beacon($body, $this->web_beacon_preg_2);
        if($this->x_header_search_1)
            $this->x_header_beacon_1 = $this->find_x_header  ($this->x_header_search_1);
        if($this->x_header_search_2)
            $this->x_header_beacon_2 = $this->find_x_header  ($this->x_header_search_2);

        return $this->output;
    }
    


    function init_bouncehandler($blob, $format='string'){
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
        $this->output = array();

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
            $strEmail = str_replace("=\r\n", "", $strEmail); // remove MIME line breaks
            $strEmail = str_replace("=3D", "=", $strEmail);  // equals sign =
            $strEmail = str_replace("=09", "  ", $strEmail); // tabs

        //}
        //else if($format=='array'){
        //    $strEmail = "";
        //    for($i=0; $i<$blob; $i++){
        //        $strEmail .= rtrim($blob[$i]) . "\r\n";
        //    }
        //}

        return $strEmail;
    }

    // general purpose recursive heuristic function
    // to try to extract useful info from the bounces produced by busted MTAs
    function get_status_code_from_text($recipient, $index){
        for($i=$index; $i<count($this->body_hash); $i++){
            $line = trim($this->body_hash[$i]);

            /******** recurse into the email if you find the recipient ********/
            if(stristr($line, $recipient)!==FALSE){
                // the status code MIGHT be in the next few lines after the recipient line,
                // depending on the message from the foreign host... What a laugh riot!
                $status_code = $this->get_status_code_from_text($recipient, $i+1);
                if($status_code){
                    return $status_code;
                }

            }

            /******** exit conditions ********/
            // if it's the end of the human readable part in this stupid bounce
            if(stristr($line, '------ This is a copy of the message')!==FALSE){
                return '';
            }
            //if we see an email address other than our current recipient's,
            if(count($this->find_email_addresses($line))>=1
               && stristr($line, $recipient)===FALSE
               && strstr($line, 'FROM:<')===FALSE){ // Kanon added this line because Hotmail puts the e-mail address too soon and there actually is error message stuff after it.
                return '';
            }
            /******** pattern matching ********/
            foreach ($this->bouncelist as $bouncetext => $bouncecode) {
              if (preg_match("/$bouncetext/i", $line, $matches)) {
                return (isset($matches[1])) ? $matches[1] : $bouncecode;
              }
            }

            // rfc1893 return code
            if(preg_match('/\W([245]\.[01234567]\.[012345678])\W/', $line, $matches)){
                if(stripos($line, 'Message-ID')!==FALSE){
                    break;
                }
                $mycode = str_replace('.', '', $matches[1]);
                $mycode = $this->format_status_code($mycode);
                return implode('.', $mycode['code']);
            }

            // search for RFC821 return code
            // thanks to mark.tolman@gmail.com
            // Maybe at some point it should have it's own place within the main parsing scheme (at line 88)
            if(preg_match('/\]?: ([45][01257][012345]) /', $line, $matches)
               || preg_match('/^([45][01257][012345]) (?:.*?)(?:denied|inactive|deactivated|rejected|disabled|unknown|no such|not (?:our|activated|a valid))+/i', $line, $matches))
            {
                $mycode = $matches[1];
                // map common codes to new rfc values
                if($mycode == '450' || $mycode == '550' || $mycode == '551' || $mycode == '554'){
                    $mycode = '511';
                } else if($mycode == '452' || $mycode == '552'){
                    $mycode = '422';
                } else if ($mycode == '421'){
                    $mycode = '432';
                }
                $mycode = $this->format_status_code($mycode);
                return implode('.', $mycode['code']);
            }

        }
        return '';
    }

    function is_RFC1892_multipart_report(){
        return @$this->head_hash['Content-type']['type']=='multipart/report'
           &&  @$this->head_hash['Content-type']['report-type']=='delivery-status'
           &&  @$this->head_hash['Content-type'][boundary]!=='';
    }

    function parse_head($headers){
        if(!is_array($headers)) $headers = explode("\r\n", $headers);
        $hash = $this->standard_parser($headers);
        if(!empty($hash['Content-type'])){//preg_match('/Multipart\/Report/i', $hash['Content-type'])){
            $multipart_report = explode (';', $hash['Content-type']);
            $hash['Content-type']='';
            $hash['Content-type']['type'] = strtolower($multipart_report[0]);
            foreach($multipart_report as $mr){
                if(preg_match('/([^=.]*?)=(.*)/i', $mr, $matches)){
                // didn't work when the content-type boundary ID contained an equal sign,
                // that exists in bounces from many Exchange servers
                //if(preg_match('/([a-z]*)=(.*)?/i', $mr, $matches)){
                    $hash['Content-type'][strtolower(trim($matches[1]))]= str_replace('"','',$matches[2]);
                }
            }
        }
        return $hash;
    }

    function parse_body_into_mime_sections($body, $boundary){
        if(!$boundary) return array();
        if(is_array($body)) $body = implode("\r\n", $body);
        $body = explode($boundary, $body);
        $mime_sections['first_body_part']            = @$body[1];
        $mime_sections['machine_parsable_body_part'] = @$body[2];
        $mime_sections['returned_message_body_part'] = @$body[3];
        return $mime_sections;
    }


    function standard_parser($content){ // associative array orstr
        // receives email head as array of lines
        // simple parse (Entity: value\n)
        $hash = array('Received'=>'');
        if(!is_array($content)) $content = explode("\r\n", $content);
        foreach($content as $line){
            if(preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $array)){
                $entity = ucfirst(strtolower($array[1]));
                if(empty($hash[$entity])){
                    $hash[$entity] = trim($array[2]);
                }
                else if($hash['Received']){
                    // grab extra Received headers :(
                    // pile it on with pipe delimiters,
                    // oh well, SMTP is broken in this way
                    if ($entity and $array[2] and $array[2] != $hash[$entity]){
                        $hash[$entity] .= "|" . trim($array[2]);
                    }
                }
            }
            elseif (preg_match('/^\s+(.+)\s*/', $line) && !empty($entity)) {
                $hash[$entity] .= ' '. $line;
            }
        }
        // special formatting
        $hash['Received']= @explode('|', $hash['Received']);
        $hash['Subject'] = iconv_mime_decode($hash['Subject'], 0, "ISO-8859-1");

        return $hash;
    }

    function parse_machine_parsable_body_part($str){
        //Per-Message DSN fields
        $hash = $this->parse_dsn_fields($str);
        $hash['mime_header'] = $this->standard_parser($hash['mime_header']);
        $hash['per_message'] = $this->standard_parser($hash['per_message']);
        if(!empty($hash['per_message']['X-postfix-sender'])){
            $arr = explode (';', $hash['per_message']['X-postfix-sender']);
            $hash['per_message']['X-postfix-sender']='';
            $hash['per_message']['X-postfix-sender']['type'] = @trim($arr[0]);
            $hash['per_message']['X-postfix-sender']['addr'] = @trim($arr[1]);
        }
        if(!empty($hash['per_message']['Reporting-mta'])){
            $arr = explode (';', $hash['per_message']['Reporting-mta']);
            $hash['per_message']['Reporting-mta']='';
            $hash['per_message']['Reporting-mta']['type'] = @trim($arr[0]);
            $hash['per_message']['Reporting-mta']['addr'] = @trim($arr[1]);
        }
        //Per-Recipient DSN fields
        for($i=0; $i<count($hash['per_recipient']); $i++){
            $temp = $this->standard_parser(explode("\r\n", $hash['per_recipient'][$i]));
            $arr = @explode (';', $temp['Final-recipient']);
            $temp['Final-recipient'] = $this->format_final_recipient_array($arr);
            //$temp['Final-recipient']['type'] = trim($arr[0]);
            //$temp['Final-recipient']['addr'] = trim($arr[1]);
            $arr = @explode (';', $temp['Original-recipient']);
            $temp['Original-recipient']='';
            $temp['Original-recipient']['type'] = @trim($arr[0]);
            $temp['Original-recipient']['addr'] = @trim($arr[1]);
            $arr = @explode (';', $temp['Diagnostic-code']);
            $temp['Diagnostic-code']='';
            $temp['Diagnostic-code']['type'] = @trim($arr[0]);
            $temp['Diagnostic-code']['text'] = @trim($arr[1]);
            // now this is wierd: plenty of times you see the status code is a permanent failure,
            // but the diagnostic code is a temporary failure.  So we will assert the most general
            // temporary failure in this case.
            $ddc=''; $judgement='';
            $ddc = $this->decode_diagnostic_code($temp['Diagnostic-code']['text']);
            $judgement = $this->get_action_from_status_code($ddc);
            if($judgement == 'transient'){
                if(stristr($temp['Action'],'failed')!==FALSE){
                    $temp['Action']='transient';
                    $temp['Status']='4.3.0';
                }
            }
            $hash['per_recipient'][$i]='';
            $hash['per_recipient'][$i]=$temp;
        }
        return $hash;
    }

    function get_head_from_returned_message_body_part($mime_sections){
        $temp = explode("\r\n\r\n", $mime_sections[returned_message_body_part]);
        $head = $this->standard_parser($temp[1]);
        $head['From'] = $this->extract_address($head['From']);
        $head['To'] = $this->extract_address($head['To']);
        return $head;
    }

    function extract_address($str){
        $from_stuff = preg_split('/[ \"\'\<\>:\(\)\[\]]/', $str);
        foreach ($from_stuff as $things){
            if (strpos($things, '@')!==FALSE){$from = $things;}
        }
        return $from;
    }

    function find_recipient($per_rcpt){
        $recipient = '';
        if($per_rcpt['Original-recipient']['addr'] !== ''){
            $recipient = $per_rcpt['Original-recipient']['addr'];
        }
        else if($per_rcpt['Final-recipient']['addr'] !== ''){
            $recipient = $per_rcpt['Final-recipient']['addr'];
        }
        $recipient = $this->strip_angle_brackets($recipient);
        return $recipient;
    }

    function find_type(){
        if($this->looks_like_a_bounce)
            return "bounce";
        else if($this->looks_like_an_FBL)
            return "fbl";
        else
            return false;
    }

    function parse_dsn_fields($dsn_fields){
        if(!is_array($dsn_fields)) $dsn_fields = explode("\r\n\r\n", $dsn_fields);
        $j = 0;
        reset($dsn_fields);
        for($i=0; $i<count($dsn_fields); $i++){
            $dsn_fields[$i] = trim($dsn_fields[$i]);
            if($i==0)
                $hash['mime_header'] = $dsn_fields[0];
            elseif($i==1 && !preg_match('/(Final|Original)-Recipient/',$dsn_fields[1])) {
                // some mta's don't output the per_message part, which means
                // the second element in the array should really be
                // per_recipient - test with Final-Recipient - which should always
                // indicate that the part is a per_recipient part
                $hash['per_message'] = $dsn_fields[1];
            }
            else {
                if($dsn_fields[$i] == '--') continue;
                $hash['per_recipient'][$j] = $dsn_fields[$i];
                $j++;
            }
        }
        return $hash;
    }

    function format_status_code($code){
        $ret = "";
        if(preg_match('/([245]\.[01234567]\.[012345678])(.*)/', $code, $matches)){
            $ret['code'] = $matches[1];
            $ret['text'] = $matches[2];
        }
        else if(preg_match('/([245][01234567][012345678])(.*)/', $code, $matches)){
            preg_match_all("/./", $matches[1], $out);
            $ret['code'] = $out[0];
            $ret['text'] = $matches[2];
        }
        return $ret;
    }

    function fetch_status_messages($code){
        include_once ("rfc1893.error.codes.php");
        $ret = $this->format_status_code($code);
        $arr = explode('.', $ret['code']);
        $str = "<P><B>". $status_code_classes[$arr[0]]['title'] . "</B> - " .$status_code_classes[$arr[0]]['descr']. "  <B>". $status_code_subclasses[$arr[1].".".$arr[2]]['title'] . "</B> - " .$status_code_subclasses[$arr[1].".".$arr[2]]['descr']. "</P>";
        return $str;
    }

    function get_action_from_status_code($code){
        if($code=='') return '';
        $ret = $this->format_status_code($code);
        $stat = $ret['code'][0];
        switch($stat){
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

    function decode_diagnostic_code($dcode){
        if(preg_match("/(\d\.\d\.\d)\s/", $dcode, $array)){
            return $array[1];
        }
        else if(preg_match("/(\d\d\d)\s/", $dcode, $array)){
            return $array[1];
        }
    }

    function is_a_bounce(){
        if(preg_match("/(mail delivery failed|failure notice|warning: message|delivery status notif|delivery failure|delivery problem|spam eater|returned mail|undeliverable|returned mail|delivery errors|mail status report|mail system error|failure delivery|delivery notification|delivery has failed|undelivered mail|returned email|returning message to sender|returned to sender|message delayed|mdaemon notification|mailserver notification|mail delivery system|nondeliverable mail|mail transaction failed)|auto.{0,20}reply|vacation|(out|away|on holiday).*office/i", $this->head_hash['Subject'])) return true;
        if(@preg_match('/auto_reply/',$this->head_hash['Precedence'])) return true;
        if(preg_match("/^(postmaster|mailer-daemon)\@?/i", $this->head_hash['From'])) return true;
        return false;
    }
    
    function find_email_addresses($first_body_part){
        // not finished yet.  This finds only one address.
        if(preg_match("/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i", $first_body_part, $matches)){
            return array($matches[1]);
        }
        else
            return array();
    }


    // these functions are for feedback loops
    function is_an_ARF(){
        if(@preg_match('/feedback-report/',$this->head_hash['Content-type']['report-type'])) return true;
        if(@preg_match('/scomp/',$this->head_hash['X-loop'])) return true;
        if(isset($this->head_hash['X-hmxmroriginalrecipient']))  {
            $this->is_hotmail_fbl = TRUE;
            $this->recipient = $this->head_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        if(isset($this->first_body_hash['X-hmxmroriginalrecipient']) )  {
            $this->is_hotmail_fbl = TRUE;
            $this->recipient = $this->first_body_hash['X-hmxmroriginalrecipient'];
            return true;
        }
        return false;
    }
    
    // look for common auto-responders
    function is_an_autoresponse() {
        if (preg_match('/^=\?utf-8\?B\?(.*?)\?=/', $this->head_hash['Subject'], $matches))
            $subj = base64_decode($matches[1]);
        else
            $subj = $this->head_hash['Subject'];
        foreach ($this->autorespondlist as $a) {
            if (preg_match("/$a/i", $subj)) {
//echo "$a , $subj"; exit;
                $this->autoresponse = $this->head_hash['Subject'];
                return TRUE;
            }
        }
        return FALSE;
    }
    
    
    
    // use a perl regular expression to find the web beacon
    public function find_web_beacon($body, $preg){
        if(!isset($preg) || !$preg)
            return "";
        if(preg_match($preg, $body, $matches))
            return $matches[1];
    }
    
    public function find_x_header($xheader){
        $xheader = ucfirst(strtolower($xheader));
        // check the header
        if(isset($this->head_hash[$xheader])){
            return $this->head_hash[$xheader];
        }
        // check the body too
        $tmp_body_hash = $this->standard_parser($this->body_hash);
        if(isset($tmp_body_hash[$xheader])){
            return $tmp_body_hash[$xheader];
        }
        return "";
    }
    
    private function find_fbl_recipients($fbl){
        if(isset($fbl['Original-rcpt-to'])){
            return $fbl['Original-rcpt-to'];
        }
        else if(isset($fbl['Removal-recipient'])){
            return trim(str_replace('--', '', $fbl['Removal-recipient']));
        }
        //else if(){
        //}
        //else {
        //}
    }

    private function strip_angle_brackets($recipient){
        $recipient = str_replace('<', '', $recipient);
        $recipient = str_replace('>', '', $recipient);
        return $recipient;
    }


    /*The syntax of the final-recipient field is as follows:
    "Final-Recipient" ":" address-type ";" generic-address
    */
    private function format_final_recipient_array($arr){
        $output = array('addr'=>'',
                        'type'=>'');
        if(strpos($arr[0], '@')!==FALSE){
            $output['addr'] = @$this->strip_angle_brackets($arr[0]);
            $output['type'] = (!empty($arr[1])) ? @trim($arr[1]) : 'unknown';
        }
        else{
            $output['type'] = @trim($arr[0]);
            $output['addr'] = @$this->strip_angle_brackets($arr[1]);
        }
        return $output;
    }
}/** END class BounceHandler **/
?>
