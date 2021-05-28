<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

//set predis
require "predis/autoload.php";
Predis\Autoloader::register();
try {
    $redis = new Predis\Client(array(
        "scheme" => "tcp",
        "host" => "localhost",//changer le nom de la base
        "port" => 6379
    ));
} catch (Exception $e) {
    die($e->getMessage());
}

//player informations
if (isset($_POST["userName"])) {
    $_SESSION["userName"] = $_POST["userName"];
    $redis->sadd("username", $_SESSION["userName"]);
    $redis->set($_POST["userName"], "0");
}

if (isset($_POST["wordToPlay"])) {
    if (!$redis->exists("wordToPlay")) {

        $redis->setnx("wordToPlay", $_POST["wordToPlay"]);
        $redis->expire("wordToPlay", 60);
        if ($redis->exists("propositions")) {
            $redis->del("propositions");
        }

        if ($redis->exists("nbError")) {
            $redis->del("nbError");
        }
        $redis->set("nbError", 0);
    }
}

if ($redis->exists("wordToPlay")) {
    $isComplet = false;

    if (isset($_POST["letterPropal"])) {
        if (!$redis->sismember("propositions", $_POST["letterPropal"])) {
            $redis->sadd("propositions", $_POST["letterPropal"]);

            $letterInWord = false;
            foreach (str_split($redis->get("wordToPlay"), 1) as $c) {
                if ($_POST["letterPropal"] == $c) {
                    $letterInWord = true;
                    break;
                }
            }

            if (!$letterInWord) {
                $redis->incr("nbError");
            }

            //as-t-il gagné ?
            foreach (str_split($redis->get("wordToPlay"), 1) as $c) {
                if ($redis->sismember("propositions", $c)) {
                    $isComplet = true;
                } else {
                    $isComplet = false;
                    break;
                }
            }
        }
    }

    if (isset($_POST["wordPropal"])) {
        if (!$redis->sismember("propositions", $_POST["wordPropal"])) {
            $redis->sadd("propositions", $_POST["wordPropal"]);

            //Si mot gagné
            if ($_POST["wordPropal"] == $redis->get("wordToPlay")) {
                $isComplet = true;
            } else {
                $redis->incr("nbError");
            }
        }
    }

    if ($isComplet) {
        //gagné
        $redis->incr($_SESSION["userName"]);

        if ($redis->exists("propositions")) {
            $redis->del("propositions");
        }

        if ($redis->exists("nbError")) {
            $redis->del("nbError");
        }

        $redis->del("wordToPlay");
    }

    if ($redis->get("nbError") >= 10) {
        $redis->del("wordToPlay");
    }
}


function getPartialWord($redis)
{
    $word = $redis->get("wordToPlay");
    $show = "";

    foreach (str_split($word, 1) as $c) {
        if ($redis->sismember("propositions", $c)) {
            $show .= $c . " ";
        } else {
            $show .= "_ ";
        }
    }
    return $show;
}

$players = $redis->smembers("username");

if ($redis->exists("propositions")) {
    $propal = $redis->smembers("propositions");
} else {
    $propal = array();
}
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Le PeNdU</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>
<body>

<nav class="navbar navbar-light bg-light">
    <span class="navbar-brand mb-0 h1">Le PeNdU</span>
    <span class="navbar-text">
      Bonjour <?php echo $_SESSION["userName"]; ?>, ton score est de <?php echo $redis->get($_SESSION["userName"]); ?> point(s) !
    </span>
</nav>

<div class="container">
    <div class="row">
        <div class="col-sm-3">
            <h2>Liste des joueurs</h2>
            <ul>
                <?php
                foreach ($players as $p) {
                    echo "<li>$p</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <?php if ($redis->exists("wordToPlay")) { ?>

        <div class="row">
            <div class="col-sm-6">
                <h2>Mot à trouver</h2>
                <span><?php echo getPartialWord($redis) ?></span>
            </div>
            <div class="col-sm-3">
                <h2>Propositions</h2>
                <ul>
                    <?php foreach ($propal as $p) {
                        echo "<li>$p</li>";
                    } ?>
                </ul>
            </div>
            <div class="col-sm-6">
                <h2>Temps restant</h2>
                <span><span id="ttl-word"><?php echo $redis->ttl("wordToPlay") ?></span> secondes</span>
            </div>
            <div class="col-sm-6">
                <h2>Nombre d'essais restant</h2>
                <span><?php echo 10 - $redis->get("nbError") ?> essais</span>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <h2>Proposer une lettre</h2>
                <form action="page.php" method="post">
                        <span>
                            <input type="text" min="1" max="1" size="3" name="letterPropal" required/>
                            <button type="submit">Valider</button>
                        </span>
                </form>
            </div>
            <div class="col-sm-6">
                <h2>Proposer un mot</h2>
                <form action="page.php" method="post">
                        <span>
                            <input type="text" min="1" size="20" name="wordPropal" required/>
                            <button type="submit">Valider</button>
                        </span>
                </form>
            </div>
        </div>


    <?php } else { ?>

        <div class="row">
            <div class="col-sm-6">
                <h2>Proposer un mot </h2>
                <form action="page.php" method="post">
                        <span>
                            <input type="text" size="30" name="wordToPlay"/>
                            <button type="submit">Valider</button>
                        </span>
                </form>
            </div>
        </div>

    <?php } ?>
</div>
<script>
    setInterval(() => {
        let timeMax = parseInt(document.getElementById("ttl-word").innerText, 10);
        document.getElementById("ttl-word").innerText = (--timeMax).toString();
        if (timeMax === 0) {
            location.reload();
        }
    }, 1000);
</script>
</body>
</html>