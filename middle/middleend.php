<?php
/// Mateusz Stolarz, middleend.php  //
// December 11, 2017 Cs490 Project  //


//  Curl to back end     //

function curltobackend($query){
    $string2 = http_build_query($query);    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://afsaccess4.njit.edu/~ash32/CS490/BackendMain.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $string2);
    $response = curl_exec($ch);
    curl_close($ch);  
    return $response;
}

//     Write the python code into file      //

function writefile($filename,$content,$call){
   $myfile = fopen($filename, "w") or die("Unable to open file!");
   $txt = $content."\n"."polska=".$call."\n".'print ("\n")'."\n"."print( polska )";
   fwrite($myfile, $txt);
   fclose($myfile);
}

//       Execute python3 file and return the result if any      //

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

//      Write user input into file              //

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

//         Fixing function name      //

 function fixfunction($call1, $answer ){  
      $correctfname = substr($call1,0, strpos($call1,"("));
      $correctfname = trim($correctfname);
      $sindex = strpos($answer,"def ")+4;
      $eindex = strpos($answer,"(");
      $studentfname = substr($answer,$sindex, $eindex-$sindex);
      $studentfname = trim($studentfname);
      if($correctfname!=$studentfname){
        $wrongname= true;;
      }else{
        $wrongname=false;
      }
      $inside = substr($answer, $eindex+1, strpos($answer,")") - $eindex -1);
      $fixedcall ="def ".$correctfname."(".$inside."):";
      $endlineindex = strpos($answer,"\n");
      $answer =$fixedcall.substr($answer,$endlineindex,strlen($answer)-1);
      $ret[0] = $answer;
      $ret[1] = $wrongname;
      return $ret;
 }
 
 //            Check for Proper Parameters           //
 
 function paramcheck($correct, $answer ){  
      $sindex = strpos($answer,"def ")+4;
      $eindex = strpos($answer,"(");
      $aparams = substr($answer, $eindex+1, strpos($answer,")") - $eindex -1);
      $sindex = strpos($correct,"def ")+4;
      $eindex = strpos($correct,"(");
      $cparams = substr($correct, $eindex+1, strpos($answer,")") - $eindex -1);
      $aparams=preg_split("/,/", $aparams);
      $cparams=preg_split("/,/", $cparams);
      $asize=sizeof($aparams);
      $csize=sizeof($cparams);
      $incorrect=0;
      if($asize==$csize){
        for($i=0; $i<$asize;$i++){
          if(trim($aparams[$i])!=trim($cparams[$i])){
            $incorrect++;
          }
        }
      }
      return $incorrect;
 }




// If Grade Request grade otherwise Just forward  //

if(isset($_POST['request'])){
  $request= $_POST['request'];
  if($request!="SubmitGrading"){
        echo curltobackend($_POST);
  }elseif($request=="SubmitGrading"){
    //         inicialize arrays              //
    $comment="";                //comments
    $testcomments = array();    // array of comments in order
    $testgrades = array();      // array of grades    in order
    $qidarray = array();        // array of qids      in order
    $testgrade = 0;             // final test grade

    //       grab required information from Request      //
    $username=$_POST['username'];
    $paylo=$_POST['payload'];
    $payobj= json_decode($paylo, true); 
    $examID=$payobj["examID"]; 
 
    $Correctfile = $username."c.py";   // create files based of username
    $Answerfile = $username."a.py";
  
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


    /////////////////For each question Run grading loop////////////////
    $questions = sizeof($correctarray);
    for($q=0;$q<$questions;$q++){ 
      $comment=""; 
      /////////////////////////////Get point value per question/////////////////////
      $questionarr=$obj["questions"];                                                           
      $points = json_decode($questionarr[$q], true);          
      $maxPoints=$points["questionInfo"]["maxPoints"]; 
      $qidarray[]=$points["questionInfo"]["qid"];     // make array of qid for backend
      if($maxPoints==NULL || $maxPoints < 1){
        $maxPoints=10;
      }                                        
      /////////////////////////set each variable ///////////////////////////////////   
      $correct=$correctarray[$q];
      $callstring=$callarray[$q];
      $answer=$answerarray[$q];

      /////////////////// Prevent grading NULL elements//////////////////////////////
      if($correct==NULL || strlen($correct) < 3 ){
        $correct="nothing";
      }
      if($callstring==NULL  || strlen($callstring) < 3){
        $callstring="noting";
      }
      if($answer==NULL  || strlen($answer) < 3){
        $answer="nothing ";
      }
      
      $expectedanswer = array();
      $recivedanswer = array();
      $callfield = preg_split("/\r\n|\n|\r/", $callstring);
      $calltimes=count($callfield);
      $penelty=0;
      $ptPerMistake = 0.10*$maxPoints;  // 20%
      /////////////////// For each function call Loop /////////////////////////////////////////////////
      $mistake = true;     
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
        //////////////////     Ckeck the function call        //////////////////////////////////////////
        if($mistake){
                $temp = fixfunction($call1, $answer);
                if($temp[1]){
                  $comment=$comment."Incorrect function name has been used\t\t\t\t\t\t\t -$ptPerMistake pt<br>";
                  $penelty+=$ptPerMistake;
                }
                $answer = $temp[0]; 
                writefile($Answerfile,$answer,$call1);
                $incorrect = paramcheck($correct,$answer);
                if($incorrect>0){
                  $comment=$comment."$incorrect Incorrect parameter name(s) used\t\t\t\t\t\t\t -".$ptPerMistake." pt <br>";
                  $penelty+=$ptPerMistake;
                }
           $mistake = false;
        }

        ///////////////////// execute correct answer to find desired result ////////////////////////////
        writefile($Correctfile,$correct,$call1);
        $correctoutput= exePython($Correctfile, $userinput);
        $expectedanswer[]=$correctoutput;
        ////////////////////// Testing the User Answer //////////////////////////////////////////////////
        writefile($Answerfile,$answer,$call1);
        $answeroutput = exePython($Answerfile, $userinput);
        $success=true;

        if($answeroutput!=""){
          $recivedanswer[]=$answeroutput;
        }else{
          $success=false;
        }
        $outputJ=[];   
        if ($success==false){
          $command = "timeout 3s java grader ".'"'.$call1.'"'." ".$Answerfile." ".$Correctfile;
          exec($command, $outputJ, $returnJ);             
          $recivedanswer[]=" ";
          $comment=$comment."The code Was not executable\t\t\t\t\t\t\t\t-".($maxPoints-$penelty)."pt";
          for($e=0; $e<sizeof($outputJ);$e++){
               $comment=$comment."<br>".$outputJ[$e];
          }
          $penelty = $maxPoints;
          $comment=$comment."<br>";
          break;
                                  
        }
      }
      /////////////////////////////////////////////// at the end generate grade for executable code///////////////////////
      $grade=$maxPoints;
      $ptPerQ=($maxPoints-$penelty)/$calltimes;
      if($success){
        $ccount=0;
        for($i=0; $i<count($expectedanswer);$i++){
           $output=",\tIncorrect\t -$ptPerQ pt <br>";
    
        if ( $expectedanswer[$i] == $recivedanswer[$i] ){
          $output=",\tCorrect  \t +$ptPerQ pt<br>";  
          $ccount++; 
        }
         $comment= $comment."Call: ".$callfield[$i]." Expected:\t".$expectedanswer[$i].",\tReceived:\t".$recivedanswer[$i].$output; 
        }
      $grade=($ccount/count($expectedanswer))*($maxPoints-$penelty);  
      if($grade<0){
        $grade=0;
      }
      }else{
       $grade=$grade-$penelty;
      }

      $comment = str_replace("'", "\"", $comment);
      $comment = str_replace('"', '\"', $comment);
      $testcomments[]=$comment; // one comment per question
      $testgrades[]=$grade;     // one grade per question
      $testgrade+=$grade;       // final test grade
}   

    //$testcomments = array("hi" ,"hi");
    ///////////////////////////////////////////////////////////////////////
    ///////  At the end, Send to database to Store                      ///
    ///////////////////////////////////////////////////////////////////////
    $jsonForword = new \stdClass();
    $jsonForword->comments = $testcomments;
    $jsonForword->scores = $testgrades;
    $jsonForword->examID = $examID;
    $jsonForword->qids = $qidarray;
    $json = json_encode($jsonForword);
    $post= ['username'=>$username ,'request'=>'SubmitGradedExam', 'payload'=>$json];

    echo curltobackend($post);

    // delete the files after we are done
    //exec("rm ".$Answerfile, $output, $return);
    //exec("rm ".$Correctfile, $output, $return);

  }//elseif
}//ifisset
?>