<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<body>

<?php
//error_reporting(E_ALL);
require_once("bounce_driver.class.php");
$bouncehandler = new Bouncehandler();

if(!empty($_GET['testall'])){
    $files = get_sorted_file_list('eml');
    if (is_array($files)) {
       echo "<P>File Tests:</P>\n";
       foreach($files as $file) {
            echo "<a href=\"".$_SERVER['PHP_SELF']."?eml=".urlencode($file)."\">$file</a> ";
            $bounce = file_get_contents("eml/".$file);
            $multiArray = $bouncehandler->get_the_facts($bounce);
            if(   !empty($multiArray[0]['action'])
               && !empty($multiArray[0]['status'])
               && !empty($multiArray[0]['recipient']) ){
                print " - Passed<br>\n";
            }
            else{
                print "<font color=red> - WRONG</font><br>\n";
                print "<pre>\n";
                print_r($multiArray[0]);
                print "</pre>\n";
            }
       }
    }
}
?>

<h1>bounce_driver.class.php -- Version 7.3</h1>

<P>
    Chris Fortune ~ <a href="http://cfortune.kics.bc.ca">http://cfortune.kics.bc.ca</a>
</P>

<P>
July 4, 2013
</P>
<P>
Replaced deprecated split() function.
Added auto-responder identification filter.  
Suppressed php Notice errors.
<P>
<HR>
    <!--  onclick="alert('not ready just yet'); return false;" -->
<a href="php.bouncehandler.v7.3.zip">Download source code</a>
<HR>


<P>
Feb 3, 2011
</P>
<P>
Hey! Class is no longer static, it is rewritten in dynamic <code>$this-></code> notation.  It's much easier to customize now.  If you are upgrading from a previous version, you will need to rewrite your method invocation code.
<P>
<HR>
    <!--  onclick="alert('not ready just yet'); return false;" -->
<a href="php.bouncehandler.v7.0.zip">Download source code</a>
<HR>

<P>
This bounce handler Attempts to parse Multipart reports for hard bounces, according to <a href='http://www.faqs.org/rfcs/rfc1892.html'>RFC1892</a> (RFC 1892 - The Multipart/Report Content Type for the Reporting of Mail System Administrative Messages) and <a href='http://www.faqs.org/rfcs/rfc1894.html'>RFC1894</a> (RFC 1894 - An Extensible Message Format for Delivery Status Notifications).  We can reuse this for any well-formed bounces. </P>
<P>
It handles FBL (Feedback Loop) emails, if they are in Abuse Feedback Reporting Format, <a href="http://en.wikipedia.org/wiki/Abuse_Reporting_Format#Abuse_Feedback_Reporting_Format_.28ARF.29">ARF</a>      (It even handles Hotmail's ridiculous attempts at FBL).  DKIM parsing is not yet implemented.
</P>
<P>
You can configure custom regular expressions to find any web beacons you may have put in your outgoing mails, in either the mail body or an x-header field.  (see source code for examples).  You can use it to track data (eg, recipient, list, mail run, etc...) by sending out unique ids, then parsing them from the bounces.  This is especially useful when parsing FBL's, because usually all recipient fields have been removed (redacted).
</P>
<P>
If the bounce is not well formed, it tries to extract some useful information anyway.  Currently Postfix and Exim are supported, partially.  You can edit the function <code>get_the_facts()</code> if you want to add a parser for your own busted MTA.  Please forward any useful & reuseable code to the keeper of this class.  <a href="http://cfortune.kics.bc.ca/">Chris Fortune</a></P>
<?

// a perl regular expression to find a web beacon in the email body
$bouncehandler->web_beacon_preg_1 = "/u=([0-9a-fA-F]{32})/";
$bouncehandler->web_beacon_preg_2 = "/m=(\d*)/";

// find a web beacon in an X-header field (in the head section)
$bouncehandler->x_header_search_1 = "X-ctnlist-suid";
//$bouncehandler->x_header_search_2 = "X-sumthin-sumpin";

if($_GET['eml']){
    echo "<HR><P><B>".$_GET['eml']."</B>  --  ";
    echo "<a href=\"testdriver1.php\">View a different bounce</a></P>";
    $bounce = file_get_contents("eml/".$_GET['eml']);
    echo "<P>Quick and dirty bounce handler:<BR>
        useage:
        <blockquote><code>
        require_once(\"bounce_driver.class.php\");<br>
        \$bouncehandler = new Bouncehandler();<br>
        \$multiArray = \$bouncehandler->get_the_facts(\$strEmail);  , or<br>
        \$multiArray = \$bouncehandler->parse_email(\$strEmail);</code>  (same thing)
        </blockquote>
        returns a multi-dimensional associative array of bounced recipient addresses and their SMTP status codes (if available)<br><br>
        <code>print_r(\$multiArray);</code>
        <P>";

    $multiArray = $bouncehandler->get_the_facts($bounce);
    echo "<TEXTAREA COLS=100 ROWS=".(count($multiArray)*8).">";
//print_r($bouncehandler); exit;

    print_r($multiArray);
    echo "</TEXTAREA>";

    $bounce = $bouncehandler->init_bouncehandler($bounce, 'string');
    list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);
}
else{
    print "<OL><LI><a href=\"".$_SERVER['PHP_SELF']."?testall=true\">Test All Sample Bounce E-mails</a>\n\n";
    print "<LI>Or, select a bounce email to view the parsed results:</OL>\n";

    $files = get_sorted_file_list('eml');
    if (is_array($files)) {
        reset($files);
        echo "<P>Files:</P>\n";
        foreach($files as $file) {
           echo "<a href=\"".$_SERVER['PHP_SELF']."?eml=".urlencode($file)."\">$file</a><br>\n";
        }
    }
    exit;
}

echo "<P>Will return recipient's email address, the RFC1893 error code, and the action.  Action can be one of the following:
<UL>
<LI>'transient'(temporary problem),
<LI>'failed' (permanent problem),
<LI>'autoreply' (a vacation auto-response), or
<LI>'' (nothing -- not classified).</UL>";

echo "<P>You could dereference the \$multiArray in a 'for loop', for example...</P>
<PRE>
    foreach(\$multiArray as \$the){
        switch(\$the['action']){
            case 'failed':
                //do something
                kill_him(\$the['recipient']);
                break;
            case 'transient':
                //do something else
                \$num_attempts  = delivery_attempts(\$the['recipient']);
                if(\$num_attempts  > 10){
                    kill_him(\$the['recipient']);
                }
                else{
                    insert_into_queue(\$the['recipient'], (\$num_attempts+1));
                }
                break;
            case 'autoreply':
                //do something different
                postpone(\$the['recipient'], '7 days');
                break;
            default:
                //don't do anything
                break;
        }
    }

Or, if it is an FBL, you could use the class variables to access the
data (Unlike Multipart-reports, FBL's report only one bounce)
You could also use them if the output array has only one index:

    if(\$bouncehandler->type == 'fbl'
       or count(\$bouncehandler->output) == 1)
    {
        echo \$bouncehandler->action : .... <i>$bouncehandler->action</i>
        echo \$bouncehandler->status : .... <i>$bouncehandler->status</i>
        echo \$bouncehandler->recipient : .... <i>$bouncehandler->recipient</i>
        echo \$bouncehandler->feedback_type : .... <i>$bouncehandler->feedback_type</i>
    }

These class variables are safe for all bounce types:

    echo \$bouncehandler->type : .... <i>$bouncehandler->type</i>
    echo \$bouncehandler->subject : .... <i>$bouncehandler->subject</i>
    echo \$bouncehandler->web_beacon_1 : .... <i>$bouncehandler->web_beacon_1</i>
    echo \$bouncehandler->web_beacon_2 : .... <i>$bouncehandler->web_beacon_2</i>
    echo \$bouncehandler->x_header_beacon_1 : .... <i>$bouncehandler->x_header_beacon_1</i>
    echo \$bouncehandler->x_header_beacon_2 : .... <i>$bouncehandler->x_header_beacon_2</i>

</PRE>
";
echo "<P>That's all you need to know, but if you want to get more complicated you can.  All methods are public.  See source code.</P><BR>";


echo "<hr><h2>Here is the parsed head</h2>\n";
$head_hash = $bouncehandler->parse_head($head);
echo "<TEXTAREA COLS=100 ROWS=".(count($head_hash)*2.7).">";
print_r($head_hash);
echo "</TEXTAREA>";

if($bouncehandler->is_hotmail_fbl) echo "RRRRRR".$bouncehandler->recipient ;
exit;

if ($bouncehandler->is_RFC1892_multipart_report($head_hash)){
    print "<h2><font color=red>Looks like an RFC1892 multipart report</font></H2>";
}
else if($bouncehandler->looks_like_an_FBL){
    print "<h2><font color=red>It's a Feedback Loop, ";
    if($bouncehandler->is_hotmail_fbl){
        print " in Hotmail Doofus Format (HDF?)</font></H2>";
    }else{
        print " in Abuse Feedback Reporting format (ARF)</font></H2>";
        echo "<TEXTAREA COLS=100 ROWS=12>";
        print_r($bouncehandler->fbl_hash);
        echo "</TEXTAREA>";
    }
}
else {
    print "<h2><font color=red>Not an RFC1892 multipart report</font></H2>";
    echo "<TEXTAREA COLS=100 ROWS=100>";
    print_r($body);
    echo "</TEXTAREA>";
    exit;
}


echo "<h2>Here is the parsed report</h2>\n";
echo "<P>Postfix adds an appropriate X- header (X-Postfix-Sender:), so you do not need to create one via phpmailer.  RFC's call for an optional Original-recipient field, but mandatory Final-recipient field is a fair substitute.</P>";
$boundary = $head_hash['Content-type']['boundary'];
$mime_sections = $bouncehandler->parse_body_into_mime_sections($body, $boundary);
$rpt_hash = $bouncehandler->parse_machine_parsable_body_part($mime_sections['machine_parsable_body_part']);
echo "<TEXTAREA COLS=100 ROWS=".(count($rpt_hash)*16).">";
print_r($rpt_hash);
echo "</TEXTAREA>";



echo "<h2>Here is the error status code</h2>\n";
echo "<P>It's all in the status code, if you can find one.</P>";
for($i=0; $i<count($rpt_hash['per_recipient']); $i++){
    echo "<P>Report #".($i+1)."<BR>\n";
    echo $bouncehandler->find_recipient($rpt_hash['per_recipient'][$i]);
    $scode = $rpt_hash['per_recipient'][$i]['Status'];
    echo "<PRE>$scode</PRE>";
    echo $bouncehandler->fetch_status_messages($scode);
    echo "</P>\n";
}

echo "<h2>The Diagnostic-code</h2> <P>is not the same as the reported status code, but it seems to be more descriptive, so it should be extracted (if possible).";
for($i=0; $i<count($rpt_hash['per_recipient']); $i++){
    echo "<P>Report #".($i+1)." <BR>\n";
    echo $bouncehandler->find_recipient($rpt_hash['per_recipient'][$i]);
    $dcode = $rpt_hash['per_recipient'][$i]['Diagnostic-code']['text'];
    if($dcode){
        echo "<PRE>$dcode</PRE>";
        echo $bouncehandler->fetch_status_messages($dcode);
    }
    else{
        echo "<PRE>couldn't decode</PRE>";
    }
    echo "</P>\n";
}

echo "<H2>Grab original To: and From:</H2>\n";
echo "<P>Just in case we don't have an Original-recipient: field, or a X-Postfix-Sender: field, we can retrieve information from the (optional) returned message body part</P>\n";
$head = $bouncehandler->get_head_from_returned_message_body_part($mime_sections);
echo "<P>From: ".$head['From']."<br>To: ".$head['To']."<br>Subject: ".$head['Subject']."</P>";


echo "<h2>Here is the body in RFC1892 parts</h2>\n";
echo "<P>Three parts: [first_body_part], [machine_parsable_body_part], and [returned_message_body_part]</P>";
echo "<TEXTAREA cols=100 rows=100>";
print_r($mime_sections);
echo "</TEXTAREA>";


/*
                $status_code = $bouncehandler->format_status_code($rpt_hash['per_recipient'][$i]['Status']);
                $status_code_msg = $bouncehandler->fetch_status_messages($status_code['code']);
                $status_code_remote_msg = $status_code['text'];
                $diag_code = $bouncehandler->format_status_code($rpt_hash['per_recipient'][$i]['Diagnostic-code']['text']);
                $diag_code_msg = $bouncehandler->fetch_status_messages($diag_code['code']);
                $diag_code_remote_msg = $diag_code['text'];
*/

function get_sorted_file_list($d){
    $fs = array();
    if ($h = opendir($d)) {
        while (false !== ($f = readdir($h))) {
            if($f=='.' || $f=='..') continue;
            $fs[] = $f;
        }
        closedir($h);
        sort($fs, SORT_STRING);//
    }
    return $fs;
}

?>
