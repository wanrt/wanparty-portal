<?php
include_once('auth/functions.inc.php');
$current = Machine::current();
$logged = false;
if ($current->isActive()) {
    $current->authorize();
    $logged = true;
}

if (!$logged) {
    header("Location: https://" . $_SERVER['SERVER_ADDR'] . "/auth/login.php");
}
if ($current->usertype == 'ORGA' &&
    isset($_GET['action']) && isset($_GET['ip'])) {
    $ip = htmlspecialchars($_GET['ip'], ENT_QUOTES, 'UTF-8');
    $action = htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8');
    if ($action == "ban") {
        Machine::ban($ip);
        $message = "Utilisateur $ip banni";
    }
    if ($action == "unban") {
        Machine::unban($ip);
        $message = "Utilisateur $ip n'est plus banni";
    }

}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="refresh" content="30">

    <title>WAN-RT</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        /* Set top padding of the page to match the height of the navbar */

        body {
            padding-top: 70px;
        }

        /* Ugly hacks to fix the container within navbar functionality - should be fixed in Bootstrap 4 beta */

        .navbar-toggler {
            z-index: 1;
        }

        @media (max-width: 576px) {
            nav > .container {
                width: 100%;
            }
        }
    </style>

</head>

<body>

<!-- Navigation -->
<nav class="navbar fixed-top navbar-toggleable-md navbar-inverse bg-inverse">
    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
            data-target="#navbarExample" aria-controls="navbarExample" aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="container">
        <a class="navbar-brand" href="#">WAN-RT</a>
        <div class="collapse navbar-collapse" id="navbarExample">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">Info </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/list.php">Liste des joueurs <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/auth/logout.php">Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container">
    <div class="row">
        <?php
        if ($message) {
            ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php } ?>
        <table class="table">
            <thead class="thead-inverse">
            <tr>
                <th>IP</th>
                <th>Joueur</th>
                <th>MAC</th>
                <th>Thpt</th>
                <th>Connecté depuis</th>
                <th>Type</th>
                <?php if ($current->usertype == 'ORGA') echo "<th>Action</th>"; ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $machines = Machine::active();

            foreach ($machines as $machine) {
                if ($current->usertype == 'ORGA') {
                    if ($machine->banned) echo "<tr  class='table-danger'>";
                    else echo '<tr>';
                    echo "<th scope='row'><a href='http://". $_SERVER['SERVER_ADDR'] .":3000/lua/host_details.lua?host=$machine->ip'>$machine->ip</a></th>";
                } else {
                    if ($machine->usertype == 'ORGA') continue;
                    if ($machine->banned) echo "<tr  class='table-danger'>";
                    else echo '<tr>';
                    echo "<th scope='row'>$machine->ip</th>";
                }


                echo "
        <td>$machine->user</td>
        <td>$machine->mac</td>
        <td>" . (($machine->ntop) ? $machine->ntop->thpt : "-") . "</td>
        <td>$machine->datetime</td>
        <td>$machine->usertype</td>";
                if ($current->usertype == 'ORGA') {
                    if ($machine->banned)
                        echo "<td><a href='list.php?action=unban&ip=$machine->ip' class='btn btn-success' role='button'>débannir</a></td>";
                    else
                        echo "<td><a href='list.php?action=ban&ip=$machine->ip' class='btn btn-danger' role='button'>Bannir</a></td>";
                }
                echo "</tr>";

            }
            ?>

            </tbody>
        </table>
    </div>
</div>

<script src=/js/jquery.min.js></script>
<script src=/js/bootstrap.min.js></script>


</body>

</html>
