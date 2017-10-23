<?php

/*Error Messages*/
$invalidRequestMessage = "**No value returned.**";
$missingRequestMessage = "**NO REQUEST SENT**";
$SQLConnectionErrorMessage = "**SQL Connection Failed**";
$SQLExecutionErrorMessage = "**SQL Execution Failed**";

/*************************************************************************
Request Service Loop.
**************************************************************************/
if(isset($_POST["request"])) {
    $request  = $_POST["request"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $output   = $invalidRequestMessage;
    $payload  = json_decode($_POST["payload"], true);
    switch($request) {
        case "Login"                : $output = attemptLogin($username, $password);          break;                      
        case "AddQuestion"          : $output = addQuestionToBank($username, $payload);      break;
        case "RemoveQuestion"       : $output = deleteQuestionFromBank($username, $payload); break;
        case "GetQuestions"         : $output = getQuestions();                              break;
        case "GetAllTests"          : $output = getAllTests();                               break;
        case "AddTest"              : $output = addTest($username, $payload);                break;
        case "RemoveTests"          : $output = removeTests($username, $payload);            break;      
        case "GetTestData"          : $output = getTestData($payload);                       break; 
        case "GetQuestion"          : $output = getSingleQuestion($payload);                 break;     
        case "SubmitQuestion"       : $output = submitQuestion($username, $payload);         break;  
        case "GetCompletedExam"     : $output = getCompletedExam($username, $payload);       break;     
        case "SubmitGradedExam"     : $output = submitGradedExam($username, $payload);       break; 
        case "GetGradesForExam"     : $output = getGradesForExam($payload);                  break;  
        case "GetStudentGrades"     : $output = getStudentGrades($username);                 break;     
        case "GetStudentExamGrade"  : $output = getStudentExamGrade($user, $payload);        break;
        default                     : $output = "**ERROR Unsupported request.**";            break;
    }
    echo $output;
}else{
    echo $missingRequestMessage;
}


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
    $query              = "SELECT * FROM UsersTable WHERE `Username` = '" .$username . "' AND `Password` = '".$hashedPassword."'";
    if($result  = runSQLQuerry($query)) {
        if($row = $result->fetch_assoc()) {
            if($row['Password'] == $hashedPassword) {
                $jsonReturn->type   = $row['AccountType'] == 0 ? 'S' : 'T';
                $jsonReturn->name   = $row['RealName'];
            }
        }
    }
    return json_encode($jsonReturn); 
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
    $query    = "INSERT INTO `QuestionBank`(`CreatorID`, `Metadata`, `Question`, `ExpectedOutput`) VALUES ('".$user."', '".$metadata."', '".$question."', '".$answer."');";
    runSQLQuerry($query);
    return 'T';
}
function getQuestions() {
    $query          = "SELECT * FROM `QuestionBank`;";
    $result         = runSQLQuerry($query);
    $questionArray  = array();
    while($row = $result->fetch_assoc()) {
        $jsonTemp->ID       = $row["ID"];
        $jsonTemp->question = $row["Question"];
        array_push($questionArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->questions  = $questionArray;
    return json_encode($jsonReturn);
}
function getAllTests() {
    $query      = "SELECT * FROM `ConfiguredExaminations`;";
    $result     = runSQLQuerry($query);
    $testArray  = array();
    while($row = $result->fetch_assoc()) {
        $jsonTemp->ID        = $row["ID"];
        $jsonTemp->name      = $row["Name"];
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
    $jsonReturn->ids  = $row["QuestionIDs"];
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
    $query  = "INSERT INTO `ConfiguredExaminations`(`CreatorID`, `Name`, `QuestionIDs`) VALUES ('".$user."', '".$payload["testname"]."', '".$payload["qid"] ."');";
    $result = runSQLQuerry($query);
    return 'T';
}
function removeTests($user, $payload) {
    $examID = $payload["ID"];
    $query  = "DELETE FROM `ConfiguredExaminations` WHERE `ID` = '".$examID."';";
    runSQLQuerry($query);
    return 'T';
}
function deleteQuestionFromBank($user, $payload) {
    $questionID = $payload["questionID"];
    $query      = "DELETE FROM `QuestionBank` WHERE `ID` = '".$questionID."';";
    runSQLQuerry($query);
    return 'T';
}
function submitQuestion($user, $payload) {
    $query = "INSERT INTO `CompletedExaminations`(`StudentID`, `ExamID`, `QuestionID`, `Answer`) VALUES ('".$user."', '".$payload["testid"]."', '".$payload["questionid"]."', '".$payload["answer"]."');";
    runSQLQuerry($query);
    return 'T';
}
function getCompletedExam($user, $payload) {
    $examID             = $payload["examID"];
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
    $jsonReturn->questions      = $questionArray;
    $jsonReturn->correctAnswers = $correctAnswerArray;
    $jsonReturn->metadata       = $metadataArray;
    $jsonReturn->answers        = $studentAnswerArray;
    return json_encode($jsonReturn);
}
function submitGradedExam($user, $payload) {
    $query  = "INSERT INTO `Grades`(`StudentID`, `ExamID`, `Comments`) VALUES ('".getUserID($user)."', '".$payload["examID"]."', '".$payload["comments"]."')";
    runSQLQuerry($query);
    return 'T';
}
function getStudentExamGrade($user, $payload) {
    $query  = "SELECT * WHERE `StudentID` = '".getUserID($user)."' AND `ExamID` = '".$payload["examID"]."' ORDER BY `ID` DESC;";
    $result = runSQLQuerry($query);
    $row    =  $examResult->fetch_assoc();
    $jsonReturn;
    $jsonReturn->comments = $row["Comments"];
    return json_encode($jsonReturn);
}
function getStudentGrades($user) {
    $query      = "SELECT * WHERE `StudentID` = '".getUserID($user)."' ORDER BY `ID` DESC;";
    $result     = runSQLQuerry($query);
    $gradeArray = array();
    while($row = $result->fetch_assoc()) {
        $jsonTemp->examID   = $row["ExamID"];
        $jsonTemp->comments = $row["Comments"];
        array_push($gradeArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->grades  = $gradeArray;
    return json_encode($jsonReturn);
}
function getGradesForExam($payload) {
    $query      = "SELECT * WHERE `ExamID` = '".$payload["examID"]."' ORDER BY `ID` DESC;";
    $result     = runSQLQuerry($query);
    $gradeArray = array();
    while($row  = $result->fetch_assoc()) {
        $jsonTemp->studentID    = $row["StudentID"];
        $jsonTemp->comments     = $row["Comments"];
        array_push($gradeArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->grades  = $gradeArray;
    return json_encode($jsonReturn);
}
/******************************** Library Functions **************************************/
function runSQLQuerry($query) {
    $conn = new Mysqli("sql2.njit.edu", "ash32", "OX9Wfn8v", "ash32");
    if ($conn->connect_error) {
        return null;
    }
    $result = $conn->query($query);
    mysqli_close($conn);
    return $result;
}
function getUserID($username) {
    $query  = "SELECT `ID` FROM `UsersTable` WHERE `Username` = '".$username."';";
    $result = runSQLQuerry($query);
    $row    = $result->fetch_assoc();
    return $row["ID"];
}
?>