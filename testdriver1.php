<?php
/**
 * Web based test driver.
 *
 * Simple tests for the emails. Really needs moving to proper PHPUnit tests.
 *
 * PHP version 5
 *
 * @category Email
 * @package  BounceHandler
 * @author   Multiple <cfortune@users.noreply.github.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link     https://github.com/cfortune/PHP-Bounce-Handler/
 */

require_once"bounce_driver.class.php";

error_reporting(E_ALL);


$testDriver=new TestDriver1();
$testDriver->main();

/**
 * Class TestDriver1.
 *
 * Tests the bounce handler in a simple web based manner.
 *
 * @category Email
 * @package  BounceHandler
 * @author   Multiple <cfortune@users.noreply.github.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link     https://github.com/cfortune/PHP-Bounce-Handler/
 */
class TestDriver1
{

    /**
     * The bounce handler object
     *
     * @var Bouncehandler $_bouncehandler
     */
    private $_bouncehandler;

    /**
     * Constructor
     */
    public function __construct() 
    {
        $this->_bouncehandler = new Bouncehandler();
    }

    /**
     * Main Class
     *
     * @return void
     */
    public function main()
    {
        $files = $this->_getSortedFileList('eml');
        $this->_htmlHead();

        if (!empty($_GET['testall'])) {
            $this->_testAllFiles($files);
        } elseif (isset($_GET['eml'])) {
            if (true===in_array($_GET['eml'], $files)) {
                echo "<h2>" . $_GET['eml'] . "</h2>";
                echo '<p><a href="'.$_SERVER['PHP_SELF'];
                echo '?">View a different bounce</a></p>';
                $bounce = file_get_contents("eml/" . $_GET['eml']);
                $this->_testSingle($bounce);
            }
        }
        echo "<OL><LI><a href=\"" . $_SERVER['PHP_SELF'];
            echo "?testall=true\">Test All Sample Bounce E-mails</a>\n\n";
        echo "<LI>Or, select a bounce email to view the parsed results:</OL>\n";

        if (is_array($files)) {
            reset($files);
            echo "<P>Files:</P>\n";
            foreach ($files as $file) {
                echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?eml=";
                echo urlencode($file) . "\">$file</a><br>\n";
            }
        }
    }

    /**
     * Display the HTML prologue
     *
     * @return void
     */
    private function _htmlHead()
    {
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'
            . PHP_EOL;
        echo '<html>' . PHP_EOL;
        echo '<head>' . PHP_EOL;
        echo '<style>' . PHP_EOL;
        echo 'span.dropt {border-bottom: thin dotted; }
span.dropt:hover {text-decoration: none;  z-index: 6; }
span.dropt span {position: absolute; left: -9999px;
  margin: 20px 0 0 0px; padding: 3px 3px 3px 3px;
  border-style:solid; border-color:black; border-width:1px; z-index: 6;}
span.dropt:hover span {left: 2%;width:40%;}
span.dropt span {position: absolute; left: -9999px;
  margin: 4px 0 0 0px; padding: 3px 3px 3px 3px;
  border-style:solid; border-color:black; border-width:1px;}
span.dropt:hover span {margin: 20px 0 0 170px; background: #ffffff; z-index:6;}'
            . PHP_EOL;
        echo '</style>';
        echo '</head>';
        echo '<body>';
    }


    /**
     * Get a list of sorted files.
     *
     * @param string $d Directory name
     *
     * @return array List of alphabetically sorted files.
     */
    function _getSortedFileList($d)
    {
        $fs = array();
        if ($h = opendir($d)) {
            while (false !== ($f = readdir($h))) {
                if ($f == '.' || $f == '..') {
                    continue;
                }
                $fs[] = $f;
            }
            closedir($h);
            sort($fs, SORT_STRING);
        }
        return $fs;
    }

    /**
     * Test all files.
     *
     * @param array $files List of files
     *
     * @return void
     */
    function _testAllFiles($files)
    {
            echo '<p>File Tests:</p>' . PHP_EOL;
            echo '<table border="1">' . PHP_EOL;
            echo '<thead>' . PHP_EOL;
            echo '<tr><th>File</th><th>Action</th>';
        echo '<th colspan="3">Status</th>';
        echo '<th>Recipient</th></tr>'. PHP_EOL;
            echo '</thead>' . PHP_EOL;
            echo '<tbody>' . PHP_EOL;
        foreach ($files as $file) {
            echo '<tr>';
            echo '<td><a href="' . $_SERVER['PHP_SELF'] . '?eml='
                . urlencode($file) . '">' . $file . '</a></td>';
            $bounce = file_get_contents("eml/" . $file);
            $multiArray = $this->_bouncehandler->get_the_facts($bounce);
            if (!empty($multiArray[0]['action'])
                && !empty($multiArray[0]['status'])
                && !empty($multiArray[0]['recipient'])
            ) {
                    echo '<td>' . $multiArray[0]['action'] . '</td>';
                    echo '<td>' . $multiArray[0]['status'] . '</td>';
                    /**
                     * Get the human readable status message description
                     */
                    $human
                        = $this->_bouncehandler->fetch_status_message_as_array(
                            $multiArray[0]['status']
                        );
                    if ('' === $human['description']) {
                        echo '<td>' . htmlspecialchars($human['title'])
                            . '</td>';
                    } else {
                        echo '<td><span class="dropt">' . htmlspecialchars(
                            $human['title']
                        ) . '<span>' . htmlspecialchars(
                            $human['description']
                        ) . '</span></span></td>';
                    }
                    if ('' === $human['sub_description']) {
                        echo '<td>' . htmlspecialchars($human['sub_title'])
                            . '</td>';

                    } else {
                        echo '<td><span class="dropt">' . htmlspecialchars(
                            $human['sub_title']
                        ) . '<span>' . htmlspecialchars(
                            $human['sub_description']
                        ) . '</span></span></td>';
                    }
                    echo '<td>' . htmlspecialchars($multiArray[0]['recipient'])
                    . '</td>';
            } else {
                echo '<td colspan="5" style="color:red;font-weight:bold;">';
                echo 'Unable to parse data<br />>';
                echo '<pre>' . PHP_EOL;
                echo htmlspecialchars(print_r($multiArray, true));
                echo '</pre>' . PHP_EOL;
                echo htmlspecialchars(
                    print_r($$this->_bouncehandler->output, true)
                );
                    echo '</td>';
            }
            echo '</tr>' . PHP_EOL;
        }
            echo '</tbody>';
            echo '</table>';
    }

    /**
     * Tests a single email.
     *
     * @param string $bounce Contents of the bounce email.
     *
     * @return void
     */
    private function _testSingle($bounce) 
    {
        $multiArray = $this->_bouncehandler->get_the_facts($bounce);
        echo "<TEXTAREA COLS=100 ROWS=" . (count($multiArray) * 8) . ">";
        print_r($multiArray);
        echo "</TEXTAREA>";

        $bounce = $this->_bouncehandler->init_bouncehandler($bounce, 'string');
        list($head, $body) = preg_split("/\r\n\r\n/", $bounce, 2);


        echo '<h2>Raw email:</h2><br />';
        echo "<TEXTAREA COLS=100 ROWS=12>";
        echo htmlspecialchars($bounce);
        echo "</TEXTAREA><br />";


        echo "<h2>Parsed head</h2>\n";
        $head_hash = $this->_bouncehandler->parse_head($head);
        echo "<TEXTAREA COLS=100 ROWS=" . (count($head_hash) * 2.7) . ">";
        print_r($head_hash);
        echo "</TEXTAREA><br />";

        if ($this->_bouncehandler->is_RFC1892_multipart_report($head_hash)) {
            echo '<h2 style="color:red;">';
            echo 'Looks like an RFC1892 multipart report';
            echo '</h2>';
        } else if ($this->_bouncehandler->looks_like_an_FBL) {
            echo '<h2 style="color:red;">';
            echo 'Looks like a feedback loop';
            if ($this->_bouncehandler->is_hotmail_fbl) {
                echo ' in Hotmail Doofus Format (HDF?)';
            } else {
                echo ' in Abuse Feedback Reporting format (ARF)';
            }
            echo '</h2>';
            echo "<TEXTAREA COLS=100 ROWS=12>";
            print_r($this->_bouncehandler->fbl_hash);
            echo "</TEXTAREA>";
        } else {
            echo "<h2 style='color:red;'>Not an RFC1892 multipart report</H2>";
            echo "<TEXTAREA COLS=100 ROWS=100>";
            print_r($body);
            echo "</TEXTAREA>";
            exit;
        }


        echo "<h2>Here is the parsed report</h2>\n";
        echo '<p>Postfix adds an appropriate X- header (X-Postfix-Sender:), ';
        echo 'so you do not need to create one via phpmailer.  RFC\'s call ';
        echo 'for an optional Original-recipient field, but mandatory ';
        echo 'Final-recipient field is a fair substitute.</p>';
        $boundary = $head_hash['Content-type']['boundary'];
        $mime_sections
            = $this->_bouncehandler->parse_body_into_mime_sections(
                $body, $boundary
            );
        $rpt_hash
            = $this->_bouncehandler->parse_machine_parsable_body_part(
                $mime_sections['machine_parsable_body_part']
            );
        echo "<TEXTAREA COLS=100 ROWS=" . (count($rpt_hash) * 16) . ">";
        print_r($rpt_hash);
        echo "</TEXTAREA>";


        echo "<h2>Here is the error status code</h2>\n";
        echo "<P>It's all in the status code, if you can find one.</P>";
        for ($i = 0; $i < count($rpt_hash['per_recipient']); $i++) {
            echo "<P>Report #" . ($i + 1) . "<BR>\n";
            echo $this->_bouncehandler->find_recipient(
                $rpt_hash['per_recipient'][$i]
            );
            $scode = $rpt_hash['per_recipient'][$i]['Status'];
            echo "<PRE>$scode</PRE>";
            echo $this->_bouncehandler->fetch_status_messages($scode);
            echo "</P>\n";
        }

        echo '<h2>The Diagnostic-code</h2>';
        echo '<p>is not the same as the reported status code, but it seems ';
        echo 'to be more descriptive, so it should be extracted (if possible).';
        for ($i = 0; $i < count($rpt_hash['per_recipient']); $i++) {
            echo "<P>Report #" . ($i + 1) . " <BR>\n";
            echo $this->_bouncehandler->find_recipient(
                $rpt_hash['per_recipient'][$i]
            );
            $dcode = $rpt_hash['per_recipient'][$i]['Diagnostic-code']['text'];
            if ($dcode) {
                echo "<PRE>$dcode</PRE>";
                echo $this->_bouncehandler->fetch_status_messages($dcode);
            } else {
                echo "<PRE>couldn't decode</PRE>";
            }
            echo "</P>\n";
        }

        echo '<h2>Grab original To: and From:</h2>\n';
        echo '<p>Just in case we don\'t have an Original-recipient: field, or ';
        echo 'a X-Postfix-Sender: field, we can retrieve information from ';
        echo 'the (optional) returned message body part</p>'.PHP_EOL;
        $head
            = $this->_bouncehandler->get_head_from_returned_message_body_part(
                $mime_sections
            );
        echo "<P>From: " . $head['From'];
        echo "<br>To: " . $head['To'];
        echo "<br>Subject: " . $head['Subject'] . "</P>";


        echo "<h2>Here is the body in RFC1892 parts</h2>\n";
        echo '<[>Three parts: [first_body_part], ';
        echo '[machine_parsable_body_part], and ';
        echo ' [returned_message_body_part]</p>';
        echo "<TEXTAREA cols=100 rows=100>";
        print_r($mime_sections);
        echo "</TEXTAREA>";
    }

}

