<?php
if(isset($_POST['request'])){
  $request= $_POST['request'];
///    other then grade request, just forword to Backend     ///  
  if($request!="SubmitGrading"){
    $post=$_POST;
    $string = http_build_query($post);  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://afsaccess4.njit.edu/~ash32/CS490/BackendMain.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
    $dbresponse = curl_exec($ch);
    curl_close($ch);
    echo $dbresponse;
  }elseif($request=="SubmitGrading"){
// used for submitting the graded test  ///
function curltobackend($query){
    $string2 = http_build_query($query);  
    $ch = curl_init();
    //curl_setopt($ch, CURLOPT_URL, 'http://afsaccess4.njit.edu/~mss86/CS490/input.php');
    curl_setopt($ch, CURLOPT_URL, 'http://afsaccess4.njit.edu/~ash32/CS490/BackendMain.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $string2);
    $response = curl_exec($ch);
    curl_close($ch);  
    return $response;
}
//    write the python code into file ///
function writefile($filename,$content,$call){
   $myfile = fopen($filename, "w") or die("Unable to open file!");
   $txt = $content."\n"."polska=".$call."\n".'print ("\n")'."\n"."print( polska )";
   fwrite($myfile, $txt);
   fclose($myfile);
}
//    execute python3 file and return the result if any ///////////////
function exePython($fname, $userin){
  if($userin){
    $command = "timeout 3s python3 ".$fname." < input.txt";
  }else{
    $command = "timeout 3s python3 ".$fname;
  }
  exec($command, $outputC, $returnC);
  if($returnC==0 && sizeof($outputC) > 0){
      return $outputC[sizeof($outputC)-1];  // last line which is the answer
  }else{
    return "";
  }
}
// if the program involves user input, write it to file   //
function writeinput($callpa){
   $callinput=preg_split("/,/", $callpa[1]);
   $input="";
   for($i=0;$i<count($callinput);$i++){
     $input=$input.$callinput[$i]."\n";
   }
   $myfile = fopen("input.txt", "w") or die("Unable to open file!");
   $txt = $input;
   fwrite($myfile, $txt);
   fclose($myfile);
   $userinput=true;
}

$testcomments = array();  // array of comments in order
$testgrades = array();    // array of grades   in order
$testgrade = 0;           // final test grade
$Correctfile = "correct1.py";   
$Answerfile = "answer1.py";
  
  
  
  
  
/// need to get user from front end*****************************************************************************************************************************
// need to get test id from front end***************************************************************************************************************************
$username="stest"; // temporary
$examID=6;         // temporary
$username=$_POST['username'];
$paylo=$_POST['payload'];
$payobj= json_decode($paylo, true); 
$examID=$payobj["examID"]; 
 
 
 
  
//           Get Json From Back end upon finished test               //   
$payload = new \stdClass();
$payload->examID = $examID;
$payload->username =$username;
$jsonpayload = json_encode($payload);
$postr= ['request'=>'GetCompletedExam','username'=>$username,'payload'=>$jsonpayload ];
$json= curltobackend($postr);

//           Decode the json into Appropriate Arrays                 //
$obj= json_decode($json, true);
$correctarray= $obj["correctAnswers"];
$callarray = $obj["metadata"];
$answerarray=$obj['answers'];


//           For each question Run grading loop                      //
$questions = sizeof($correctarray);
for($q=0;$q<$questions;$q++){     
      $correct=$correctarray[$q];
      $callstring=$callarray[$q];
      $answer=$answerarray[$q];
      //$answer=$correct;
      /// making sure to not execute grading loop on NULL elements    //
      if($correct==NULL || strlen($correct) < 5 ){
        $correct="nothing";
      }
      if($callstring==NULL  || strlen($callstring) < 3){
        $callstring="noting";
      }
      if($answer==NULL  || strlen($answer) < 5){
        $answer="nothing ";
      }
      
     $expectedanswer = array();
     $recivedanswer = array();
     $callfield = preg_split("/\r\n|\n|\r/", $callstring);
     $calltimes=count($callfield);
     $penelty=0;

     // for each funcion call, execute that many times////////////////////////////////////////////////////
     for($i=0;$i<$calltimes;$i++){
        $call3=$callfield[$i];
        $callp=preg_split("/</", $call3);
        if($callp[0]==""){
          continue;
        }
        /////////// if there is user input write it to file ////
        if (count($callp)>1){
          writeinput($callp);
          $userinput=true;
        }
        else{
          $userinput=false;
        }
        $call1=$callp[0];
        ///////////// execute correct answer to find desired result //////
        writefile($Correctfile,$correct,$call1);
        $correctoutput= exePython($Correctfile, $userinput);
        $expectedanswer[]=$correctoutput;
        ////////////// Testing the User Answer //////////////////////////////////////////////////
        writefile($Answerfile,$answer,$call1);
        $answeroutput = exePython($Answerfile, $userinput);
        $success=true;

        $comment="";
        if($answeroutput!=""){
          $recivedanswer[]=$answeroutput;
        }else{
          $success=false;
        }
       if ($success==false){
         $command = "timeout 3s java grader ".'"'.$call1.'"'." ".$Answerfile." ".$Correctfile;
         exec($command, $outputJ, $returnJ);
         if(sizeof($outputJ)>0 && $returnJ[0]==0){
               $answeroutput = exePython($Answerfile, $userinput);
              if($answeroutput!=""){
                   $comment=$comment."<br>Mistake in function defenition, -1<br>";
                   $penelty=1;
                   $recivedanswer[]=$answeroutput;
                   $success=true;
              }
         }
         else{
           $recivedanswer[]=" ";
           $comment=$comment."Error could not Execute<br>";
           for($e=0; $e<sizeof($outputJ);$e++){
               $comment=$comment."<br>".$outputJ[$e];
               $penelty++;
           }
           break;
           }                         
        }
   }
/////////////////////////////////////////////// at the end generate grade for executable code///////////////////////
     $grade=10;
     if($success){
       $ccount=0;
       for($i=0; $i<count($expectedanswer);$i++){
         $output=", Incorrect <br>";
    
         if ( $expectedanswer[$i] == $recivedanswer[$i] ){
          $output=", Correct <br>";  
          $ccount++; 
       }
       $comment= $comment."Call: | ".$callfield[$i]." Expected: ".$expectedanswer[$i].", Recived: ".$recivedanswer[$i].$output; 
      }
      $grade=($ccount/count($expectedanswer))*10-$penelty;
      if($grade<0){
        $grade=0;
      }
    }else{
      $grade=$grade-$penelty;
    }
    $comment= $comment."Grade: ".$grade;
    //echo $comment;
  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  $testcomments[]=$comment; // one comment per question
  $testgrades[]=$grade;     // one grade per question
  $testgrade+=$grade;
}   








///////////////////////////////////////////////////////////////////////
///////  At the end, Send to database to Store                      ///
///////////////////////////////////////////////////////////////////////
$jsonForword = new \stdClass();
$jsonForword->comments = $testcomments;
$jsonForword->grades = $testgrades;
$jsonForword->testgrade = $testgrade;
$jsonForword->username = $username;
$jsonForword->answers = $answerarray;
$JsonForword->examID = $examID;
$json = json_encode($jsonForword);
$post= ['username'=>$username ,'request'=>'SubmitGradedExam', 'payload'=>$json];
//var_dump($post);
var_dump( curltobackend($post));

// delete the files after we are done
//exec("rm ".$Answerfile, $output, $return);
//exec("rm ".$Correctfile, $output, $return);

}//elseif
}//ifisset
?>
