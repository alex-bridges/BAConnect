<?php
require_once "session.php";
require_once "database.php";

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "rejectMentorship") {
    if ($type < 2) {
        header("location: index.php");
        die();
    }

    $pendingMentorship_ID = $_REQUEST['id'];
    $success = pendingMentorshipResponse($account_ID,$pendingMentorship_ID, -1 );
    if ($success) {
        echo formatPendingMentorships();
    }
    $report = new Report("Pending Mentorship Request Has Been Revoked Successfully", "Emails have been sent to both parties notifying them of the change.", "", TRUE);
    $_SESSION['report'] = $report;
    die();
}

function formatPendingMentorships() {
    $pendingMentorships = getPendingMentorships();
    $result = '<table id="pending_mentorship_history_table"><thead><tr><th>Mentor</th><th>Mentee</th><th>Approve Request</th><th>Delete Request</th></tr></thead><tbody>';
    foreach($pendingMentorships as $pen) {

        $id = $pen['pending_ID'];

		$decline = '<button name="decline" class="w3-button w3-red" onclick="handlePendingMentorship(\'' . $id . '\', 0);">Decline</button>';
        $mentorLink = '<a href="profile.php?user=' . $pen['mentor_ID'] . '">' . getName($pen['mentor_ID']) . '</a>';
        $menteeLink = '<a href="profile.php?user=' . $pen['mentee_ID'] . '">' . getName($pen['mentee_ID']) . '</a>';

        $result .= "<tr>";
        $result .= "<th><h6>" . $mentorLink . "</h6></th>";
        $result .= "<th><h6>" . $menteeLink . "</h6></th>";
        $result .= "<th><h6>" . $pen['request_date'] . "</h6></th>";
        $result .= "<th><h6>" . $decline . "</h6></th>";
        $result .= "</tr>";
    }

    $result .= '</tbody>';

    return $result;
}

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
        <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
        <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
        <script src="js/registration.js"></script>
        <script src="js/closeModals.js"></script>
        <script>
            function revokeMentorship(mentorship_ID) {
                let xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function(){
                    if(this.readyState == 4 && this.status == 200){
                        let table = $('#pending_mentorships');
                        table.DataTable().destroy();
                        document.getElementById("pending_mentorships").innerHTML = this.responseText;
                        table.DataTable();
                    }
                };

                xmlhttp.open("POST", "pendingmentorships.php?action=rejectMentorship&id=" + mentorship_ID, true);
                xmlhttp.send();
            }

            $(document).ready(function () {
                $('#pending_mentorships').DataTable();
            });

        </script>
    </head>

    <body class="w3-light-grey" onload="init();">
    <!-- Navbar -->
    <?php include "header.php"; ?>
    <!-- Page content -->
    <div class="w3-content" style="max-width:1400px;">
    <div class="w3-container w3-card w3-white w3-padding-large">
        <table id="pending_mentorships" class="display">
            <?php echo formatPendingMentorships() ?>
        </table>
    </div>
    </div>
    </body>
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
    </html>