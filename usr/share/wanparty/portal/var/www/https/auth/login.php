<?php
include_once('functions.inc.php');
$current = Machine::current();
$logged = false;
if ($current->isActive()) {
    $logged = true;
}

$strerror = "";
if (!$logged && isset($_POST['umail']) && isset($_POST['upass']) && strlen($_POST['umail']) > 3 && strlen($_POST['umail']) < 50 && strlen($_POST['upass']) > 1 && strlen($_POST['upass']) < 25) {
    //$ldap_user = "cn={$_POST['umail']},ou=gamers,dc=wanparty,dc=neticien,dc=net";
    $ldap_user = "{$_POST['umail']}";
    $ldap_pass = $_POST['upass'];
    $ret = ldapConnection($ldap_user, $ldap_pass);
    if (is_array($ret)) {
        $user = $ret['uid'][0];
        $type = $ret['employeetype'][0];
        $machine = Machine::create(getIP(), getMAC(), $user, $type);
    } else {
        $strerror = "Erreur LDAP";
    }

} else if (isset($_POST['umail'])) {
    $strerror = "Entrez des identifiants valides !";
}

$current = Machine::current();
if ($current->isActive()) {
    $current->authorize(); // in case! good To synchronize the two catalogs
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <title>WAN-RT</title>
        <script language="JavaScript">
            document.location = "https://<?php echo $_SERVER['SERVER_ADDR']; ?>"
        </script>
    </head>
    <body>
    <h2>
        <center><a href="https://<?php echo $_SERVER['SERVER_ADDR']; ?>">Portail WAN-RT</a></center>
    </h2>
    </body>
    </html>
    <?php
}
?>


<html>
<head>
    <meta http-equiv="content-language" content="fr"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>WAN-RT : authentification</title>

    <style>

        .login-page {
            width: 360px;
            padding: 8% 0 0;
            margin: auto;
        }

        .form {
            position: relative;
            z-index: 1;
            background: #FFFFFF;
            max-width: 360px;
            margin: 0 auto 100px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
        }

        .form input {
            font-family: sans-serif;
            outline: 0;
            background: #f2f2f2;
            width: 100%;
            border: 0;
            margin: 0 0 15px;
            padding: 15px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form button {
            font-family: sans-serif;
            text-transform: uppercase;
            outline: 0;
            background: orange;
            width: 100%;
            border: 0;
            padding: 15px;
            color: #FFFFFF;
            font-size: 14px;
            -webkit-transition: all 0.3 ease;
            transition: all 0.3 ease;
            cursor: pointer;
        }

        .form button:hover, .form button:active, .form button:focus {
            background: #43A047;
        }

        .form .message {
            margin: 15px 0 0;
            color: #b3b3b3;
            font-size: 12px;
        }

        .form .message a {
            color: #4CAF50;
            text-decoration: none;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 300px;
            margin: 0 auto;
        }

        .container:before, .container:after {
            content: "";
            display: block;
            clear: both;
        }

        .container .info {
            margin: 50px auto;
            text-align: center;
        }

        .container .info h1 {
            margin: 0 0 15px;
            padding: 0;
            font-size: 36px;
            font-weight: 300;
            color: #1a1a1a;
        }

        .container .info span {
            color: #4d4d4d;
            font-size: 12px;
        }

        .container .info span a {
            color: #000000;
            text-decoration: none;
        }

        .container .info span .fa {
            color: #EF3B3A;
        }

        body {
            background: orange;
            font-family: sans-serif;
            -webkit-font-smoothing: antialiased;
        }
    </style>

</head>
<body>
<div class="login-page">
    <div class="form">

        <form class="login-form" method="post" action="login.php">
            <input type="text" name='umail' id="umail" placeholder="Pseudo d'inscription"/>
            <input type="password" name='upass' id='upass' placeholder="Mot de passe"/>
            <button>Valider</button>
            <p class="message"><b><?php echo $strerror; ?> </b> <br/>Si vous avez oublié votre mot de passe :<br/>demandez
                aux organisateurs l'accès à une machine ayant un accès suffisant<br/> pour le changer sur le site
                d'inscription puis consulter votre messagerie !</p>
        </form>
    </div>
</div>
</body>
</html>