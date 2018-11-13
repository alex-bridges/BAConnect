<?php
require_once "session.php";
require_once "database.php";
require_once "card.php";

//require_once "extras/zipUploader.php";
//uploadZips();

if(isset($_POST["action"]) && $_POST["action"] == "loadCards"){
    if(!isset($_POST["offset"])){
        $offset = 0;
    } else {
        $offset = $_POST["offset"];
    }

    if(!isset($_POST["search"])){
        $num = $_POST["num"];

        $con = Connection::connect();
        $stmt = $con->prepare("SELECT `account_ID` FROM Information LIMIT " . $num . " OFFSET " . $offset);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $con = null;
        echo json_encode($result);
        die();
    } else {
        $num = $_POST["num"];
        $search = Input::str($_POST["search"]);

        //$con = Connection::connect();
        //$stmt = $con->prepare("SELECT `account_ID` FROM UserAddressView where '%" . $search . "%' IN (`state`, `country`, `state_name`, `city`, `post_code`, `street_address`, `street_address2`, `first_name`, `middle_name`, `last_name`, `gender`, `facebook`, `linkedin`, `mentorship_preference`, `dob`, `phone_number`) LIMIT " . $num . " OFFSET " . $offset);
        //$stmt->execute();
        //$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //$con = null;

        $result = searchEntireDBFor($search);
        echo json_encode($result);
        die();
    }
}

if(isset($_POST["action"]) && $_POST["action"] == "openModal"){
    echo "<script>document.getElementById('" . $_POST["modal"] . "').style.display='block';</script>";
}

if (isset($_POST['register'])) {
    $error = false;
    $msg = "";

    $requiredPOSTFieldNames = array('password', 'confirmedPassword', 'username', 'firstName', 'lastName', 'email', 'gender', 'phoneNumber', 'status', 'preference', 'state', 'country', 'numDegs', 'numJobs');
    $optionalPOSTFieldNames = array('middleName', 'street', 'street2', 'city', 'postcode', 'facebook', 'twitter', 'linkedin');

    foreach ($requiredPOSTFieldNames as $req) {
        if (isset($_REQUEST[$req])) {
            $_SESSION[$req] = Input::str($_POST[$req]);
        } else {
            $error = true;
        }
    }

    foreach ($optionalPOSTFieldNames as $req) {
        $_SESSION[$req] = (isset($_REQUEST[$req]) ? $_SESSION[$req] = Input::str($_POST[$req]) : $_SESSION[$req] = "");
    }

    // Collect Education and Work Histories
    $degree = array();
    for ($degreeNum = 0; $degreeNum < $_SESSION['numDegs']; $degreeNum++) {
        foreach (array('schoolName_' . $degreeNum, 'degreeType_' . $degreeNum, 'major_' . $degreeNum, 'enrollmentYear_' . $degreeNum, 'gradYear_' . $degreeNum) as $req) {
            $_SESSION[$req] = (isset($_REQUEST[$req]) ? $_SESSION[$req] = Input::str($_POST[$req]) : $_SESSION[$req] = "");
        }
        if ($_SESSION['schoolName_' . $degreeNum] != "" && $_SESSION['major_' . $degreeNum] != "") {
            $degree[$degreeNum] = new EducationHistoryEntry($_SESSION['schoolName_' . $degreeNum], $_SESSION['degreeType_' . $degreeNum], $_SESSION['major_' . $degreeNum], $_SESSION['enrollmentYear_' . $degreeNum], $_SESSION['gradYear_' . $degreeNum]);
        }
    }

    $work = array();
    for ($jobNum = 0; $jobNum < $_SESSION['numJobs']; $jobNum++) {
        foreach (array('employerName_' . $degreeNum, 'jobTitle' . $degreeNum, 'startYear_' . $degreeNum, 'endYear_' . $degreeNum) as $req) {
            $_SESSION[$req] = (isset($_REQUEST[$req]) ? $_SESSION[$req] = Input::str($_POST[$req]) : $_SESSION[$req] = "");
        }
        if ($_SESSION['employerName_' . $degreeNum] != "" && $_SESSION['jobTitle' . $degreeNum] != "") {
            $work[$jobNum] = new WorkHistoryEntry($_SESSION['employerName_' . $degreeNum], $_SESSION['jobTitle' . $degreeNum], $_SESSION['startYear_' . $degreeNum], $_SESSION['endYear_' . $degreeNum]);
        }
    }

    // handle files
    $picturePath = $_FILES['profile']['tmp_name'];
    $resumePath = $_FILES['resume']['tmp_name'];

    // verify password
    if ($_SESSION['password'] != $_SESSION['confirmedPassword']) {
        $error = true;
        $msg .= "\nPasswords do not match.";
    } elseif (!(preg_match('/[A-Za-z]/', $_SESSION['password']) && preg_match('/[0-9]/', $_SESSION['password']))) {
        $error = true;
        $msg .= "\nPassword must contain a capital letter and a number.";
    } elseif (strlen($_SESSION['password']) < 12) {
        $error = true;
        $msg .= "\nPassword must be 12 or more characters.";
    }

    // verify email format and if we already have that email in the server
    $sanitized_email = Input::email($_SESSION['email']);
    if (!$sanitized_email) {
        $error = true;
        $msg .= "\nEmail is not a valid address.";
    }
    $_SESSION['email'] = $sanitized_email;

    $con = Connection::connect();
    $stmt = $con->prepare("select account_ID from Information where email_address = ?");
    $stmt->bindValue(1, $_SESSION['email'], PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row != null) {
        $error = true;
        $msg .= "\nAn account with this email already exists.";
    }
    $con = null;

    // check that username is unique
    $con = Connection::connect();
    $stmt = $con->prepare("select account_ID from Account where username = ?");
    $stmt->bindValue(1, $_SESSION['username'], PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row != null) {
        $error = true;
        $msg .= "\nAn account with this username already exists.";
    }
    $con = null;

    // check that gender is in range
    if (!is_numeric($_SESSION['gender']) || $_SESSION['gender'] > 2 || $_SESSION['gender'] < 0) {
        $error = true;
        $msg .= "\nInvalid gender.";
    }

    // check that status is in range
    if (!is_numeric($_SESSION['status']) || ($_SESSION['status'] != 0 && $_SESSION['status'] != 1)) {
        $error = true;
        $msg .= "\nInvalid status.";
    }

    // check that preference is in range
    if (!is_numeric($_SESSION['preference']) || $_SESSION['preference'] > 2 || $_SESSION['preference'] < 0) {
        $error = true;
        $msg .= "\nInvalid preference.";
    }

    // check that country is a number that correlates to a country in the db
    if (!is_numeric($_SESSION['country'])) {
        $error = true;
        $msg .= "\nInvalid country.";
    }
    $con = Connection::connect();
    $stmt = $con->prepare("select country from Countries where country_ID = ?");
    $stmt->bindValue(1, $_SESSION['country'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(empty($row)) {
        $error = true;
        $msg .= "\nInvalid country.";
    }
    $con = null;

    // check that state is a number that correlates to a state and a country in the db
    if (!is_numeric($_SESSION['state'])) {
        $error = true;
        $msg .= "\nInvalid state.";
    }
    $con = Connection::connect();
    $stmt = $con->prepare("select state from States where state_ID = ? and country_ID = ?");
    $stmt->bindValue(1, $_SESSION['state'], PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['country'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(empty($row)) {
        $error = true;
        $msg .= "\nInvalid state.";
    }
    $con = null;

    if ($error == false) {
        $user = new User($_SESSION['username'], $_SESSION['password'], $_SESSION['firstName'], $_SESSION['middleName'], $_SESSION['lastName'], $_SESSION['email'], $_SESSION['gender'], $_SESSION['phoneNumber'], $_SESSION['$status']);
        $address = new Address($_SESSION['street'], $_SESSION['street2'], $_SESSION['city'], $_SESSION['postcode'], $_SESSION['state'], $_SESSION['country']);
        registerUser($user, $address, $degree, $work, $picturePath, $resumePath);
        header("Location: created.php");
        die();
    } else {
        $_SESSION['title'] = "Error in Registration";
        $_SESSION['msg'] = $msg;
        $_SESSION['nextModal'] = 'registerModal';
        header("Location: index.php");
        die();
    }
}

if(isset($_POST['editSearch']) && isset($_POST['username'])) {

    if(!isset($type)){
        header("Location: index.php");
        die;
    }
    if($type <= 2){
        header("Location: index.php");
        die;
    }

    $con = Connection::connect();
    $stmt = $con->prepare("SELECT `account_ID` FROM Account WHERE username = ?");
    $stmt->bindValue(1, $_POST['username'], PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch();

    if ($row == null) {
        $_SESSION['title'] = "Error in Profile Search";
        $_SESSION['msg'] = "There is no user with the given username.";
        $_SESSION['nextModal'] = 'editModal';
        header("Location: index.php");
        die();
    }

    header("Location: profile.php?user=" . $row['account_ID']);
    die;
}

if(isset($_POST['upgrade'])){

    if(!isset($type)){
        header("Location: index.php");
        die;
    }
    if($type <= 2){
        header("Location: index.php");
        die;
    }

    $username = $_POST["username"];
    $newType = $_POST["type"];
    $id = getAccountIDFromUsername($username);

    $oldType = getAccountTypeFromAccountID($id);

    if($newType != $oldType && $_SESSION['account_ID'] != $id){

        if($oldType > $newType){
            if($type > $oldType){
                editAccountType($id, $newType);
            }
        }
        else{
            if($type > $newType){
                editAccountType($id, $newType);
            }
        }
    }
    header("Location: index.php");
    die;
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
    <script src="js/registration.js"></script>
    <script src="js/closeModals.js"></script>
    <script src="js/cardHandler.js"></script>
</head>

<body class="w3-light-grey" onload="init();">
<!-- Navbar -->
<?php include "header.php"; ?>
<!-- Page content -->
<div id="mentorDisplay" class="flex-container" style="display: flex; flex-wrap: wrap; justify-content: center; align-items: stretch; align-content: flex-start;">

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

    var offset = 0;

    function continuallyLoadCards(num = 10) {
        let xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                var array = JSON.parse(this.responseText);
                console.log(array);
                cardAjax(array);
            }
        };
        let params = "action=loadCards&num=" + num +"&offset=" + offset;
        xmlhttp.open("POST", "index.php", true);
        xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xmlhttp.send(params);
        offset += num;
    }

    continuallyLoadCards(30);

    function searchCards(num = 30, startOver = true) {
        if (startOver) {
            document.getElementById("mentorDisplay").innerHTML = "";
            offset = 0;
        }

        let term = document.getElementById("searchBox").value;
        if (term === "") {
            continuallyLoadCards(30);
            return;
        }

        let xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                var array = JSON.parse(this.responseText);
                console.log(array);
                cardAjax([...new Set(array)]);
            }
        };

        let params = "action=loadCards&num=" + num +"&offset=" + offset + "&search=" + term;
        xmlhttp.open("POST", "index.php", true);
        xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xmlhttp.send(params);
        offset += num;
    }

    window.onscroll = function(ev) {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight) {
            let term = document.getElementById("searchBox").value;
            if (term = "") {
                continuallyLoadCards(10);
            } else {
                searchCards(10, false);
            }
        }
    };
</script>
</html>
