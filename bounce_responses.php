<?php
#
# Mapping of bounce responses to RFC3463 codes
#
#

# text in messages from which to figure out what kind of bounce
$this->bouncelist = array(
    '[45]\d\d[- ]#?([45]\.\d\.\d{1,2})'                              => 'x',         # use the code from the regex
    'Diagnostic[- ][Cc]ode: smtp; ?\d\d\ ([45]\.\d\.\d{1,2})'        => 'x',         # use the code from the regex
    'Status: ([45]\.\d\.\d{1,2})'                                    => 'x',         # use the code from the regex

    'not yet been delivered'                                    => '4.2.0',     #
    'Message will be retried for'                               => '4.2.0',     #
    'Connection frequency limited\. http:\/\/service\.mail\.qq\.com'   => '4.2.0',

    'Benutzer hat zuviele Mails auf dem Server'                 => '4.2.2',     #.DE "mailbox full"
    'exceeded storage allocation'                               => '4.2.2',     #
    'Mailbox full'                                              => '4.2.2',     #
    'mailbox is full'                                           => '4.2.2',     #BH
    'Mailbox quota usage exceeded'                              => '4.2.2',     #BH
    'Mailbox size limit exceeded'                               => '4.2.2',     #
    'over ?quota'                                               => '4.2.2',     #
    'quota exceeded'                                            => '4.2.2',     #
    'Quota violation'                                           => '4.2.2',     #
    'User has exhausted allowed storage space'                  => '4.2.2',     #
    'User has too many messages on the server'                  => '4.2.2',     #
    'User mailbox exceeds allowed size'                         => '4.2.2',     #
    'mailfolder is full'                                        => '4.2.2',     #
    'user has Exceeded'                                         => '4.2.2',     #
    'not enough storage space'                                  => '4.2.2',     #

    'Delivery attempts will continue to be made for'            => '4.3.2',     #SB: 4.3.2 is a more generic 'defer'; Kanon added. From Symantec_AntiVirus_for_SMTP_Gateways@uqam.ca Im not sure why Symantec delayed this message, but x.2.x means something to do with the mailbox, which seemed appropriate. x.5.x (protocol) or x.7.x (security) also seem possibly appropriate. It seems a lot of times its x.5.x when it seems to me it should be x.7.x, so maybe x.5.x is standard when mail is rejected due to spam-like characteristics instead of x.7.x like I think it should be.
    'delivery temporarily suspended'                            => '4.3.2',     #
    'Greylisted for 5 minutes'                                  => '4.3.2',     #
    'Greylisting in action'                                     => '4.3.2',     #
    'Server busy'                                               => '4.3.2',     #
    'server too busy'                                           => '4.3.2',     #
    'system load is too high'                                   => '4.3.2',     #
    'temporarily deferred'                                      => '4.3.2',     #
    'temporarily unavailable'                                   => '4.3.2',     #
    'Throttling'                                                => '4.3.2',     #
    'too busy to accept mail'                                   => '4.3.2',     #
    'too many connections'                                      => '4.3.2',     #
    'too many sessions'                                         => '4.3.2',     #
    'Too much load'                                             => '4.3.2',     #
    'try again later'                                           => '4.3.2',     #
    'Try later'                                                 => '4.3.2',     #
    'retry timeout exceeded'                                    => '4.4.7',     #
    'queue too long'                                            => '4.4.7',     #

    '554 delivery error:'                                       => '5.1.1',     #SB: Yahoo/rogers.com generic delivery failure (see also OU-00)
    'account has been disabled'                                 => '5.1.1',     #
    'account is unavailable'                                    => '5.1.1',     #
    'Account not found'                                         => '5.1.1',     #
    'Address invalid'                                           => '5.1.1',     #
    'Address is unknown'                                        => '5.1.1',     #
    'Address unknown'                                           => '5.1.1',     #
    'Addressee unknown'                                         => '5.1.1',     #
    'ADDRESS_NOT_FOUND'                                         => '5.1.1',     #
    'bad address'                                               => '5.1.1',     #
    'Bad destination mailbox address'                           => '5.1.1',     #
    'destin. Sconosciuto'                                       => '5.1.1',     #.IT "user unknown"
    'Destinatario errato'                                       => '5.1.1',     #.IT "invalid"
    'Destinatario sconosciuto o mailbox disatttivata'           => '5.1.1',     #.IT "unknown /disabled"
    'does not exist'                                            => '5.1.1',     #
    'Email Address was not found'                               => '5.1.1',     #
    'Excessive userid unknowns'                                 => '5.1.1',     #
    'Indirizzo inesistente'                                     => '5.1.1',     #.IT "no user"
    'Invalid account'                                           => '5.1.1',     #
    'invalid address'                                           => '5.1.1',     #
    'Invalid or unknown virtual user'                           => '5.1.1',     #
    'Invalid mailbox'                                           => '5.1.1',     #
    'Invalid recipient'                                         => '5.1.1',     #
    'Mailbox not found'                                         => '5.1.1',     #
    'mailbox unavailable'                                       => '5.1.1',     #
    'nie istnieje'                                              => '5.1.1',     #.PL "does not exist"
    'Nie ma takiego konta'                                      => '5.1.1',     #.PL "no such account"
    'No mail box available for this user'                       => '5.1.1',     #
    'no mailbox here'                                           => '5.1.1',     #
    'No one with that email address here'                       => '5.1.1',     #
    'no such address'                                           => '5.1.1',     #
    'no such email address'                                     => '5.1.1',     #
    'No such mail drop defined'                                 => '5.1.1',     #
    'No such mailbox'                                           => '5.1.1',     #
    'No such person at this address'                            => '5.1.1',     #
    'no such recipient'                                         => '5.1.1',     #
    'No such user'                                              => '5.1.1',     #
    'not a known user'                                          => '5.1.1',     #
    'not a valid mailbox'                                       => '5.1.1',     #
    'not a valid user'                                          => '5.1.1',     #
    'not available'                                             => '5.1.1',     #
    'not exists'                                                => '5.1.1',     #
    'Recipient address rejected'                                => '5.1.1',     #
    'Recipient not allowed'                                     => '5.1.1',     #
    'Recipient not found'                                       => '5.1.1',     #
    'recipient rejected'                                        => '5.1.1',     #
    'Recipient unknown'                                         => '5.1.1',     #
    "server doesn't handle mail for that user"                  => '5.1.1',     #
    'This account is disabled'                                  => '5.1.1',     #
    'This address no longer accepts mail'                       => '5.1.1',     #
    'This email address is not known to this system'            => '5.1.1',     #
    'Unknown account'                                           => '5.1.1',     #
    'unknown address or alias'                                  => '5.1.1',     #
    'Unknown email address'                                     => '5.1.1',     #
    'Unknown local part'                                        => '5.1.1',     #
    'unknown or illegal alias'                                  => '5.1.1',     #
    'unknown or illegal user'                                   => '5.1.1',     #
    'Unknown recipient'                                         => '5.1.1',     #
    'unknown user'                                              => '5.1.1',     #
    'user disabled'                                             => '5.1.1',     #
    "User doesn't exist in this server"                         => '5.1.1',     #
    'user invalid'                                              => '5.1.1',     #
    'User is suspended'                                         => '5.1.1',     #
    'User is unknown'                                           => '5.1.1',     #
    'User not found'                                            => '5.1.1',     #
    'User not known'                                            => '5.1.1',     #
    'User unknown'                                              => '5.1.1',     #
    'valid RCPT command must precede DATA'                      => '5.1.1',     #
    'was not found in LDAP server'                              => '5.1.1',     #
    'We are sorry but the address is invalid'                   => '5.1.1',     #
    'Unable to find alias user'                                 => '5.1.1',     #

    "domain isn't in my list of allowed rcpthosts"              => '5.1.2',     #
    'Esta casilla ha expirado por falta de uso'                 => '5.1.2',     #BH ES:expired
    'host ?name is unknown'                                     => '5.1.2',     #
    'no relaying allowed'                                       => '5.1.2',     #
    'no such domain'                                            => '5.1.2',     #
    'not our customer'                                          => '5.1.2',     #
    'relay not permitted'                                       => '5.1.2',     #
    'Relay access denied'                                       => '5.1.2',     #
    'relaying denied'                                           => '5.1.2',     #
    'Relaying not allowed'                                      => '5.1.2',     #
    'This system is not configured to relay mail'               => '5.1.2',     #
    'Unable to relay'                                           => '5.1.2',     #
    'unrouteable mail domain'                                   => '5.1.2',     #BH
    'we do not relay'                                           => '5.1.2',     #

    'Old address no longer valid'                               => '5.1.6',     #
    'recipient no longer on server'                             => '5.1.6',     #

    'Sender address rejected'                                   => '5.1.8',     #

    'exceeded the rate limit'                                   => '5.2.0',     #
    'Local Policy Violation'                                    => '5.2.0',     #
    'Mailbox currently suspended'                               => '5.2.0',     #
    'mailbox unavailable'                                       => '5.2.0',     #
    'mail can not be delivered'                                 => '5.2.0',     #
    'Delivery failed'                                           => '5.2.0',     #
    'mail couldn\'t be delivered'                               => '5.2.0',     #
    'The account or domain may not exist'                       => '5.2.0',     #I guess.... seems like 5.1.1, 5.1.2, or 5.4.4 would fit too, but 5.2.0 seemed most generic

    'Account disabled'                                          => '5.2.1',     #
    'account has been disabled'                                 => '5.2.1',     #
    'Account Inactive'                                          => '5.2.1',     #
    'Adressat unbekannt oder Mailbox deaktiviert'               => '5.2.1',     #
    'Destinataire inconnu ou boite aux lettres desactivee'      => '5.2.1',     #.FR disabled
    'mail is not currently being accepted for this mailbox'     => '5.2.1',     #
    'El usuario esta en estado: inactivo'                       => '5.2.1',     #.IT inactive
    'email account that you tried to reach is disabled'         => '5.2.1',     #
    'inactive user'                                             => '5.2.1',     #
    'Mailbox disabled for this recipient'                       => '5.2.1',     #
    'mailbox has been blocked due to inactivity'                => '5.2.1',     #
    'mailbox is currently unavailable'                          => '5.2.1',     #
    'Mailbox is disabled'                                       => '5.2.1',     #
    'Mailbox is inactive'                                       => '5.2.1',     #
    'Mailbox Locked or Suspended'                               => '5.2.1',     #
    'mailbox temporarily disabled'                              => '5.2.1',     #
    'Podane konto jest zablokowane administracyjnie lub nieaktywne'=> '5.2.1',  #.PL locked or inactive
    "Questo indirizzo e' bloccato per inutilizzo"               => '5.2.1',     #.IT blocked/expired
    'Recipient mailbox was disabled'                            => '5.2.1',     #
    'Domain name not found'                                     => '5.2.1',

    'couldn\'t find any host named'                             => '5.4.4',     #
    'couldn\'t find any host by that name'                      => '5.4.4',     #
    'PERM_FAILURE: DNS Error'                                   => '5.4.4',     #SB: Routing failure
    'Temporary lookup failure'                                  => '5.4.4',     #
    'unrouteable address'                                       => '5.4.4',     #
    "can't connect to"                                          => '5.4.4',     #

    'Too many hops'                                             => '5.4.6',     #

    'Requested action aborted'                                  => '5.5.0',     #

    'rejecting password protected file attachment'              => '5.6.2',     #RFC "Conversion required and prohibited"

    '550 OU-00'                                                 => '5.7.1',     #SB hotmail returns a OU-001 if you're on their blocklist
    '550 SC-00'                                                 => '5.7.1',     #SB hotmail returns a SC-00x if you're on their blocklist
    '550 DY-00'                                                 => '5.7.1',     #SB hotmail returns a DY-00x if you're a dynamic IP
    '554 denied'                                                => '5.7.1',     #
    'You have been blocked by the recipient'                    => '5.7.1',     #
    'requires that you verify'                                  => '5.7.1',     #
    'Access denied'                                             => '5.7.1',     #
    'Administrative prohibition - unable to validate recipient' => '5.7.1',     #
    'Blacklisted'                                               => '5.7.1',     #
    'blocke?d? for spam'                                        => '5.7.1',     #
    'conection refused'                                         => '5.7.1',     #
    'Connection refused due to abuse'                           => '5.7.1',     #
    'dial-up or dynamic-ip denied'                              => '5.7.1',     #
    'Domain has received too many bounces'                      => '5.7.1',     #
    'failed several antispam checks'                            => '5.7.1',     #
    'found in a DNS blacklist'                                  => '5.7.1',     #
    'IPs blocked'                                               => '5.7.1',     #
    'is blocked by'                                             => '5.7.1',     #
    'Mail Refused'                                              => '5.7.1',     #
    'Message does not pass DomainKeys'                          => '5.7.1',     #
    'Message looks like spam'                                   => '5.7.1',     #
    'Message refused by'                                        => '5.7.1',     #
    'not allowed access from your location'                     => '5.7.1',     #
    'permanently deferred'                                      => '5.7.1',     #
    'Rejected by policy'                                        => '5.7.1',     #
    'rejected by Windows Live Hotmail for policy reasons'       => '5.7.1',     #
    'Rejected for policy reasons'                               => '5.7.1',     #
    'Rejecting banned content'                                  => '5.7.1',     #
    'Sorry, looks like spam'                                    => '5.7.1',     #
    'spam message discarded'                                    => '5.7.1',     #
    'Too many spams from your IP'                               => '5.7.1',     #
    'TRANSACTION FAILED'                                        => '5.7.1',     #
    'Transaction rejected'                                      => '5.7.1',     #
    'Wiadomosc zostala odrzucona przez system antyspamowy'      => '5.7.1',     #.PL rejected as spam
    'Your message was declared Spam'                            => '5.7.1'      #
);

# triggers for autoresponders
$this->autorespondlist = array(
    'auto:',
    '^away from.{0,15}office',
    '^.?auto(mated|matic).{0,5}(reply|response)',
    '^out.?of (the )?office',
    '^(I am|I\'m|I will).{0,15}\s(away|on vacation|on leave|out of office|out of the office)',
    '^Thank you for your e-?mail',
    '^This is an automated',
    '^Vacation.{0,10}(alert|reply|response)',
    '^Yahoo! auto response',
    "\350\207\252\345\212\250\345\233\236\345\244\215"   #sino.com,  163.com  UTF8 encoded
);

# trigger subject lines for bounces
$this->bouncesubj = array(
    'deletver reports about your e?mail',
    'delivery errors',
    'delivery failure',
    'delivery has failed',
    'delivery notification',
    'delivery problem',
    'delivery reports about your email',
    'delivery status notif',
    'failure delivery',
    'failure notice',
    'mail delivery fail',			#catches failure and failed
    'mail delivery system',
    'mailserver notification',
    'mail status report',
    'mail system error',
    'mail transaction failed',
    'mdaemon notification',
    'message delayed',
    'nondeliverable mail',
    'Non[_ ]remis[_ ]',			#fr
    'No[_ ]se[_ ]puede[_ ]entregar',	#es
    'Onbestelbaar',				#nl
    'returned e?mail',
    'returned to sender',
    'returning message to sender',
    'spam eater',
    'undeliverable',
    'undelivered mail',
    'warning: message',
);

#
# test mapping to ensure that we don't match one within another
#
#foreach ($bouncelist as $l1 => $j) {
#  foreach ($bouncelist as $l2 => $k) {
#    if (($l1 != $l2) && preg_match("/$l1/i", $l2)) {
#      print "'$l1'($j) = '$l2'($k)\n";
#    }
#  }
#}
?>