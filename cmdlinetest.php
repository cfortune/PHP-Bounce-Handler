<?PHP
require_once('bounce_driver.class.php');

if (defined('STDIN')) {
    $handle = fopen("php://stdin","r");
    $email = stream_get_contents($handle);
    fclose($handle);

    $bh = new Bouncehandler();
    $bounceinfo = $bh->get_the_facts($email);
    if ($bh->type) {
      print "TYPE      ". $bh->type. "\n";
      print "ACTION    ". $bounceinfo[0]['action']. "\n";
      print "STATUS    ". $bounceinfo[0]['status']. "\n";
      print "RECIPIENT ". $bounceinfo[0]['recipient']. "\n";
    }
    if ($bh->type == 'fbl') {
        print "ENV FROM  ". $bh->fbl_hash['Original-mail-from']. "\n";
        print "AGENT     ". $bh->fbl_hash['User-agent']. "\n";
        print "IP        ". $bh->fbl_hash['Source-ip']. "\n";
    }
    var_dump($bounceinfo);
    print "\n";

}
?>
