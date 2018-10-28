<?php
require_once "session.php";
require_once "database.php";
require_once "card.php";
include "extras/fakegen.php";
?>
<!-- template from: https://www.w3schools.com/w3css/w3css_templates.asp -->
<!DOCTYPE html>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAConnect Home</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="js/registration.js"></script>
    <script src="js/closeModals.js"></script>
    <script>
        // Used to toggle the menu on small screens when clicking on the menu button
        function toggleNav() {
            let x = document.getElementById("navMobile");
            if (x.className.indexOf("w3-show") == -1) {
                x.className += " w3-show";
            } else {
                x.className = x.className.replace(" w3-show", "");
            }
        }
    </script>
</head>

<body class="w3-light-grey" onload="init();">
<!-- Navbar -->
<?php include "header.php"; ?>
<!-- Page content -->
<div id="mentorDisplay">
    <?php
    $con = Connection::connect();
    $stmt = $con->prepare("SELECT `account_ID` FROM Information limit 90");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $user) {
        $card = createCard($user["account_ID"]);
        echo '<span class="w3-container" style="display: inline-block; text-align: center; vertical-align: middle;">' . $card . '</span>';
    }
    $con = null;
    ?>
</div>
<!-- modals -->
<?php

if ($type == 0) {
    include "login.php";
    include "register.php";
    include "forgot.php";
}

if ($type > 1) {
    include "match.php";
    include "edit.php";
    include "upgrade.php";
    include "search.php";
    include "addCountry.php";
    include "addDegreeType.php";
    include "addState.php";
}
?>
</body>
</html>
