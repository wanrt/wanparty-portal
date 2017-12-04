<?php 
include_once('auth/functions.inc.php');
$current = Machine::current();
$logedin = false;
// synchronize the db and shorewall
// if($current->isAuthorized()){
//     $current->activate();
//     $logedin = true;
// }
if($current->isActive()){
    $current->authorize();
    $logedin = true;
}



if(!$logedin){
		header( "Location: https://" . $_SERVER['SERVER_ADDR'] . "/auth/login.php");
}


?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

 
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
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarExample" aria-controls="navbarExample" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="container">
            <a class="navbar-brand" href="#">WAN-RT</a>
            <div class="collapse navbar-collapse" id="navbarExample">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="/index.php">Info <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/list.php">Liste des joueurs</a>
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
            <div class="col-lg-12 text-center">
                <h1 class="mt-5"><?php echo $current->ip; ?></h1>
                <p class="lead">Vous êtes connecté avec l'id : <?php echo $current->user; ?>. sur l'adresse mac : <?php echo $current->mac; ?></p>
                <ul class="list-unstyled">
                    <li>Depuis <?php echo $current->datetime; ?></li>
                </ul>
            </div>
        </div>
    </div>

 
<script src=/js/jquery.min.js></script>
<script src=/js/bootstrap.min.js></script>
</body>

</html>
