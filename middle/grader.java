///////////////////////////////////////////////
//   Mateusz Stolarz                         //
//   UCID: mss86                             //
//   grader.java                             //
///////////////////////////////////////////////
import java.util.regex.*;
import java.io.*;
import java.util.*;
class grader{
   static int MAXSIZE=30;
   static int Grade=10;
   static String Comment="";
   public static void main(String[] args) throws IOException 
   {
   String call=args[0];     // call
   String filename=args[1]; // answer filename
   String filecorrect=args[2]; // correct answer file
   
   String[] Aarray = new String[MAXSIZE];
   String[] Carray = new String[MAXSIZE];         
         /////////////////////////////////////Read Answer into Array/////////////////////////////////     
         int[] s= new int[2];
         Aarray=readfile(filename,s);
         int Asize=s[0];
         //////////////// check line by line syntax /////////////////////////////////////////
          SyntaxParse parser= new SyntaxParse();
          Comment = parser.Syntax(Aarray,Asize-3); // subtract 3 for the execution code
       
         String l1=Aarray[0];
         boolean found = l1.indexOf('(') !=-1? true: false; 
         if (!found)
            System.exit(1);
         //////// cheacking if correct name was used /////////////////////
         String correct= call.substring(0,call.indexOf('('));  
         String answer= l1.substring(l1.indexOf("def ")+4,l1.indexOf('('));   
         if(!correct.equals(answer)){
            Grade--;
            Comment+="bad function name";
            }           
         String inside = l1.substring(l1.indexOf('(')+1,l1.indexOf(')'));     
         String call1 = call.substring(0,call.indexOf('(')+1) + inside + call.substring(call.indexOf(')'),call.length());
         Aarray[0]="def "+call1+":";
         //////////// Write to file  ////////////////////////        
         writefile(filename,Asize,Aarray);
         ////////////////////////////////////////////////////////////////////////////////////// 
         System.out.println(Comment);
       System.exit(0);                   
   }//main
   
   
   
  ///////////Read content of file into array ////////////////////////////////
   static String[] readfile(String filename,int[] s){
         try {
            int size=0;
            String[] array = new String[MAXSIZE];
			   BufferedReader br = new BufferedReader(new FileReader(filename));
			   String Line;
			   while ((Line = br.readLine()) != null) {
             if (Line.length()>0)
				     array[size++]=Line;
			   }
          br.close();
          s[0]=size;
          return array;
          } catch (IOException e) { 
			   e.printStackTrace();
            System.exit(1);   
          }
   return null;
   }
 ///////////////////// write content from array into file /////////////////////  
   static boolean writefile(String filename,int Asize, String[] array){    
         try {
            FileWriter writer = new FileWriter(filename, false);
            BufferedWriter buffered = new BufferedWriter(writer);
            for(int i=0; i<Asize;i++){
               buffered.write(array[i]);
               buffered.newLine();
               }
            buffered.close();
            return true;
        } catch (IOException e1) {
            e1.printStackTrace();
        }
     return false;
     }
   static void printarray(String[] array,int len){
      for(int i = 0 ; i< len;i++)
         System.out.println(array[i]);
   } 
}// class