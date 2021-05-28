<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION["userName"])) { ?>
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <h2>Connectez-vous</h2>
                <form method="post" action="page.php">
            <span>
                <input type="text" name="userName" size="100"/>
                <button type="submit">Se connecter</button>
            </span>
                </form>
            </div>
        </div>
    </div>
<?php } else {
    include("page.php");
} ?>