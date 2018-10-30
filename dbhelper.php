<?php
require_once "database.php";

class Account {
    public $username;
    public $password;

    function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }
}

class User extends Account {
    public $firstName;
    public $middleName;
    public $lastName;
    public $email;
    public $gender;
    public $phoneNumber;
    public $status;

    public $address;

    function __construct($username, $password, $firstName, $middleName, $lastName, $email, $gender, $phoneNumber, $status, $address = null) {
        parent::__construct($username, $password);

        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->status = $status;

        $this->address = $address;
    }

    public static function fromID($account_id) {
        $con = Connection::connect();
        $stmt = $con->prepare("SELECT * FROM UserAddressView where account_ID = ?");
        $stmt->bindValue(1, $account_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $addr = new Address($row['street_address'], $row['street_address2'], $row['city'], $row['post_code'], $row['state_name'], $row['country']);
        return new self($row['username'], $row['password'], $row['first_name'], $row['middle_name'], $row['last_name'], $row['email_address'], $row['gender'], $row['phone_number'], $row['status'], $addr);
    }

    public function formatName() {
        if ($this->middleName == "") {
            return $this->firstName . " " . $this->lastName;
        } else {
            return $this->firstName . " " . $this->middleName . $this->lastName;
        }
    }

    public function formatStatus() {
        if ($this->status == 0) {
            return "Student";
        } elseif ($this->status == 1) {
            return "Working Professional";
        } else {
            return "Unknown Status";
        }
    }

    public function formatGender() {
        if ($this->gender == 0) {
            return "Male";
        } elseif ($this->gender == 1) {
            return "Female";
        } else {
            return "Nonbinary/Other";
        }
    }

    public function formatCityAndState() {
        if (!is_null($this->address)) {
            return $this->address->city . ", " . $this->address->state;
        } else {
            return "";
        }
    }

}

class Address {
    public $street;
    public $street2;
    public $city;
    public $postcode;
    public $state;
    public $country;

    function __construct($street, $street2, $city, $postcode, $state, $country) {
        $this->street = $street;
        $this->street2 = $street2;
        $this->city = $city;
        $this->postcode = $postcode;
        $this->state = $state;
        $this->country = $country;
    }
}

class EducationHistoryEntry {
    public $schoolName;
    public $degreeType;
    public $degreeMajor;
    public $enrollmentYear;
    public $gradYear;

    function __construct($schoolName, $degreeType, $degreeMajor, $enrollmentYear, $gradYear) {
        $this->schoolName = $schoolName;
        $this->degreeType = $degreeType;
        $this->degreeMajor = $degreeMajor;
        $this->enrollmentYear = $enrollmentYear;
        $this->gradYear = $gradYear;
    }

}

class WorkHistoryEntry {
    public $companyName;
    public $jobTitle;
    public $startYear;
    public $endYear;

    function __construct($companyName, $jobTitle, $startYear, $endYear) {
        $this->companyName = $companyName;
        $this->jobTitle = $jobTitle;
        $this->startYear = $startYear;
        $this->endYear = $endYear;
    }
}
