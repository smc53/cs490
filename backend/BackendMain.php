<?php

$request                = isset($_POST["request"]) ? $_POST["request"] : "**ERROR**";

$output                 = '**No value returned.**';
$payload                = json_decode($_POST["payload"], true);

if($request == "Login") {
    $output = json_encode(attemptLogin($_POST["username"], $_POST["password"]));
}else if($request == "AddQuestion") {
    $output = addQuestionToBank($_POST["username"], $payload);
}else if($request == "DeleteQuestion") {
    $output = deleteQuestionFromBank($_POST["username"], $payload);
}else if($request == "GetQuestions") {
    $output = getQuestions();
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
    $jsonReturn;
    $jsonReturn->type   = 'F';
    $hashedPassword     = md5($password);
    $query            = "SELECT * FROM UsersTable WHERE `Username` = '" .$username . "' AND `Password` = '".$hashedPassword."'";
    if($result          = runSQLQuerry($query)) {
        if($row = $result->fetch_assoc()) {
            if($row['Password'] == $hashedPassword) {
                if($row['AccountType'] == 0) {
                    $jsonReturn->type   = 'S';
                }else{
                    $jsonReturn->type   = 'T';
                }
                $jsonReturn->name       = $row['RealName'];
            }
        }
    }
    return $jsonReturn; 
}
/*************************************************************************
Amine Sebastian 10/17/17 8:23PM
Function that handles adding a question to the bank.
@param user The user's username.
@param payload The payload that contains all the required question information.
**************************************************************************/
function addQuestionToBank($user, $payload) {
    $metadata = $payload["params"];
    $question = $payload["question"];
    $answer   = $payload["answer"];
    echo $user;
    $query    = "INSERT INTO `QuestionBank`(`CreatorID`, `Metadata`, `Question`, `ExpectedOutput`) VALUES ('".$user."', '".$metadata."', '".$question."', '".$answer."');";
    runSQLQuerry($query);
    return 1;
}
function deleteQuestionFromBank($user, $questionID) {
    $questionID = $payload["ID"];
    $query      = "DELETE FROM `QuestionBank` WHERE 'ID' = '".$questionID."';";
    runSQLQuerry($query);
    return 1;
}
function getQuestions() {
    $query     = "SELECT * FROM `QuestionBank`;";
    $result    = runSQLQuerry($query);
    $questionArray = array();
    while($row = $result->fetch_assoc()) {
        $jsonTemp->ID = $row["ID"];
        $jsonTemp->question = $row["Question"];
        array_push($questionArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->questions  = $questionArray;
    return json_encode($jsonReturn);
}

/******************************** Library Functions **************************************/
function runSQLQuerry($query) {
    $conn   = new Mysqli("sql2.njit.edu", "ash32", "OX9Wfn8v", "ash32");
    if ($conn->connect_error) {
        return null;
    }
    $result = $conn->query($query);
    mysqli_close($conn);
    return $result;
}
?>
