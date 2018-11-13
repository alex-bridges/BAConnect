<?php

require_once "session.php";

function getUserMentorshipPreference($account_id){
  $con = Connection::connect();
  $stmt = $con->prepare("SELECT mentorship_preference FROM `Information` WHERE account_ID = '" . $account_id . "'");
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $preference = $result['mentorship_preference'];

  $con = null;
  $stmt = null;
  $result = null;

  if ($preference == 0) {
      return "Mentor";
  } elseif ($preference == 1) {
      return "Mentee";
  } else {
      return "Not Interested";
  }
}


/* NOTE: Pending mentorship functions section*/


function getPendingMentorships($account_id = null){
  $con = Connection::connect();

  if($account_id != null){
      $stmt = $con->prepare("SELECT * FROM `Pending Mentorship` WHERE mentor_ID = ? OR mentee_id = ?");
      $stmt->bindValue(1, $account_id, PDO::PARAM_INT);
      $stmt->bindValue(2, $account_id, PDO::PARAM_INT);
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
      $stmt = $con->prepare("SELECT * FROM `Pending Mentorship`");
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  $con = null;
  return $list;
}

function pendingMentorshipResponse($account_id, $pending_id, $response){
    //$mentee;
    $con = Connection::connect();

    $stmt = $con->prepare("SELECT * FROM `Pending Mentorship` WHERE pending_ID = '" . $pending_id . "'");
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result == null){
        return false;
    }

    if($account_id == $result['mentor_ID']){
        if($response == 1){
            if($result['mentor_status'] == "1"){
                return TRUE; //this user has already approved of this relationghip, so nothing happens.
            }
            else{
                $stmt = $con->prepare("UPDATE `Pending Mentorship` SET mentor_status = 1 WHERE pending_ID = '" . $pending_id . "'");
                $stmt->execute();
                if($result['mentee_status'] == "1"){
                    resolvePendingMentorship($result['mentor_ID'], $result['mentee_ID'], $account_id);
                }
                return TRUE;
            }
        }
        else{
            resolvePendingMentorship($result['mentor_ID'], $result['mentee_ID'], $account_id);
            return TRUE;
        }
    }
    else if($account_id == $result['mentee_ID']){
        if($response == 1){
            if($result['mentee_status'] == "1"){
                return TRUE; //this user has already approved of this relationghip, so nothing happens.
            }
            else{
                $stmt = $con->prepare("UPDATE `Pending Mentorship` SET mentee_status = 1 WHERE pending_ID = '" . $pending_id . "'");
                $stmt->execute();
                if($result['mentor_status'] == "1"){
                    resolvePendingMentorship($result['mentor_ID'], $result['mentee_ID'], $account_id);
                }
                return TRUE;
            }
        }
        else{
            resolvePendingMentorship($result['mentor_ID'], $result['mentee_ID'], $account_id);
            return TRUE;
        }
    }
    else{

        if(getAccountTypeFromAccountID($account_id) == "1"){
            //the current user isn't the mentee, mentor, or an admin, so they shouldn't be here.
            return FALSE;
        }
        else{
            resolvePendingMentorship($result['mentor_ID'], $result['mentee_ID'], $account_id);
        }
    }
}
//this function will create an entry in the Mentorship table based on the info in the related entry
//in the Pending Mentorship table. It will then delete the entry in the Pending Mentorship table.

//NOTE:this function should only be called after either both mentor and mentee have approved the pending
//mentorship, or if either of them or an admin rejected it.
//NOTE: duplicate pending mentorships (ones with the same mentor and mentee) cannot be allowed.
function resolvePendingMentorship($mentorID, $menteeID, $userID){
    $con = Connection::connect();

    //first get the information from the entry in Pending Mentorship
    $stmt = $con->prepare("SELECT * FROM `Pending Mentorship` WHERE mentor_ID = '" . $mentorID . "' AND mentee_ID = '" . $menteeID . "'");
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result == NULL){
        return false;
    }

    $stmt = $con->prepare("INSERT INTO `Mentorship` (mentorship_ID, mentor_ID, mentee_ID, start, end, terminator_ID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, null , PDO::PARAM_NULL);
    $stmt->bindValue(2, $mentorID, PDO::PARAM_INT);
    $stmt->bindValue(3, $menteeID, PDO::PARAM_INT);

    //now start building the rest of the INSERT command piece-meal depending on the Information
    //from the Pending Mentorship entry

    /*
    if($result['mentee_status'] == "-1"){   //the mentee rejected the proposal
        $stmt->bindValue(4, NULL, PDO::PARAM_NULL);
        $stmt->bindValue(5, NULL, PDO::CURRENT_TIMESTAMP);
    }
    else if($result['mentor_status'] == "-1"){ //the mentor rejected the proposal
        $stmt->bindValue(4, NULL, PDO::PARAM_NULL);
        $stmt->bindValue(5, NULL, PDO::CURRENT_TIMESTAMP);
    }
    else*/ if($result['mentee_status'] == "1" && $result['mentor_status'] == "1"){ //the proposal was accepted by both the mentor and mentee
        $stmt->bindValue(4, null, PDO::CURRENT_TIMESTAMP);
        $stmt->bindValue(5, NULL, PDO::PARAM_NULL);
        $stmt->bindValue(6, NULL, PDO::PARAM_NULL);

        //Do we send an email here?

    }
    /*else{   //the proposal was rejected by an admin
        $stmt->bindValue(4, NULL, PDO::PARAM_NULL);
        $stmt->bindValue(5, NULL, PDO::CURRENT_TIMESTAMP);
    }
    */
    else{ //the proposal was denied
        $stmt->bindValue(4, NULL, PDO::PARAM_NULL);
        $stmt->bindValue(5, NULL, PDO::CURRENT_TIMESTAMP);
        $stmt->bindValue(6, $userID, PDO::PARAM_INT);
    }

    $stmt->execute();

    //now delete the entry from the Pending Mentorship table
    $stmt = $con->prepare("DELETE FROM `Pending Mentorship` WHERE mentor_ID = '" . $mentorID . "' AND mentee_ID = '" . $menteeID . "'");
    $stmt->execute();

}


/* NOTE: Active mentorship functions section */


//a current mentorship has a set 'start' date, but it's 'end' date is null
function getCurrentMentorships($account_id = null){
    $con = Connection::connect();

    if($account_id != null){
        $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE (mentor_ID = ? OR mentee_id = ?) AND isnull(end) AND !isnull(start)");
        $stmt->bindValue(1, $account_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE isnull(end) AND !isnull(start)");
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $con = null;
    return $list;
}

function fetchMenteeID($mentorship_ID){
	$con = Connection::connect();
	$stmt = $con->prepare("select * from Mentorship where mentorship_ID = '" .$mentorship_ID. "'");
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if($row == null){
		return "MISSING INFORMATION!";
	}
	$con = null;
	return $row['mentee_ID'];
}//jonathan

function fetchMentorID($mentorship_ID){
	$con = Connection::connect();
	$stmt = $con->prepare("select * from Mentorship where mentorship_ID = '" .$mentorship_ID. "'");
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if($row == null){
		return "MISSING INFORMATION!";
	}
	$con = null;
	return $row['mentor_ID'];
}//jonathan

function forceEndMentorship($account_id,$targetMentorshipID){
	$accountType=getAccountTypeFromAccountID($account_id);
	if($accountType >= 2){
		$con = Connection::connect();
		$date = new Datetime('NOW');
		$dateStr = $date->format('Y-m-d H:i:s');//end date
		$menteeID=fetchMenteeID($targetMentorshipID);
		$mentorID=fetchMentorID($targetMentorshipID);
		$menteeEmail = getEmail($menteeID);
        $mentorEmail = getEmail($mentorID);
		$menteeName=getName($menteeID);
		$mentorName=getName($mentorID);
		mail($menteeEmail, "BAConnect: Mentorship", "Congradulations! You have completed your mentorship with ".$mentorName.".");
		mail($mentorEmail, "BAConnect: Mentorship", "Congradulations! You have completed your mentorship with ".$menteeEmail.".");
		$stmt = $con->prepare("UPDATE `Mentorship` SET end = '".$dateStr."' WHERE mentorship_ID = '" . $targetMentorshipID. "'");
		$stmt->execute();
		$con = null;
	}
}//jonathan


/* NOTE: Inactive mentorship functions section*/


//a mentorship that was rejected while it was still pending will have it's 'end' date set to the date it was rejected,
//but it's 'start' date will be left null. A user won't see a mentorship where they were rejected.
function getRejectedMentorships($account_id = null){
    $con = Connection::connect();

    if($account_id != null){
      $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE (mentor_ID = ? OR mentee_id = ?) AND isnull(start) AND !isnull(end)");
        $stmt->bindValue(1, $account_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $account_id, PDO::PARAM_INT);
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE isnull(start) AND !isnull(end)");
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $con = null;
    return $list;
}
//a mentorship that was started but later ended will have NEITHER it's 'start' nor 'end' dates set to null
function getEndedMentorships($account_id){
    $con = Connection::connect();

    if($account_id != null){
      $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE (mentor_ID = ? OR mentee_id = ?) AND !isnull(start) AND !isnull(end)");
        $stmt->bindValue(1, $account_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $account_id, PDO::PARAM_INT);
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $stmt = $con->prepare("SELECT * FROM `Mentorship` WHERE !isnull(start) AND !isnull(end)");
      $stmt->execute();
      $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $con = null;
    return $list;
}

/* NOTE: misc mentorship functions */


function proposeMentorship($mentorID, $menteeID, $proposerID){
    if($mentorID == $menteeID){
        return false;
    }
    $mentorPreference = getUserMentorshipPreference($mentorID);
    $menteePreference = getUserMentorshipPreference($menteeID);
    if($mentorPreference != "Mentor" || $menteePreference != "Mentee"){
        if($mentorPreference == "Mentee" || $menteePreference == "Mentor"){
            $temp = $mentorID;
            $mentorID = $menteeID;
            $menteeID = $temp;
        }
        else{
            return false;
        }
    }

    $con = Connection::connect();

    $stmt = $con->prepare("SELECT * FROM `Pending Mentorship` WHERE mentor_ID = '" . $mentorID . "' AND mentee_ID = '" . $menteeID . "'");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($result != null){
        return true;
    }

    $stmt = $con->prepare("INSERT INTO `Pending Mentorship` (pending_ID, mentor_ID, mentee_ID, mentor_status, mentee_status, request_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, null , PDO::PARAM_NULL);
    $stmt->bindValue(2, $mentorID, PDO::PARAM_INT);
    $stmt->bindValue(3, $menteeID, PDO::PARAM_INT);

    $mentor = FALSE;
    $mentee = FALSE;

    if($menteeID == $proposerID){
        $stmt->bindValue(4, 1, PDO::PARAM_INT);
        $mentee = TRUE;
    }
    else{
        $stmt->bindValue(4, 0, PDO::PARAM_INT);
    }

    if($mentorID == $proposerID){
        $stmt->bindValue(5, 1, PDO::PARAM_INT);
        $mentor = FALSE;
    }
    else{
        $stmt->bindValue(5, 0, PDO::PARAM_INT);
    }

    $date = new Datetime('NOW');
    $dateStr = $date->format('Y-m-d');

    $stmt->bindValue(6, $dateStr, PDO::PARAM_STR);

    $stmt->execute();

    if(!$mentee && !$mentor){
        $menteeEmail = getEmail($menteeID);
        $mentorEmail = getEmail($mentorID);
        mail($menteeEmail, "BAConnect: Mentorship Proposal", "An admin has proposed a mentorship relationship for you. Click this link to log-in and view your profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php");
        mail($mentorEmail, "BAConnect: Mentorship Proposal", "An admin has proposed a mentorship relationship for you. Click this link to log-in and view your profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php");
    }
    else if($mentee){
        $menteeEmail = getEmail($menteeID);
        mail($menteeEmail, "BAConnect: Mentorship Proposal", "A user has proposed a mentorship relationship with you. Click this link to log-in and view your profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php");
    }
    else if($mentor){
        $mentorEmail = getEmail($mentorID);
        mail($mentorEmail, "BAConnect: Mentorship Proposal", "A user has proposed a mentorship relationship with you. Click this link to log-in and view your profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php");
    }
    return true;
}

function forcePairMentorships($account_id,$mentorAccID, $menteeAccID){
	$accountType=getAccountTypeFromAccountID($account_id);
	if($accountType >= 2){
		$con = Connection::connect();
		$date = new Datetime('NOW');
		$dateStr = $date->format('Y-m-d H:i:s');
		$menteeEmail = getEmail($menteeAccID);
        $mentorEmail = getEmail($mentorAccID);
		$menteeName=getName($menteeAccID);
		$mentorName=getName($mentorAccID);
		mail($menteeEmail, "BAConnect: Mentorship", "You've successfully started a mentorship relationship with".$mentorName." Click this link to view thier profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php?user=".$mentorAccID);
		mail($mentorEmail, "BAConnect: Mentorship", "You've successfully started a mentorship relationship with".$menteeName." Click this link to view thier profile: http://corsair.cs.iupui.edu:22891/courseproject/profile.php?user=".$menteeAccID);
		//add to mentorship relationship
		$stmt = $con->prepare("insert into Mentorship(mentor_ID,mentee_ID,start) values (?, ?, ?)");
		$stmt->bindValue(1, $mentorAccID, PDO::PARAM_INT);
		$stmt->bindValue(2, $menteeAccID, PDO::PARAM_INT);
		$stmt->bindValue(3, $dateStr, PDO::PARAM_STR);
		$stmt->execute();
		$con = null;
	}
}//jonathan

function hasAlreadySentRequest($target_user) {
    if (isset($_SESSION['account_ID'])) {
        $current_user = $_SESSION['account_ID'];

        $con = Connection::connect();
        $stmt = $con->prepare("SELECT * FROM `Pending Mentorship` WHERE (mentor_ID = '" . $target_user . "' AND mentee_ID = '" . $current_user . "') OR (mentor_ID = '" . $current_user . "' AND mentee_ID = '" . $target_user . "')");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return count($result) > 0;
    } else {
        return FALSE;
    }
}