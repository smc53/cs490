<?php

$request                = isset($_POST["request"]) ? $_POST["request"] : "**ERROR**";

$output                 = '**No value returned.**';

$payload->examID = 6;
echo getCompletedExam('stest', $payload);

if($request != "**ERROR**") {
    $payload                = json_decode($_POST["payload"], true);
    if($request == "Login") {
        $output = json_encode(attemptLogin($_POST["username"], $_POST["password"]));
    }else if($request == "AddQuestion") {
        $output = addQuestionToBank($_POST["username"], $payload);
    }else if($request == "RemoveQuestion") {
        $output = deleteQuestionFromBank($_POST["username"], $payload);
    }else if($request == "GetQuestions") {
        $output = getQuestions();
    }else if($request == "GetAllTests") {
        $output = getAllTests();
    }else if($request == "AddTest") {
        $output = addTest($_POST["username"], $payload);
    }else if($request == "RemoveTests") {
        $output = removeTests($_POST["username"], $payload);
    }else if($request == "GetTestData") {
        $output = getTestData($payload);
    }else if($request == "GetQuestion") {
        $output = getSingleQuestion($payload);
    }else if($request == "SubmitQuestion") {
        $output = submitQuestion($_POST["username"], $payload);
    }else if($request == "GetCompletedExam") {
        $output = getCompletedExam($_POST["username"], $payload);
    }else{
        $output = "**ERROR Unsupported request.**";
    }
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
function getAllTests() {
    $query = "SELECT * FROM `ConfiguredExaminations`;";
    $result = runSQLQuerry($query);
    $testArray = array();
    while($row = $result->fetch_assoc()) {
        $jsonTemp->ID = $row["ID"];
        $jsonTemp->name = $row["Name"];
        $jsonTemp->questions = $row["QuestionIDs"];
        array_push($testArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->tests  = $testArray;
    return json_encode($jsonReturn);
}
function getTestData($payload) {
    $query     = "SELECT * FROM `ConfiguredExaminations` WHERE `ID` = '".$payload["examID"]."';";
    $result    = runSQLQuerry($query);
    $row       = $result->fetch_assoc();
    $jsonReturn;
    $jsonReturn->ids = $row["QuestionIDs"];
    $jsonReturn->name = $row["Name"];
    return json_encode($jsonReturn);
}
function getSingleQuestion($payload) {
    $query     = "SELECT * FROM `QuestionBank` WHERE `ID` = '".$payload["ID"]."';";
    $result    = runSQLQuerry($query);
    $row       = $result->fetch_assoc();
    $jsonReturn;
    $jsonReturn->question = $row["Question"];
    return json_encode($jsonReturn);
}
function addTest($user, $payload) {
    $query = "INSERT INTO `ConfiguredExaminations`(`CreatorID`, `Name`, `QuestionIDs`) VALUES ('".$user."', '".$payload["testname"]."', '".$payload["qid"] ."');";
    $result = runSQLQuerry($query);
    return 1;
}
function removeTests($user, $payload) {
    $examID = $payload["ID"];
    $query      = "DELETE FROM `ConfiguredExaminations` WHERE `ID` = '".$examID."';";
    runSQLQuerry($query);
    return 1;
}
function deleteQuestionFromBank($user, $payload) {
    $questionID = $payload["questionID"];
    $query      = "DELETE FROM `QuestionBank` WHERE `ID` = '".$questionID."';";
    runSQLQuerry($query);
    return 1;
}
function submitQuestion($user, $payload) {
    $query = "INSERT INTO `CompletedExaminations`(`StudentID`, `ExamID`, `QuestionID`, `Answer`) VALUES ('".$user."', '".$payload["testid"]."', '".$payload["questionid"]."', '".$payload["answer"]."');";
    runSQLQuerry($query);
    return 1;
}
function getCompletedExam($user, $payload) {
    $examID = 6;
    $studentID          = getUserID($user);
    $examQuery          = "SELECT * FROM `ConfiguredExaminations` WHERE `ID` = ".$examID.";";

    $examResult         = runSQLQuerry($examQuery);
    $examRow            = $examResult->fetch_assoc();
    $questionCSV        = $examRow["QuestionIDs"];
    $questionIDList     = explode(',', $questionCSV);
    $questionArray      = array();
    $correctAnswerArray = array();
    $metadataArray      = array();
    $studentAnswerArray = array();

    foreach($questionIDList as $questionID) {
        $questionQuery      = "SELECT * FROM `QuestionBank` WHERE `ID` = '".$questionID."';";
        $questionResult     = runSQLQuerry($questionQuery);
        $questionRow        = $questionResult->fetch_assoc();
        array_push($questionArray, $questionRow["Question"]);
        array_push($correctAnswerArray, $questionRow["ExpectedOutput"]);
        array_push($metadataArray, $questionRow["Metadata"]);

        $studentAnswerQuery     = "SELECT `Answer` FROM `CompletedExaminations` WHERE `ExamID` = '".$examID."' AND `QuestionID` = '".$questionID."' AND `StudentID` = '".$studentID."' ORDER BY `ID` DESC;";
        $studentAnswerResult    = runSQLQuerry($studentAnswerQuery);
        $studentAnswerRow       = $studentAnswerResult->fetch_assoc();
        array_push($studentAnswerArray, $studentAnswerRow["Answer"]);
    }

    $jsonReturn;
    $jsonReturn->questions = $questionArray;
    $jsonReturn->correctAnswers = $correctAnswerArray;
    $jsonReturn->metadata = $metadataArray;
    $jsonReturn->answers = $studentAnswerArray;
    return json_encode($jsonReturn);
}
function getUserID($username) {
    $query = "SELECT `ID` FROM `UsersTable` WHERE `Username` = '".$username."';";
    $result     = runSQLQuerry($query);
    $row        = $result->fetch_assoc();
    return $row["ID"];
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
