<?php
require_once "session.php";
require_once "database.php";

if (isset($_POST['submit'])) {

    $image_dir = 'images';
    $image_dir_path = getcwd() . DIRECTORY_SEPARATOR . $image_dir;

    $file_name = $_FILES['profile']['name'];
    $file_size = $_FILES['profile']['size'];
    $file_tmp = $_FILES['profile']['tmp_name'];
    $file_type = $_FILES['profile']['type'];
    $file_ext=strtolower(end(explode('.',$_FILES['profile']['name'])));

    $target = $image_dir_path . DIRECTORY_SEPARATOR . $file_name;
    move_uploaded_file($file_tmp, $target);

    registerNewPicture($_SESSION["account_ID"], $target);
    header("location: profile.php");
}

if (isset($_GET['user'])) {
    $account_id = $_GET['user'];
    $allowEdit = FALSE;
} elseif (isset($_SESSION["account_ID"])) {
    $account_id = $_SESSION["account_ID"];
    $allowEdit = TRUE;
} else {
    header("location: index.php");
}

function formatDegrees($degrees) {
    $result = "";
    foreach($degrees as $degree) {
        $result .= '<div class="w3-container"><h5 class="w3-opacity"><b>';
        $result .= $degree[1] . " / " . $degree[0];
        $result .= '</b></h5><h6 class="w3-text-lime"><i class="fa fa-calendar fa-fw w3-margin-right"></i>';
        $result .= $degree[3] . " - " . $degree[2];
        $result .= '</h6><hr></div>';
    }
    return $result;
}

function formatJobs($jobs) {
    $result = "";
    foreach ($jobs as $job) {
        $result .= '<div class="w3-container"><h5 class="w3-opacity"><b>';
        $result .= $job[1] . " / " . $job[0];
        $result .= '</b></h5><h6 class="w3-text-lime"><i class="fa fa-calendar fa-fw w3-margin-right"></i>';
        $result .= $job[2] . " - " . $job[3];
        $result .= '</h6><hr></div>';
    }
    return $result;
}
?>
<!-- template from: https://www.w3schools.com/w3css/w3css_templates.asp -->
<!DOCTYPE html>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAConnect Profile</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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

<div class="w3-content" style="max-width:1400px;">

    <!-- The Grid -->
    <div class="w3-row-padding">

        <!-- Left Column -->
        <div class="w3-third">

            <div class="w3-white w3-text-grey w3-card-4">
                <div class="w3-display-container">
                    <img src="data:image/jpeg;base64,<?php echo file_get_contents("http://corsair.cs.iupui.edu:22891/courseproject/image.php?account_id=" . $account_id); ?>" style="width:100%;" alt="Avatar">
                    <div class="w3-display-middle w3-display-hover w3-xlarge">
                        <?php if ($allowEdit) { echo "<button class=\"w3-button w3-black\" onclick=\"document.getElementById('uploadPicModal').style.display='block'\">Change Picture...</button>";} ?>
                    </div>
                    <div class="w3-display-bottomleft w3-container w3-text-black">
                        <h2 class="w3-text-white" style="text-shadow:1px 1px 0 #444"><?php echo getName($account_id) ?></h2>
                    </div>
                </div>
                <div class="w3-container">
                    <p><i class="fa fa-user fa-fw w3-margin-right w3-large w3-text-lime"></i><?php echo getGender($account_id) ?></p>
                    <p><i class="fa fa-briefcase fa-fw w3-margin-right w3-large w3-text-lime"></i><?php echo getStatus($account_id) ?></p>
                    <p><i class="fa fa-home fa-fw w3-margin-right w3-large w3-text-lime"></i><?php echo getApproximateLocation($account_id) ?></p>
                    <p><i class="fa fa-envelope fa-fw w3-margin-right w3-large w3-text-lime"></i><?php echo getEmail($account_id) ?></p>
                    <p><i class="fa fa-phone fa-fw w3-margin-right w3-large w3-text-lime"></i><?php echo getPhoneNumber($account_id) ?></p>
                    <hr>

                    <button class="w3-button w3-block w3-dark-grey">+ Connect</button>
                    <br>
                </div>
            </div><br>

            <!-- End Left Column -->
        </div>

        <!-- Right Column -->
        <div class="w3-twothird">

            <div class="w3-container w3-card w3-white w3-margin-bottom">
                <h2 class="w3-text-grey w3-padding-16"><i class="fa fa-suitcase fa-fw w3-margin-right w3-xxlarge w3-text-lime"></i>Work Experience</h2>
                <?php echo formatJobs(getJobs($account_id)); ?>
            </div>

            <div class="w3-container w3-card w3-white">
                <h2 class="w3-text-grey w3-padding-16"><i class="fa fa-certificate fa-fw w3-margin-right w3-xxlarge w3-text-lime"></i>Education</h2>
                <?php echo formatDegrees(getDegrees($account_id)); ?>
            </div>

            <!-- End Right Column -->
        </div>

        <!-- End Grid -->
    </div>

<?php if ($allowEdit) { echo "
<div id=\"uploadPicModal\" class=\"w3-modal\">
        <div class=\"w3-modal-content w3-animate-top w3-card-4\">
            <header class=\"w3-container w3-lime w3-center w3-padding-32\">
            <span onclick=\"document.getElementById('uploadPicModal').style.display='none'\"
                  class=\"w3-button w3-lime w3-xlarge w3-display-topright\">×</span>
                <h2 class=\"w3-wide\"><i class=\"w3-margin-right\"></i>Change Profile Picture </h2>
            </header>
            <form method=\"post\" action=\"profile.php\" enctype='multipart/form-data' class=\"w3-container\">
                <p>
                    <label>
                        <i class=\"fa fa-user\"></i> New picture:
                    </label>
                </p>
                <input class=\"w3-input w3-border\" type=\"file\" placeholder=\"\" name=\"profile\" id=\"profile\" accept=\"image/png, image/jpeg\">
                <button class=\"w3-button w3-block w3-lime w3-padding-16 w3-section w3-right\" type=\"submit\" name=\"submit\">
                    Submit New Photo
                    <i class=\"fa fa-check\"></i>
                </button>
                <button type=\"button\" class=\"w3-button w3-red w3-section\"
                        onclick=\"document.getElementById('uploadPicModal').style.display='none'\">Close
                    <i class=\"fa fa-remove\"></i>
                </button>
            </form>
        </div>
    </div>";} ?>

    <!-- End Page Container -->
</div>
<!-- End Page Content -->
</body>
</html>