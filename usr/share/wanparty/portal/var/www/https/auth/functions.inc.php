<?php
$DB = '/var/www/data/auth.db';
// CREATE TABLE `machines` (
//   `id`  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
//   `mac` TEXT NOT NULL,
//   `ip`  TEXT NOT NULL,
//   `user`  TEXT NOT NULL,
//   `usertype`  TEXT,
//   `active`  INTEGER DEFAULT 1,
//   `banned`  INTEGER DEFAULT 1,
//   `datetime`  TEXT,
//   `extrafiled`  TEXT,
//   `extravalue`  TEXT
// );

/**
 *
 */
class Machine
{
    public $ip, $mac, $user, $usertype, $datetime;
    public $id = -1;

    function __construct($p_ip, $p_mac)
    {
        $this->ip = $p_ip;
        $this->mac = $p_mac;
    }

    public static function create($ip, $mac, $user, $usertype)
    {
        openlog("PORTAL ", LOG_PID | LOG_PERROR | LOG_NDELAY, LOG_LOCAL2);
        $access = date("d/m/Y H:i:s");
        syslog(LOG_INFO, "Add machine {$ip}:{$mac}:{$user} : $access ");
        closelog();

        $instance = new self($ip, $mac);
        $instance->user = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        $instance->usertype = $usertype;
        $instance->activate();
        $instance->authorize();
        return $instance;
    }

    public static function current()
    {
        $instance = new self(getIP(), getMAC());
        return $instance;
    }

    public function save()
    {
        global $DB;
        $query = "INSERT INTO machines (mac, ip, user, usertype, active, banned, datetime) VALUES('$this->mac', '$this->ip', '$this->user' , '$this->usertype', 1, 0, '" . date('c') . "');";
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $insert = $db->exec($query);
        $this->id = $db->lastInsertRowID();
        $db->close();
        return $insert;
    }

    private function isSaved()
    {
        global $DB;
        $query = "SELECT * FROM machines WHERE mac='$this->mac' AND ip='$this->ip';";
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $machine = $db->querySingle($query, true);
        $db->close();
        if ($machine && !empty($machine)) {
            // $this->id = $machine['id'];
            // $this->user = $machine['user'];
            // $this->usertype = $machine['usertype'];
            // $this->datetime = $machine['datetime'];
            return $machine;
        }
        return false;
    }

    public function isActive()
    {
        global $DB;
        $query = "SELECT * FROM machines WHERE mac='$this->mac' AND ip='$this->ip' AND active=1;";
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $machine = $db->querySingle($query, true);
        $db->close();
        if ($machine && !empty($machine)) {
            $this->id = $machine['id'];
            $this->user = $machine['user'];
            $this->usertype = $machine['usertype'];
            $this->datetime = $machine['datetime'];
            return $machine;
        }
        return false;
    }

    public function activate()
    {
        $saved = $this->isSaved();
        if (($saved && $saved['active'] == 1)) return true;
        if (!($saved && $saved['user'] == $this->user)) {
            $saved = $this->save();
            $saved = $this->isSaved();
        }
        global $DB;
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);

        // disable other auths using the same machine
        $queryDisable = "UPDATE machines SET active=0 WHERE ip='$this->ip';";
        $db->exec($queryDisable);

        $query = "UPDATE machines SET active = 1 WHERE id=$saved[id];";
        $update = $db->exec($query);
        $db->close();
        return $update;
    }

    public function disable()
    {
        openlog("PORTAL ", LOG_PID | LOG_PERROR | LOG_NDELAY, LOG_LOCAL2);
        $access = date("d/m/Y H:i:s");
        syslog(LOG_INFO, "Remove machine {$this->ip}:{$this->mac} : $access ");
        closelog();
        $active = $this->isActive();
        if ($active) {
            global $DB;
            $query = "UPDATE machines SET active=0 WHERE ip='$this->ip';";
            $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
            $update = $db->exec($query);
            return $update;
        }
        return $this->isSaved();
    }

    public static function ban($ip)
    {
        openlog("PORTAL ", LOG_PID | LOG_PERROR | LOG_NDELAY, LOG_LOCAL2);
        $access = date("d/m/Y H:i:s");
        syslog(LOG_INFO, "Ban machine $ip : $access ");
        closelog();
        global $DB;
        $query = "UPDATE machines SET banned=1 WHERE ip='$ip';";
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $update = $db->exec($query);
        $output = array();
        exec("sudo shorewall drop " . $ip, $output);
        sleep(5);
        return $update;
    }

    public static function unban($ip)
    {
        openlog("PORTAL ", LOG_PID | LOG_PERROR | LOG_NDELAY, LOG_LOCAL2);
        $access = date("d/m/Y H:i:s");
        syslog(LOG_INFO, "Unban machine $ip : $access ");
        closelog();
        global $DB;
        $query = "UPDATE machines SET banned=0 WHERE ip='$ip';";
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $update = $db->exec($query);
        $output = array();
        exec("sudo shorewall allow " . $ip, $output);
        sleep(5);
        return $update;
    }

    public function authorize()
    {
        $output = array();
        $zone = getZone();
        exec("sudo shorewall add $zone " . $this->ip, $output);
        sleep(5);
        foreach ($output as $line) {
            if (strpos($line, 'added to zone') !== false) {
                return true;
            }
            if (strpos($line, 'already added') !== false) {
                return true;
            }
        }
        return false;
    }

    public function deny()
    {
        $output = array();
        $zone = getZone();
        exec("sudo shorewall delete $zone " . $this->ip, $output);
        sleep(3);
        return true;
    }

    public function isAuthorized()
    {
        $zone = getZone();
        $output = array();
        exec("sudo shorewall show dynamic $zone ", $output);
        foreach ($output as $line) {
            if (preg_replace('/\s+/', '', $line) == $this->ip) {
                return true;
            }
        }
        return false;
    }

    public static function ntopMachines()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://127.0.0.1:3000/lua/wanparty.lua?mode=local&version=4",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array("cookie: user=nologin"),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $ntop_machines = array();
        if ($err) {
            return $ntop_machines;
        } else {
            $data = json_decode($response)->data;
            foreach ($data as $host) {
                $ntop_machines[$host->ip] = $host;
            }
        }
        return $ntop_machines;
    }

    public static function active()
    {
        $ntop_machines = Machine::ntopMachines();
        $machines = array();
        $query = "SELECT * FROM machines WHERE  active=1;";
        global $DB;
        $db = new SQLite3($DB, SQLITE3_OPEN_READWRITE);
        $dbmachines = $db->query($query);

        while ($dbmachine = $dbmachines->fetchArray()) {
            $machine = new self($dbmachine['ip'], $dbmachine['mac']);
            $machine->user = $dbmachine['user'];
            $machine->usertype = $dbmachine['usertype'];
            $machine->datetime = $dbmachine['datetime'];
            $machine->id = $dbmachine['id'];
            $machine->banned = $dbmachine['banned'];
            $machine->ntop = (isset($ntop_machines[$dbmachine['ip']]))?$ntop_machines[$dbmachine['ip']]:null;
            $machines[] = $machine;
        }
        $db->close();

        return $machines;
    }
}


function getZone()
{
    $zone = "web_eth1";
    // to be changed with the value set in shorewall config
    return $zone;
}

function getIP()
{
    return $_SERVER['REMOTE_ADDR'];
}

function getMAC($ip = '')
{
    if ($ip == '') $ip = getIP();
    @system('ping -W 1 -c 1 ' . $ip . ' > /dev/null 2>&1');
    $lines = array();
    $arp = exec("/usr/sbin/arp -a $ip", $lines);
    foreach ($lines as $line) {
        $cols = preg_split('/\s+/', trim($line));
        if ($cols[1] == "($ip)")
            return $cols[3];
    }
    return false;
}


// return array() if ok, else an error string
function ldapConnection($user, $pwd)
{
    global $connex;
    // test en local, sinon central, sinon directement

    //$res = ldapConnAnno("127.0.0.1");
    //if($res !== true)
    $res = ldapConnAnno("ldaps://10.200.0.1");
    if ($res !== true)
        $res = ldapConnAnno("ldaps://wanparty.neticien.net");
    if ($res !== true)
        return "Problème d'accès au service d'authentification !!!!";

    //$ldap_user = "cn={$_POST['umail']},ou=gamers,dc=wanparty,dc=neticien,dc=net";
    $f = "(|(cn=$user)(uid=$user)(mail={$user}))"; // le filtre : uid='login'
    $a = array('dn', 'uid', 'mail', 'employeeType');
    $users = ldap_get_entries($connex, ldap_search($connex, 'dc=wanparty,dc=neticien,dc=net', $f, $a));
    //ldap_unbind($connex);

    $ldapres = print_r($users, true);

    //return print_r($users[0]['dn'],true);
    if (!isset($users[0]['dn']))
        return "Identification erronée (Invalid credentials) !";
    $user = $users[0];

    if (strpos($users[0]['dn'], 'ou=z-gamers,ou=people,dc=wanparty,dc=neticien,dc=net') === false)
        $user['employeetype'][0] = 'ORGA';

    $res = ldapBind($user['dn'], $pwd);
    if ($res === true) {
        if ($user['employeetype'][0] !== 'ORGA' && $user['employeetype'][0] !== 'OK') // etudiant ?
            return "Inscription non validée ( {$user['uid'][0]} : {$user['employeetype'][0]} ) !";
        return $user;
    } else if ($res === 49) {
        return "Identification erronée (Invalid credentials) !";
        //return "Identification erronée (Invalid credentials) ! {$user['dn']}";
    }

    return "Problème d'accès au service d'authentification !!!!";
}

function ldapConnAnno($server)
{
    global $connex;
    // Connexion au serveur ldap
    $connex = ldap_connect($server);
    if (!$connex)
        return -2;
    ldap_set_option($connex, LDAP_OPT_PROTOCOL_VERSION, 3);
    // Initialisation de la liaison annonyme
    $bind = ldap_bind($connex);
    if (!$bind) { // -1=Can't contact LDAP server , 49=Invalid credentials
        return ldap_errno($connex);
    }
    // ok : ferme connexion ldap
    //ldap_unbind($connex);
    return true;
}


function ldapBind($user, $pwd)
{
    global $connex;
    // Initialisation de la liaison pour le user
    $bind = ldap_bind($connex, $user, $pwd);
    if (!$bind) // -1=Can't contact LDAP server , 49=Invalid credentials
        return ldap_errno($connex);
    // ok : ferme connexion ldap
    ldap_unbind($connex);
    return true;
}

?>