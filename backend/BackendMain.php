<?php

//To attempt to log in, a POST request must be sent with the data field 'request' that contains the value 'login'.
//The server will then respond with a jsonPacket that contains one response.
//A 'T' is returned if the attempt is succesfull, and an 'F' is retured if the attempt fails.

$request                = isset($_POST["request"]) ? $_POST["request"] : "**ERROR**";

if($request == "login") {
    $username           = $_POST["username"];
    $password           = $_POST["password"];
    $url                = $_POST["requestURL"];
    echo attemptSQLLogin($username, $password); 
}

function attemptSQLLogin($username, $password) {
    $conn               = new Mysqli("sql2.njit.edu", "ash32", "OX9Wfn8v", "ash32");

    if ($conn->connect_error) {
        printf("Connect failed: %s\n", connect_error);
        exit();
    }
    $hashedPassword     = md5($password);
    $command            = "SELECT * FROM UsersTable WHERE `Username` = '" .$username . "'";
    if($result          = $conn->query($command)) {
        $count          = mysqli_num_rows($result);
        if($count >= 1) {
            while($row = $result->fetch_assoc()) {
                if($row['Password'] == $hashedPassword) {
                    mysqli_close($conn);
                    return 'T';
                }
            }
            return 'F';
        }
    }else{
        echo 'Query failed';
    }
    mysqli_close($conn);
    return 0;
}
?>
