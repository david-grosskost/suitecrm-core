<?php

// Keeps every email of a non-prod instance inside it. Local and demo send
// everything to Mailpit (compose.local.yaml, compose.demo.yaml): nothing ever
// reaches a real inbox. Prod sets no MAIL_SMTP_HOST, so this script never runs
// there and the administrator configures the real server.
//
// - every outgoing account (the system one, its per-user copies and the users'
//   own accounts) is pointed to Mailpit, without authentication;
// - every incoming mailbox (IMAP) is deactivated: a database copied from prod
//   must not read, nor mark as read, the real mailboxes.
//
// Runs as www-data, at install (suitecrm-install) and at each start of the app
// container (suitecrm-entrypoint), before the cron container starts: a change
// made in the admin screens, or a database copied from prod, is neutralised
// on the next start.
//
// @usage MAIL_SMTP_HOST=mailpit MAIL_SMTP_PORT=1025 MAIL_FROM_ADDRESS=crm@example.test \
//          php /usr/local/share/suitecrm/mail.php

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}
chdir('/var/www/html/public/legacy');
require_once 'include/entryPoint.php';
require_once 'include/OutboundEmail/OutboundEmail.php';

$host = (string) getenv('MAIL_SMTP_HOST');
$port = (int) (getenv('MAIL_SMTP_PORT') ?: 1025);
$fromAddress = (string) getenv('MAIL_FROM_ADDRESS');
$fromName = (string) (getenv('MAIL_FROM_NAME') ?: 'SuiteCRM');

if ($host === '' || $fromAddress === '') {
    fwrite(STDERR, "mail : MAIL_SMTP_HOST et MAIL_FROM_ADDRESS sont obligatoires\n");
    exit(1);
}

// Saving settings refreshes the admin's cached navigation: it needs a user.
global $current_user;
$current_user = BeanFactory::getBean('Users', '1');
if (!$current_user instanceof User || empty($current_user->id)) {
    fwrite(STDERR, "mail : administrateur introuvable\n");
    exit(1);
}

// The system account: created by SuiteCRM if it does not exist yet.
$system = (new OutboundEmail())->getSystemMailerSettings();
$system->mail_sendtype = 'SMTP';
$system->mail_smtptype = 'other';
$system->mail_smtpserver = $host;
$system->mail_smtpport = $port;
// Mailpit accepts everything, without authentication or encryption.
$system->mail_smtpauth_req = 0;
$system->mail_smtpssl = 0;
$system->mail_smtpuser = '';
$system->mail_smtppass = '';
$system->smtp_from_name = $fromName;
$system->smtp_from_addr = $fromAddress;
$system->save();

// Every other outgoing account, whatever its server or sign-in (password or
// OAuth), goes to Mailpit too.
$db = DBManagerFactory::getInstance();
$db->query(sprintf(
    "UPDATE outbound_email SET mail_sendtype = 'SMTP', mail_smtptype = 'other',"
    . ' mail_smtpserver = %s, mail_smtpport = %d, mail_smtpauth_req = 0, mail_smtpssl = 0,'
    . " mail_smtpuser = '', mail_smtppass = NULL, auth_type = 'no_auth',"
    . ' external_oauth_connection_id = NULL WHERE deleted = 0',
    $db->quoted($host),
    $port,
));

// No real mailbox is ever read from here.
$result = $db->query("UPDATE inbound_email SET status = 'Inactive' WHERE deleted = 0 AND status <> 'Inactive'");
$inactive = $db->getAffectedRowCount($result);

// Sender shown in the admin email settings and used by the notifications.
$administration = BeanFactory::newBean('Administration');
if (!$administration instanceof Administration) {
    fwrite(STDERR, "mail : module Administration introuvable\n");
    exit(1);
}
$administration->saveSetting('notify', 'fromaddress', $fromAddress);
$administration->saveSetting('notify', 'fromname', $fromName);

echo "  E-mails envoyés à $host:$port (expéditeur $fromAddress).\n";
if ($inactive > 0) {
    echo "  $inactive boîte(s) de réception désactivée(s).\n";
}
