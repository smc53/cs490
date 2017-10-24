<?php
if(isset($_POST['request'])){
  $request= $_POST['request'];
  if($request!="grade"){
    //////////////////CURL to BackEnd////////////////////////////////
    $post=$_POST;
    $string = http_build_query($post);  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://afsaccess4.njit.edu/~ash32/CS490/BackendMain.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
    $dbresponse = curl_exec($ch);
    curl_close($ch);
    //echo $dbresponse;
    echo "hello";
  }elseif($request=="grade"){


///// write content to file includinf fucnion call at the end /////////
function writefile($filename,$content,$call){
   $myfile = fopen($filename, "w") or die("Unable to open file!");
   $txt = $content."\n".'print ("\n")'."\n"."print (".$call.")";
   fwrite($myfile, $txt);
   fclose($myfile);
}
///// execute python3 file and return the result if any ///////////////
function exePython($fname, $userin){
  if($userin){
    $command = "python3 ".$fname." < input.txt";
  }else{
    $command = "python3 ".$fname;
  }
  exec($command, $outputC, $returnC);
  if($returnC==0 && sizeof($outputC) > 0){
      return $outputC[sizeof($outputC)-1];  // last line which is the answer
  }else{
    return "";
  }
}


$correct = $_POST['correct'];
$answer = $_POST['answer'];
$callstring = $_POST['call'];
$Correctfile = "correct.py";
$Answerfile = "answer.py";

$callfield = preg_split("/\r\n|\n|\r/", $callstring);
$call=$callfield[0];

/////////////// if there is user input for the program, write it to a file ////////////
$callfieldcount=count($callfield);
if($callfieldcount>1){
  $input="";
  for($i=1;$i<$callfieldcount;$i++){
    $input=$input.$callfield[$i]."\r\n";
  }
  $myfile = fopen("input.txt", "w") or die("Unable to open file!");
  $txt = $input;
  fwrite($myfile, $txt);
  fclose($myfile);
  $userinput=true;
}
//////////////////////////////////////////////////////////////////////////////////////////

///////////// execute correct answer to find desired result //////
writefile($Correctfile,$correct,$call);
$correctoutput= exePython($Correctfile, $userinput);
//////////////////////////////////////////////////////////////////

////////////// Testing the User Answer //////////////////////////////////////////////////
writefile($Answerfile,$answer,$call);
$answeroutput = exePython($Answerfile, $userinput);
$success=true;
$grade=10;
$comment="";
if($answeroutput!=""){
  if ( $answeroutput == $correctoutput){
      $grade=10;
      $comment=$comment."Correct Answer";    
  }else{
      //$comment=$comment."Answer Not Correct";
      $success=false;
  }
}else{
  $comment=$comment."Execute Error";
  $success=false;
}

if ($success==false){
    $command = "java regexp ".'"'.$call.'"'." ".$Answerfile." ".$Correctfile;
    exec($command, $outputJ, $returnJ);
    if(sizeof($outputJ)>0)
      $javaoutput=$outputJ[0];
    $answeroutput = exePython($Answerfile, $userinput);
    if($answeroutput!=""){
      if ( $answeroutput == $correctoutput){
         $comment=$comment."<br>Answer correct after function defenition fix";
         $grade--;
      }else{
         $comment=$comment."<br> Answer Executed, but WRONG<br>Code based on Similarity is :";
         $grade=$javaoutput;
         $success=false;
      }
    }
    else{
      $comment=$comment."<br>Code based on Similarity is :";
      $grade=$javaoutput;
      }                         
}

echo $comment."<br> Grade: ".$grade."/10";
//echo $answeroutput;
//echo $correctoutput;
//exec("rm ".$Answerfile, $output, $return);
//exec("rm ".$Correctfile, $output, $return);

}//elseif
}//ifisset
?>