<?php

$payload->examID = 6;
echo getCompletedExam('stest', $payload);

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
    $jsonReturn->questions      = $questionArray;
    $jsonReturn->correctAnswers = $correctAnswerArray;
    $jsonReturn->metadata       = $metadataArray;
    $jsonReturn->answers        = $studentAnswerArray;
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
