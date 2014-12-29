<?php
/**
 * Command line test utility
 *
 * PHP version 5
 *
 * @category Email
 * @package  BounceHandler
 * @author   Multiple <cfortune@users.noreply.github.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause  BSD
 * @link     https://github.com/cfortune/PHP-Bounce-Handler/
 */

require_once'bounce_driver.class.php';

$total = array();

/**
 * Check an email.
 *
 * @param string $email Contents of an email to check
 *
 * @return void
 */
function checkmail($email)
{
    global $total;
    $bh = new Bouncehandler();
    $bounceinfo = $bh->get_the_facts($email);
    // var_dump($bounceinfo);
    // var_dump($bh);
    print " TYPE      " . @$bh->type . "\n";
    if ($bh->type == 'bounce') {
        print " ACTION    " . $bounceinfo[0]['action'] . "\n";
        print " STATUS    " . $bounceinfo[0]['status'] . "\n";
        print " RECIPIENT " . $bounceinfo[0]['recipient'] . "\n";
    }
    if ($bh->type == 'fbl') {
        print " ENV FROM  " . @$bh->fbl_hash['Original-mail-from'] . "\n";
        print " AGENT     " . @$bh->fbl_hash['User-agent'] . "\n";
        print " IP        " . @$bh->fbl_hash['Source-ip'] . "\n";
    }
    if ($bh->type == 'autoresponse') {
        print " AUTO      " . $bounceinfo[0]['autoresponse'] . "\n";
    }
    if ($bh->type) {
        @$total[$bh->type]++;
    } else {
        @$total['unknown']++;
    }
    print "\n";
}


if (defined('STDIN')) {
    if (count($argv) > 1) {
        for ($i = 1; $i < count($argv); $i++) {
            if (is_dir($argv[$i])) {
                $dh = opendir($argv[$i]);
                while ($fn = readdir($dh)) {
                    if (substr($fn, 0, 1) !== '.') {
                        $email = file_get_contents($argv[$i] . '/' . $fn);
                        print $argv[$i] . "/$fn\n";
                        checkmail($email);
                    }
                }
                closedir($dh);
            } else {
                $email = file_get_contents($argv[$i]);
                print $argv[$i] . "\n";
                checkmail($email);
            }
        }


    } else {
        $handle = fopen("php://stdin", "r");
        $email = stream_get_contents($handle);
        fclose($handle);
        checkmail($email);
    }
    /**
     * Now show the results
     */
    foreach ($total as $t => $v) {
        printf("%-15s  %6d\n", $t, $v);
    }
}
