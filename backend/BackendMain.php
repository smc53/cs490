<?php

$request                = isset($_POST["request"]) ? $_POST["request"] : "**ERROR**";

$output                 = '**ERROR No value returned.**';
if($request == "Login") {
    $output = attemptLogin($_POST["username"], $_POST["password"]);
}else{
    $output = "**ERROR Unsupported request.**";
}
echo $output;

/*************************************************************************
Amine Sebastian 10/10/17 5:23PM
Login function that handles validation of user information.
@param username The user's username.
@param password The user's password.
@return Returns T for teacher, S for student, F if the login fails.
**************************************************************************/
function attemptLogin($username, $password) {
    $conn               = new Mysqli("sql2.njit.edu", "ash32", "OX9Wfn8v", "ash32");
    $jsonReturn;
    if ($conn->connect_error) {
        $jsonReturn->type = 'F';
    }
    $hashedPassword     = md5($password);
    $command            = "SELECT * FROM UsersTable WHERE `Username` = '" .$username . "'";
    if($result          = $conn->query($command)) {
        $count          = mysqli_num_rows($result);
        mysqli_close($conn);
        if($count >= 1) {
            $found = false;
            while($row = $result->fetch_assoc()) {
                if($row['Password'] == $hashedPassword) {
                    if($row['AccountType'] == 0) {
                        $jsonReturn->type   = 'S';
                    }else{
                        $jsonReturn->type   = 'T';
                    }
                    $jsonReturn->name       = $row['RealName'];
                    $found = true;
                }
            }
            if(!$found) {
                $jsonReturn->type           = 'F';
            }
        }else{
            $jsonReturn->type               = 'F';
        }
    }
    return json_encode($jsonReturn); 
}

function addQuestionToBank();
function deleteQuestionFromBank();
function getQuestions();
?>
