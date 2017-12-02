<?php

/*Error Messages*/
$invalidRequestMessage     = "**No value returned.**";
$missingRequestMessage     = "**NO REQUEST SENT**";
$SQLConnectionErrorMessage = "**SQL Connection Failed**";
$SQLExecutionErrorMessage  = "**SQL Execution Failed**";

/*************************************************************************
Request Service Loop.
**************************************************************************/
if(isset($_POST["request"])) {
    $request  = ucfirst($_POST["request"]);
    $username = $_POST["username"];
    $password = $_POST["password"];
    $output   = $invalidRequestMessage;
    $payload  = json_decode($_POST["payload"], true);
    switch($request) {
        case "Login"                     : $output = attemptLogin($username, $password);          break;                      
        case "AddQuestion"               : $output = addQuestionToBank($username, $payload);      break;
        case "RemoveQuestion"            : $output = deleteQuestionFromBank($username, $payload); break;
        case "GetQuestions"              : $output = getQuestions($payload);                      break;
        case "GetAllTests"               : $output = getAllTests();                               break;
        case "AddTest"                   : $output = addTest($username, $payload);                break;
        case "RemoveTests"               : $output = removeTests($username, $payload);            break;      
        case "GetTestData"               : $output = getTestData($payload);                       break; 
        case "GetQuestion"               : $output = getSingleQuestion($payload);                 break;     
        case "SubmitQuestion"            : $output = submitCompletedQuestion($username, $payload);break;  
        case "GetCompletedExam"          : $output = getCompletedExam($username, $payload);       break;     
        case "SubmitGradedExam"          : $output = submitGradedExam($username, $payload);       break; 
        case "GetGradesForExam"          : $output = getGradesForExam($payload);                  break;  
        case "GetStudentGrades"          : $output = getStudentGrades($username);                 break;     
        case "GetStudentExamGrade"       : $output = getStudentExamGrade($user, $payload);        break;
        case "ReleaseGrades"             : $output = releaseGrades($user, $payload);              break;
        case "EditGradesForExam"         : $output = editGradesForExam($username, $payload);       break;
        case "GetQuestionTopics"         : $output = getQuestionTopics();                         break;
        case "GetQuestionHiddenState"    : $output = getQuestionHiddenState($payload);            break;
        case "ToggleQuestionHiddenState" : $output = toggleQuestionHiddenState($payload);         break;
        case "GetUsername"               : $output = getUsername($payload);                       break;
        case "Reflect"                   : $output = reflect($_POST["payload"]);                  break;
        case "ReflectEscaped"            : $output = reflectEscaped($_POST["payload"]);           break;
        default                          : $output = "**ERROR Unsupported request.**";            break;
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
    $tags     = $payload["tags"];
    $hidden   = 0;
    if( isset( $payload["hidden"] ) ){
        $hidden = 1;
     }
    $query    = "INSERT INTO `QuestionBank`(`CreatorID`, `Metadata`, `Question`, `ExpectedOutput`, `Tags`, `Hidden`) VALUES ('".$user."', '".$metadata."', '".$question."', '".$answer."', '".$tags."', '".$hidden."');";
    runSQLQuerry($query);
    return 'T';
}
function editQuestion($username, $payload) {
    return addQuestionToBank($username, $payload);
}
function getQuestions($payload) {
    $query = "SELECT * FROM `QuestionBank` WHERE `Hidden` = 0";

    if(isset($payload["filter"])) {      
        if($payload["filter"][0] != "topic\$none") {
            $filterArray = $payload["filter"];
            $additionalSearch = " AND `Tags` LIKE '%".$filterArray[0]."%'";
            $query = $query.$additionalSearch;
        }

        if(count($filterArray) > 1) {
            $query = $query."FROM (SELECT * FROM `QuestionBank` WHERE "
            for(int i=1; i<count($filterArray); i++) {
                $additionalSearch = "`Tags` LIKE '%".$filterArray[$i]."%'";
                if($i < count($filterArray)-1) {
                    $additionalSearch = $additionalSearch." OR ";
                }
                $query = $query.$additionalSearch.")";
            }
        }
    }

    $questionArray  = array(); 
    $result         = runSQLQuerry($query);

    while($row = $result->fetch_assoc()) {
        $jsonTemp->ID       = $row["ID"];
        $jsonTemp->question = $row["Question"];
        $jsonTemp->metadata = $row["Metadata"];
        $jsonTemp->output   = $row["ExpectedOutput"];
        $jsonTemp->tags     = $row["Tags"];
        array_push($questionArray, json_encode($jsonTemp));      
    }
    $jsonReturn;
    $jsonReturn->questions  = $questionArray;
    $jsonReturn->filter     =  $payload["filter"][0];
    return json_encode($jsonReturn);
}
function getQuestionHiddenState($payload) {
    $query              = "SELECT `Hidden` FROM `QuestionBank` WHERE `ID` = '".$payload["ID"]."';";
    $result             = runSQLQuerry($query);
    $row                = $result->fetch_assoc();
    $jsonReturn->hidden =  $row["Hidden"];;
    return json_encode($jsonReturn);
}
function toggleQuestionHiddenState($payload) {
    $query  = "UPDATE `QuestionBank` SET `Hidden` = ".$payload["hidden"]." WHERE `ID` = '".$payload["ID"]."';";
    runSQLQuerry($query);
    return 'T';
}
function getAllTests() {
    $query      = "SELECT * FROM `ConfiguredExaminations`;";
    $result     = runSQLQuerry($query);
    $testArray  = array();
    while($row  = $result->fetch_assoc()) {
        $jsonTemp->ID        = $row["ID"];
        $jsonTemp->name      = $row["Name"];
        $jsonTemp->questions = $row["Questions"];
        array_push($testArray, json_encode($jsonTemp));
    }
    $jsonReturn;
    $jsonReturn->tests  = $testArray;
    return json_encode($jsonReturn);
}
function getTestData($payload) {
    $testDataObject = internal_getTestData($payload["examID"]);
    return json_encode($testDataObject);
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
    $query  = "INSERT INTO `ConfiguredExaminations`(`CreatorID`, `Name`, `Questions`) VALUES ('".$user."', '".$payload["testname"]."', '".json_encode($payload["questions"])."');";
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
function submitCompletedQuestion($user, $payload) {
    $query = "INSERT INTO `CompletedExaminations`(`StudentID`, `ExamID`, `QuestionID`, `Answer`) VALUES ('".getUserID($user)."', '".$payload["examID"]."', '".$payload["questionID"]."', '".$payload["answer"]."');";
    runSQLQuerry($query);
    return 'T';
}
function getCompletedExam($user, $payload) {
    $examID             = $payload["examID"];
    $studentID          = getUserID($user);
    $examQuery          = "SELECT * FROM `ConfiguredExaminations` WHERE `ID` = ".$examID.";";
    $examResult         = runSQLQuerry($examQuery);
    $examRow            = $examResult->fetch_assoc();
    $questions          = json_decode($examRow["Questions"], true);
    $questionArray      = array();
    $correctAnswerArray = array();
    $metadataArray      = array();
    $studentAnswerArray = array();
    foreach($questions as $currentQuestionInfo) {
        $questionQuery              = "SELECT * FROM `QuestionBank` WHERE `ID` = '".$currentQuestionInfo["qid"]."';";
        $questionResult             = runSQLQuerry($questionQuery);
        $questionRow                = $questionResult->fetch_assoc();
        $fullQuestion->question     = $questionRow["Question"];
        $fullQuestion->questionInfo = $currentQuestionInfo;
        array_push($questionArray, json_encode($fullQuestion));
        array_push($correctAnswerArray, $questionRow["ExpectedOutput"]);
        array_push($metadataArray, $questionRow["Metadata"]);
        $studentAnswerQuery         = "SELECT `Answer` FROM `CompletedExaminations` WHERE `ExamID` = '".$examID."' AND `QuestionID` = '".$currentQuestionInfo["qid"]."' AND `StudentID` = '".$studentID."' ORDER BY `ID` DESC;";
        $studentAnswerResult        = runSQLQuerry($studentAnswerQuery);
        $studentAnswerRow           = $studentAnswerResult->fetch_assoc();
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
    $query  = "INSERT INTO `Grades`(`StudentID`, `ExamID`, `Scores`, `Comments`) VALUES ('"
    .getUserID($user)
    ."', '".$payload["examID"]."', '"
    .json_encode($payload["scores"])
    ."', '".addslashes(json_encode($payload["comments"]))."')";
    runSQLQuerry($query);
    return 'T';
}
function releaseGrades($user, $payload) {
    $query  = "UPDATE `Grades` SET `Released` = 1 WHERE `ExamID` = '".$payload["examID"]."';";
    runSQLQuerry($query);
    return 'T';
}
function getStudentExamGrade($user, $payload) {
    $query  = "SELECT * FROM `Grades` WHERE `StudentID` = '".getUserID($user)."' AND `ExamID` = '".$payload["examID"]."' ORDER BY `ID` DESC;";
    $result     = runSQLQuerry($query);
    $gradeArray = array();
    $checkedStudentsArray = array();

    $examData = internal_getTestData($payload["examID"]);
    $examQuestions = json_decode($examData->questions);
    $questionsArray = array();
    for($i = 0; $i<count($examQuestions); $i++) {
        $question->qid        = $examQuestions[$i]->qid;
        $question->score      = json_decode($row["Scores"])[$i];
        $question->maxScore   = $examQuestions[$i]->maxPoints;
        $question->comments   = json_decode($row["Comments"])[$i];
        $studentAnswerQuery   = "SELECT `Answer` FROM `CompletedExaminations` WHERE `ExamID` = '".$payload["examID"]."' AND `QuestionID` = '".$question->qid."' AND `StudentID` = '".getUserID($user)."' ORDER BY `ID` DESC;";
        $studentAnswerResult  = runSQLQuerry($studentAnswerQuery);
        $studentAnswerRow     = $studentAnswerResult->fetch_assoc();
        $question->answer     = $studentAnswerRow["Answer"]; 

        $questionNameQuery          = "SELECT `Question` FROM `QuestionBank` WHERE `ID` = '".$question->qid."';";
        $questionNameResult         = runSQLQuerry($questionNameQuery);
        $questionNameRow            = $questionNameResult->fetch_assoc();
        $question->questionContent  = $questionNameRow["Question"]; 

        array_push($questionsArray, clone($question));
    }
    $examGrade->examName    = $examData->name;
    $examGrade->examID      = $row["ExamID"];
    $examGrade->questions   = $questionsArray;
    $examGrade->studentID   = $row["StudentID"];
    $examGrade->studentName = getUsername($row["StudentID"]);
    $examGrade->released    = $row["Released"];
    array_push($gradeArray, $examGrade); 
    array_push($checkedStudentsArray, $row["StudentID"]);     

    $jsonReturn->grades  = $gradeArray;
    return json_encode($jsonReturn);
}
function getStudentGrades($user) {
    $query      = "SELECT * FROM `Grades` WHERE `StudentID` = '".getUserID($user)."' ORDER BY `ID` DESC;";
    $result     = runSQLQuerry($query);
    $gradeArray = array();
    $checkedExams = array();
    while($row  = $result->fetch_assoc()) {
        if(array_search($row["ExamID"], $checkedExams) === FALSE) {
            $examData = internal_getTestData($row["ExamID"]);
            $examQuestions = json_decode($examData->questions);
            $questionsArray = array();
            for($i = 0; $i<count($examQuestions); $i++) {
                $totalScore           = 0;
                $maxScore             = 0;
                $question->qid        = $examQuestions[$i]->qid;
                $question->score      = json_decode($row["Scores"])[$i];
                $totalScore          += json_decode($row["Scores"])[$i];
                $question->maxScore   = $examQuestions[$i]->maxPoints;
                $maxScore            += $examQuestions[$i]->maxPoints;
                $question->comments   = json_decode($row["Comments"])[$i];
                $studentAnswerQuery   = "SELECT `Answer` FROM `CompletedExaminations` WHERE `ExamID` = '".$row["ExamID"]."' AND `QuestionID` = '".$question->qid."' AND `StudentID` = '".getUserID($user)."' ORDER BY `ID` DESC;";
                $studentAnswerResult  = runSQLQuerry($studentAnswerQuery);
                $studentAnswerRow     = $studentAnswerResult->fetch_assoc();
                $question->answer     = $studentAnswerRow["Answer"]; 

                $questionNameQuery          = "SELECT `Question` FROM `QuestionBank` WHERE `ID` = '".$question->qid."';";
                $questionNameResult         = runSQLQuerry($questionNameQuery);
                $questionNameRow            = $questionNameResult->fetch_assoc();
                $question->questionContent  = $questionNameRow["Question"]; 

                array_push($questionsArray, clone($question));
            }
            $examGrade              = null;
            $examGrade->examName    = $examData->name;
            $examGrade->examID      = $row["ExamID"];
            $examGrade->questions   = $questionsArray;
            $examGrade->studentID   = $row["StudentID"];
            $examGrade->studentName = getUsername($row["StudentID"]);
            $examGrade->released    = $row["Released"];
            $examGrade->score       = $totalScore;
            $examGrade->maxScore    = $maxScore;
            array_push($gradeArray, $examGrade);
            array_push($checkedExams, $row["ExamID"]);  
        }   
    }
    $jsonReturn->grades  = $gradeArray;
    return json_encode($jsonReturn);
}
function getGradesForExam($payload) {
    $query      = "SELECT * FROM `Grades` WHERE `ExamID` = '".$payload["examID"]."' ORDER BY `ID` DESC;";
    $result     = runSQLQuerry($query);
    $gradeArray = array();
    $checkedStudentsArray = array();
    $examData = internal_getTestData($payload["examID"]);
    $examQuestions = json_decode($examData->questions);

    while($row  = $result->fetch_assoc()) {
        if(array_search($row["StudentID"], $checkedStudentsArray) === FALSE) {
            $questionsArray = array();
            for($i = 0; $i<count($examQuestions); $i++) {
                $question->qid        = $examQuestions[$i]->qid;
                $question->score      = json_decode($row["Scores"])[$i];
                $question->maxScore   = $examQuestions[$i]->maxPoints;
                $question->comments   = json_decode($row["Comments"])[$i];
                $studentAnswerQuery   = "SELECT `Answer` FROM `CompletedExaminations` WHERE `ExamID` = '".$payload["examID"]."' AND `QuestionID` = '".$question->qid."' AND `StudentID` = '".$row["StudentID"]."' ORDER BY `ID` DESC;";
                $studentAnswerResult  = runSQLQuerry($studentAnswerQuery);
                $studentAnswerRow     = $studentAnswerResult->fetch_assoc();
                $question->answer     = $studentAnswerRow["Answer"]; 

                $questionNameQuery          = "SELECT `Question` FROM `QuestionBank` WHERE `ID` = '".$question->qid."';";
                $questionNameResult         = runSQLQuerry($questionNameQuery);
                $questionNameRow            = $questionNameResult->fetch_assoc();
                $question->questionContent  = $questionNameRow["Question"]; 

                array_push($questionsArray, clone($question));
            }
            $examGrade->examName    = $examData->name;
            $examGrade->examID      = $row["ExamID"];
            $examGrade->questions   = $questionsArray;
            $examGrade->studentID   = $row["StudentID"];
            $examGrade->studentName = getUsername($row["StudentID"]);
            $examGrade->released    = $row["Released"];
            array_push($gradeArray, $examGrade);
            array_push($checkedStudentsArray, $row["StudentID"]);   
        }   
    }
    $jsonReturn->grades  = $gradeArray;
    return json_encode($jsonReturn);
}
function editGradesForExam($username, $payload) {
    $qid             = $payload["qid"];
    $examData        = internal_getTestData($payload["examID"]);
    $examQuestion    = array();
    $examQuestions   = json_decode($examData->questions);
    $index = -1;

    for($i=0; $i<count($examQuestions); $i++) {
        if($examQuestions[$i]->qid == $qid) {
            $index = $i;
        }
    }
    if($index == -1) {
        return 'F';
    }

    $query            = "SELECT * FROM `Grades` WHERE `StudentID` = '".$payload["studentID"]."' AND `ExamID` = '".$payload["examID"]."' ORDER BY `ID` DESC;";
    $result           = runSQLQuerry($query);
    $gradeRow         = $result->fetch_assoc();

    $originalScores   = json_decode($gradeRow["Scores"]);
    $originalComments = json_decode($gradeRow["Comments"]);

    $originalScores[$index]   = $payload["score"];
    $originalComments[$index] = $payload["comment"];

    $query  = "INSERT INTO `Grades`(`StudentID`, `ExamID`, `Scores`, `Comments`, `Released`) VALUES ('".$payload["studentID"]."', '".$payload["examID"]."', '".json_encode($originalScores)."', '".json_encode($originalComments)."', '".$payload["released"]."')";
    runSQLQuerry($query);
    return 'T';
}
function getQuestionTopicsAmineSystem() {
    $query          = "SELECT `Tags` FROM `QuestionBank`;";
    $result         = runSQLQuerry($query);
    //$regex        = preg_quote("topic\$.*?(,|$)");
    $topicArray     = array();

    while($row = $result->fetch_assoc()) {
        $tags       = $row["Tags"];
        $tagArray   = split(',', $tags);
        foreach ($tagArray as $curr) {
            $trimmedTag = trim($curr);
            if (0 === strpos($trimmedTag, 'topic$')) {
                $topic = str_replace("topic$", "", $trimmedTag);
                if(array_search($topic, $topicArray) === FALSE) {
                    array_push($topicArray, $topic);
                }
            }
        }
    }
    $jsonReturn->topics  = $topicArray;
    return json_encode($jsonReturn);
}
function getQuestionTopics() {
    $query          = "SELECT * FROM `Topics`;";
    $result         = runSQLQuerry($query);
    //$regex        = preg_quote("topic\$.*?(,|$)");
    $topicArray     = array();

    while($row = $result->fetch_assoc()) {
        array_push($topicArray, $row["Topic"]);
    }
    $jsonReturn->topics  = $topicArray;
    return json_encode($jsonReturn);
}
function getUsername($payload) {
    $query  = "SELECT `Username` FROM `UsersTable` WHERE `ID` = '".$payload["ID"]."';";
    $result = runSQLQuerry($query);
    $row    = $result->fetch_assoc();
    return $row["Username"];
}
function reflect($data) {
    return $data;
}
function reflectEscaped($data) {
    return mysql_real_escape_string($data);
}
/******************************** Library Functions **************************************/
function runSQLQuerry($query) {
    $conn = new Mysqli("sql2.njit.edu", "ash32", "OX9Wfn8v", "ash32");
    if ($conn->connect_error) {
        return null;
    }
    $escapedQuery = $query;
    $result       = $conn->query($escapedQuery);
    mysqli_close($conn);
    return $result;
}
function getUserID($username) {
    $query  = "SELECT `ID` FROM `UsersTable` WHERE `Username` = '".$username."';";
    $result = runSQLQuerry($query);
    $row    = $result->fetch_assoc();
    return $row["ID"];
}
function internal_getUsername($ID) {
    $query  = "SELECT `Username` FROM `UsersTable` WHERE `ID` = '".$ID."';";
    $result = runSQLQuerry($query);
    $row    = $result->fetch_assoc();
    return $row["Username"];
}
function sqlAppend() {
    return "', '";
}
function internal_getTestData($examID) {
    $query            = "SELECT * FROM `ConfiguredExaminations` WHERE `ID` = '".$examID."';";
    $result           = runSQLQuerry($query);
    $row              = $result->fetch_assoc();
    $jsonReturn;
    $jsonReturn->name       = $row["Name"];
    $jsonReturn->questions  = $row["Questions"];
    return $jsonReturn;
}
?>