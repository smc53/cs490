///////////////////////////////////////////////
//   SyntaxParse.java                        //
//   Mateusz Stolarz                         //
//   UCID: mss86                             //
///////////////////////////////////////////////
import java.util.regex.*;
import java.util.function.*;  
import java.io.*;
import java.util.*;
public class SyntaxParse{
   static int i; // String position
   static String[] s = new String [50];
   static int len=s.length;
   static String comment="";
   static int Line=0;
   static String[] declared = new String[200];
   static String[] reserved = {"for","and","or","if","elif","else","while","def","return","not"};
   static int declaredlen=0;
      
////////////////////////////////////////////////////////////
//          Set of Recursive Parsing Methods              //
////////////////////////////////////////////////////////////
  public static boolean Input(){ // cast that return a number
      if(i<len&&s[i].equals("input")){
           i++;
           if(i<len&&s[i].equals("(")){
               i++;
               if(Stringexp()||ExpressionSign()){
                  if(i<len&&s[i].equals(")")){
                     i++;
                     if(Splus()){
                        return true;
                     }
                     else return true;
                  }    
               }
           }
     }
   return false;
   }
   public static boolean CastN(){ // cast that return a number
      if(i<len&&(s[i].equals("int") || s[i].equals("float")||s[i].equals("len"))){
           i++;
           if(i<len&&s[i].equals("(")){
               i++;
               if(Stringexp()||ExpressionSign()){
                  if(i<len&&s[i].equals(")")){
                     i++;
                     if(Exp1()){
                        return true;
                     }
                     else return true;
                  }    
               }
           }
     }
   return false;
   }
   public static boolean CastS(){
      if(i<len&&s[i].equals("string")){
         i++;
         if(i<len&&s[i].equals("(")){
               i++;
               if(ExpressionSign()){
                  if(i<len&&s[i].equals(")")){
                     i++;
                     if(Splus()){
                        return true;
                     }
                     else return true;
                  }    
               }
           }
      }
   return false;
   }
   public static boolean Stringexp(){
      if(String()|| Name()||CastS()||Input()){
            int I=i;
            if(Splus()){
                  return true;
            }else if(Stimes()){
                  return true;
            }  
            else {
            i=I;
            return true;
            }
         }       
   return false;
   }
   public static boolean Splus(){
      if(i<len&&s[i].equals("+")){
               i++;
               if(Stringexp()){
                  return true;
               } 
      }  
   return false;
   }
   public static boolean Stimes(){
         if(i<len&&s[i].equals("*")){
               i++;
               if(Number()){
                  if(Splus()){
                     return true;
                  }
               }
            }
   return false;
   }
   public static boolean Function(){
      if(i<len&&s[i].equals("def")){
         i++;
         if(NameD()){
            if(i<len&&s[i].equals("(")){
               i++;
               if(Finput()){
                  if(i<len&&s[i].equals(")")){
                     i++;
                     if(i<len&&s[i].equals(":")){
                        i++;
                        return true;
                     }
                  }
               }
               else if(i<len&&s[i].equals(")")){
                  i++;
                  if(i<len&&s[i].equals(":")){
                        i++;
                        return true;
                     }
               }
            }
         }
       }       
   return false;     
   }
   public static boolean Return(){
      if(i<len&& Pattern.matches("return", s[i])){
            i++;
            int I=i;
            if(ExpressionSign()){
               return true;
            }
            i=I;
            if(Stringexp()){
               return true;
            }
            i=I;
            if(Condition()){
               return true;
            }
            return true;
         }       
   return false;     
   }
   public static boolean ForLoop(){
      if(i<len&&s[i].equals("for")){
         i++;
         if(NameD()){
            if(i<len&&s[i].equals("in")){
               i++;
               if(i<len&&s[i].equals("range")){
                  i++;
                  if(i<len&&s[i].equals("(")){
                     i++;
                     if(ExpressionSign()){
                        
                        if(i<len&&s[i].equals(",")){
                           i++;
                           if(ExpressionSign()){
                             if(i<len&&s[i].equals(",")){
                                 i++;
                                 if(ExpressionSign()){
                                 }
                             } 
                           }  

                        }
                        if(i<len&&s[i].equals(")")){
                           i++;
                           if(i<len&&s[i].equals(":")){
                              i++;
                              return true;
                           }
                        } 
                      }    
                   }
               }
               else if(ExpressionSign()){
                  if(i<len&&s[i].equals(":")){
                     i++;
                     return true;
                  }
               }  
            }
         }
      }
   return false;
   }
   public static boolean Increment(){
      if(Name()){
          if(i<len&&(s[i].equals("+")||s[i].equals("-") )){
               i++;
               if(i<len&&s[i].equals("=")){
                  i++;
                  if(Expression()){
                     return true;
                  }
               }
          }    
      }
   return false;
   }
   public static boolean WhileLoop(){  
      if(i<len&&s[i].equals("while")){
            i++;
            if(Condition()){
               if(i<len&&s[i].equals(":")){
                  i++;
                  return true;
               }
            }
         }       
   return false;     
   }
   
   public static boolean Condition(){
      int I=i;
      if(i<len&&(s[i].equals("True")||s[i].equals("False") )){
         i++;
         if(Con1()){
            if(Condition()){
               return true;
            }else return false;
         }
         else return true;
      }
      else if(ExpressionSign()||Stringexp()){  // modified today added || Stringexp()
         if(Con1()){
            if(Condition()){
               return true;
            }else return false;
         }
       return true;
      }
      i=I;
      if(i<len&&s[i].equals("(")){
         i++;
         if(Condition()){
            if(i<len&&s[i].equals(")")){
               i++;
               if(Con1()){
                  if(Condition()){
                     return true;
                  }
               }
               return true;
            }
         }
      }
   return false;     
   }
   public static boolean Con1(){
      if(i<len&&s[i].equals("not")){
         i++;
         if(Con1()){
            return true;
         }else return false;
      }
      if(i<len&&(s[i].equals("and")||s[i].equals("or")||s[i].equals("in"))){
         i++;
         return true;
      }
      if(i<len&&(s[i].equals("<")|| s[i].equals(">"))){
            i++;
            if(i<len&&s[i].equals("=")){
               i++;
               return true;
            }
            return true;
         }
      if(i<len&&(s[i].equals("!")|| s[i].equals("="))){
            i++;
            if(i<len&&s[i].equals("=")){
               i++;
               return true;
            }
         }    
   return false;
   }
   public static boolean If(){  
      if(i<len&&(s[i].equals("if")||s[i].equals("elif"))){
            i++;
            if(Condition()){
               if(i<len&&s[i].equals(":")){
                  i++;
                  return true;
               }
            }
         }       
   return false;     
   }
   public static boolean Else(){
      if(i<len&&s[i].equals("else")){
         i++;
         if(i<len&&s[i].equals(":")){
                  i++;
                  return true;
               }
      }
   return false;
   }
   public static boolean NameD(){  // add name to the known list
      if(i<len&& Pattern.matches("[a-zA-Z_]+[a-zA-Z0-9_]*", s[i])&&inreserve(s[i])){
            declare(s[i]);
            i++;
            if(i<len&&s[i].equals("[")){
               i++;
               if(ExpressionSign()){
                  if(i<len&&s[i].equals("]")){
                     i++;
                     return true;
                  }
               }  
            }
            else return true;
         }       
   return false;     
   }
   static int cnt;
   public static boolean Name(){  // check if name was declared
      if(i<len&& Pattern.matches("[a-zA-Z_]+[a-zA-Z0-9_]*", s[i])&&indeclare(s[i])&&inreserve(s[i])){
            i++;
            if(i<len&&s[i].equals("[")){
               i++;
               if(Findex()){
                  cnt=0;
                  if(i<len&&s[i].equals("]")){
                     i++;
                     return true;
                  }
               }  
            }
            else return true;
         }       
   return false;     
   }
   public static boolean Findex(){
      if(cnt++>1){
       cnt=0;
       return false;
       }
      if(i<len&&s[i].equals(":")){
         i++;
          if(ExpressionSign()){
             if(Findex()){
                  return true;
               }
            else return true;  
          }
          else return true;
         }
      else if (ExpressionSign()){
         if(i<len&&s[i].equals(":")){
            i++;
            if(ExpressionSign()){
               if(Findex()){
                  return true;
               }
               else return true;
            }else return true;
         }else return true; 
      }       
   return false;     
   }
   
   public static boolean Number(){
      if(i<len&& Pattern.matches("[0-9]+", s[i])){
            i++;
            return true;
         }       
   return false;     
   }
   public static boolean Stringhelper(){
      if(i<len&& Pattern.matches(".*", s[i])){
         i++;
         return true;
      }
   return false;
   }
   public static boolean String(){
      if(i<len&& (Pattern.matches("[\"]{1}(.*[ ]*)*[\"]", s[i])|| Pattern.matches("[']{1}(.*[ ]*)*[']", s[i]) )){
            i++;
            return true;
         } 
       else if(i<len &&Pattern.matches("[\"'].*", s[i])){
         i++;
         if(i<len &&Pattern.matches(".*[\"']", s[i])){
            i++;
            return true;
         }
         else if(Stringhelper()){
            if(i<len &&Pattern.matches(".*[\"']", s[i])){
               i++;
               return true;
            }
         }
       }      
   return false;     
   }
   
   public static boolean Finput(){
      if(NameD()){
            if(i<len&&s[i].equals(",")){
               i++;
               if(Finput()){
                  return true;
               }
            }
            return true;
         }       
   return false;     
   }
/////////////////////////////////////////////////////Expression //////////////////////////////////////////////
   public static boolean ExpressionSign(){
     // if(Stringexp()){
      //   return true;   
     // }
      if(i<len && Pattern.matches("[\\+\\-]", s[i])){
            i++;
            if(Expression()){
               return true;
            }
      }
      else if (Expression()){
         return true;
      }
   return false;
   }   
   public static boolean Expression(){
      if(CastN()||CastS()){
         return true;
      }
      else if(i<len&&s[i].equals("(")){
         i++;
         if(ExpressionSign()){
            if(i<len&&s[i].equals(")")){
               i++;
               int I=i;
               if(Exp1()){
                  return true;
               }
               i=I;
               return true;
            }
         }
      }    
      else if(Number()||Name()){
         int I=i;
         if(Exp1()){
            return true;
         }
         else{
          i=I;
          return true; 
          }   
      }  
   return false;     
   }
   public static boolean Exp1(){
         if(i<len && Pattern.matches("[\\*\\+\\-\\/]", s[i])){
            i++;
            if(ExpressionSign()){
               if(i<len && Pattern.matches("[\\+\\-\\/\\*]", s[i])){
                  i++;
                  if(ExpressionSign()){
                     return true;
                  }   
               }else return true;
            }
         } 
   return false;
   }
   public static boolean List(){
      if(i<len&&s[i].equals("[")){
         i++;
         int I = i;
         if(Listinput()){
            if(i<len&&s[i].equals("]")){
               i++;
               return true;
            }
         i=I;
         }else if(i<len&&s[i].equals("]")){
               i++;
               return true;
            }
      } 
   return false;
   }
   public static boolean Listinput(){ 
      if(ExpressionSign()){
         if(i<len&&s[i].equals(",")){
            i++;
            if(Listinput()){
               return true;
            }else return true;
         }else return true;
      }
   return false;
   }
   public static boolean Assigment(){ 
      if(NameD()){
         if(i<len&&s[i].equals("=")){
            i++;
            int I = i;
            if(ExpressionSign()&&i==len){
               return true;
            }
            i=I;
            if(List()&&i==len){
               return true;
            }
            i=I;
            if(Stringexp()&&i==len){
               return true;
            }
            i=I;
            if(Condition()){
               return true;
            }     
         }
      }         
   return false;
   }
   public static void missing(String mis){
      comment+="Missing/Unreachable "+mis+" at line "+Line+"\n";
   }
///// save variable names into known array //////////   
   static void declare(String name){
      if(!indeclare(name))
            declared[declaredlen++]=name;
         }
/// checks if variable was declared privisiously ////
   static boolean indeclare(String name){
          for(int i=0;i<declaredlen;i++){
               if(name.equals(declared[i])){
                  return true;
                }   
            }
       //System.out.println(name+" was not declared");
       return false;  
   }
/// check if the name is a reserved word/////////////////////////////////////
   static boolean inreserve(String name){
          for(int i=0;i<reserved.length;i++){
               if(name.equals(reserved[i])){
                  return false;
                }   
            }
       return true;
   }
   public static String Syntax(String[] Lines, int Asize){
      String comment="";    
      String sample;

     for(int l=0;l<Asize;l++){
         sample=Lines[l];
         String b[] = sample.split("([ ]|(?<=[*)(:+-/=\\[\\]<>!])|(?=[*)(:+-/=\\[\\]<>!])|[ ]|[\t])"); 
         len=0;
         for(int j=0;j<b.length;j++){
            if(!b[j].equals("")){
               s[len++]=b[j]; 
            }  
         }
         i=0; 
         if(Function()&&i==len){
            continue;
            }
         i=0;
         if(Return()&&i==len){
            continue;
            }
         i=0;
         if(ForLoop()&&i==len){
            continue;
            }
         i=0;
         if(WhileLoop()&&i==len){
            continue;
            }
         i=0;
         if(If()&&i==len){
            continue;
            }
         i=0;
         if(NameD()&&i==len){
           continue;
            }
         i=0;
         if(Increment()&&i==len){
            continue;
            }
         i=0;
         if(Assigment()&&i==len){
            continue;
            }
         if(Else()&&i==len){
            continue;
            }
         comment+="Syntax error on line "+(l+1)+"\n";
       }
    return comment;
    }

} // end of class