<?php

    /*
    This is the code which is called by the arduino.
    
    It takes in the the RFID UID from the card and the scanner number
    
    This version takes in a HEX code 
    
    rearranges the order of the bytes and converts it into decimal
    
    
    It then searches the database to see if it reciognises the UID
    
        If so, then record the fact that person X has scanned at machine Y
        
        If not, then we assume that this card is not recognised.
        We could then have a suitable message on the OLED to say, please see Reception 
        There could be a stack of cards and a card reader attached to a pc. The receptionist could then 
        scan the blank card to read the code, add it to the database and attach a sticker with the person's name on it.
        
        This is slightly complicated by the fact that the Arduino scans a HEX code, whilst the USB scanner grabs a decimal code.
        The decimal number, when converted to HEX is the reverse of the Arduino code.
        
        Hence, we need to get the code from the Arduino, swap the bytes around and compare it with a HEX version of the stored code.
        The extra fly in the ointment occurs if the USB scanner records a leading zero
        
        as  760967571 - gives the hex value 2d5b7193 (which is the reverse version of the arduino scan for this card)
        but 0760967571 - produces 1f0 (which is not)


    This is called by every Arduino RFID box.
    However, as we need to do spcific things for particular boxes.
    
    e.g. Matron's Scanner - Links to her System
    
    Sign In / Sign Out - Links to the Sign-in/out db
    
    Others - just record that a pupil is in a particular place.
    This could be used to record when a pupil goes to a Music lesson, for example.
    

        
    */

$ID="";

// Set up the basics
$Scanner_ID = $User_ID = "";

$t=time();
$time = date("H:i:s",$t);
$date = date("Y-m-d",$t);    
require_once 'config.php';

// Get the keys from the Arduino:

    if (isset($_GET["Scanner"])){
        
    
        $Scanner_ID = test_input($_GET["Scanner"]);
    
    }
    
    if (isset($_GET["UID"])){
    
        $User_RFID_Key = test_input($_GET["UID"]);
    }
    
    $User_RFID_Key = reverse_bytes($User_RFID_Key);
    
    
    
    // We shall add the extra feature later...similar to the code in DT
    $person = who_is_this($User_RFID_Key);
    
    
    
    if ($person != "Unknown"){
        
        echo $person; // Their name will be dislayed on the OLED of the scanner
    
        // What is their ID?
        
        $pupil_ID = pupil_id($User_RFID_Key);
        
        // Which scanner are we using?
        
        switch ($Scanner_ID) {
  
            case 1:
                
                // Signing In at Reception

                $sql = "INSERT INTO `Sign_in_history` 
                
                        (pupil_id,date,time)
        
                        VALUES ('".$pupil_ID."','".$date."','".$time."')";

            
                
 
                if ($link->query($sql) === TRUE) {
                    
                }  

                
                store_scan($User_RFID_Key, $Scanner_ID,"P");
                break;
  
            case 2:
                // Signing Out at Art / Science staircase

                $sql = "INSERT INTO `Sign_out_history` 
                
                        (pupil_id,date,time,reason,authorised)
        
                        VALUES ('".$pupil_ID."','".$date."','".$time."','Scan@Science-Stairs','0')";

                
                
 
                if ($link->query($sql) === TRUE) {
                    
                }  


                store_scan($User_RFID_Key, $Scanner_ID,"P");
                break;
  
            case 3:
                // Matron

                store_scan($User_RFID_Key, $Scanner_ID,"P");
                
                // Check to see if Matron is allowing visitors...
                
                $sql = 'SELECT * FROM `matron_meta_data`';
    
                $result = mysqli_query($link, $sql);

                if(mysqli_num_rows($result) > 0){
    
                    while($row = mysqli_fetch_array($result)){
        
                        $available=$row['Available'];
                        $message=$row['Message'];
                    }

                }



                // Yes? 

                
                if ($available == "Y"){
    
                    // Display a message on her tablet to let person know...
        
                    $sql = 'UPDATE `matron_meta_data` SET New_pupils="Y", Changed="Y", Message = "'.$person.'", RFID="Y" ';
    
                    $result = mysqli_query($link, $sql);
            
                    // Add name to her list
                
                    $sql = "INSERT INTO `Matron_Visits` 
                
                            (Pupil_ID,Date,Time_In)
        
                            VALUES ('".$pupil_ID."','".$date."','".$time."')";

                    $result = mysqli_query($link, $sql);
            
                
                }
                
                break;
  
  
  
            case 4:
                // Signing Out at Reception
                
            
                $sql = "INSERT INTO `Sign_out_history` 
                
                        (pupil_id,date,time,reason,authorised)
        
                        VALUES ('".$pupil_ID."','".$date."','".$time."','Scan@Reception','0')";

                
                
 
                if ($link->query($sql) === TRUE) {
                    
                }  


                store_scan($User_RFID_Key, $Scanner_ID,"P");
                break;
                
// This will need some discussion...

         case 5:
        
                // Signing in or out at Stobo
                store_scan($User_RFID_Key, $Scanner_ID,"P");
                break;
                  
        
        case 6:
        
            // falling through as no break - so this is effectively if 6 OR 7
            
        case 7:
        
                // Music Lesson N1
                
                store_scan($User_RFID_Key, $Scanner_ID,"P");
            
            // If they have tapped the Music scanner, then they have not left the school - so remove this sign out.
            /*
            


                $sql = "DELETE * FROM `Sign_out_history` 
                
                        WHERE pupil_id LIKE $pupil_ID AND date LIKE '$date'
                        
                        AND Time_Stamp >= NOW() - INTERVAL 15 MINUTE 
                    
                        ";
                
                
 
                if ($link->query($sql) === TRUE) {
                    
                }  

            */
            
                
                /*
                
                The plan is to double tap the scanner.
                When a person arrives for their lesson, they tap.
            
                The system records their sign in time
                
                When a person leaves the lesson, they tap.
                
                The system records their sign out time
                
                
                Problems 
                ========
                
                    Double saving - switch bounce?
                    Need to have log to determine if sign in was more than 5 minutes ago, hence recording a sign out.
                    
                
                */
                
                // If they have signed in..
                
                $sql = "SELECT Time_In FROM `Music_Lessons_Scans` WHERE Date LIKE '$date'";
    
                $result = mysqli_query($link, $sql);

                if(mysqli_num_rows($result) > 0){

                
                // Record signing out
                $sql = "UPDATE `Music_Lessons_Scans` SET  Time_Out = '$time' WHERE
                
                Pupil_ID = '$pupil_ID' AND Date='$date'";
                
                }
                else
                {
                // Record signing in
                $sql = "INSERT INTO `Music_Lessons_Scans` 
                
                            (Pupil_ID,Date,Time_In,Scanner)
        
                            VALUES ('".$pupil_ID."','".$date."','".$time."','$Scanner_ID')";

                  }


                // Store the data
                $result = mysqli_query($link, $sql);
            
                
                
                break;
  
  
        
                // Signing in or out at N1
                
        // case 7:
        
                // Signing in or out at N2
                
                
                
                
                
            // If not a special scanner, then just store the fact that a person has scanned their card.
            default:
                store_scan($User_RFID_Key, $Scanner_ID, "P");
 
}
    
        
    }
    else
    {

      echo "Unknown\nUser";
      
    }
    
function reverse_bytes($rfid)
{
    // This takes in the HEXadecimal bytes and changes the order
    /*
    e.g. Arduino reads in: A2E4B6
    
    55EC021B
    
    this should spit out:  B6E4A2
    
    
    substr(string,start,length)
    */
    
    $output = "";
    $index=strlen($rfid);
    while ($index >= 0 )
    {
        $character = substr($rfid,$index,2);
        
        $output = $output.$character;
        $index = $index - 2;
        
    }
    
    
    return $output;
    
}
    
function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
}
    
function store_scan($User_RFID_Key, $Scanner_ID,$Tag_Type)
{
    global $link;
    
    
$t=time();
$time = date("H:i:s",$t);
$date = date("Y-m-d",$t);  

     $User_RFID_Key = hexdec($User_RFID_Key);
     
    if (strlen($User_RFID_Key)==9){
        // Needs a leading zero
        $User_RFID_Key = "0".$User_RFID_Key;
        
    }
    
     // $Scan_Time=date("Y-m-d H:i:s",time());
    
    $sql = "INSERT INTO Scanned_Attendance (RFID,Scanner,Scan_Date,Scan_Time,Tag_Type) VALUES ($User_RFID_Key,$Scanner_ID,'$date','$time','$Tag_Type')";
    
    
    if($result = mysqli_query($link, $sql)){
        
        $Message = "Details stored in database";
    }
    else
    {
        $Message = "Problem storing details";
    }
    
    return $Message;
    
}

function who_is_this($User_RFID_Key)
{

    // This function takes in the scanned RFID and returns the name of the pupil.
    // This could be used to display a welcome message on the OLED of the scanner.
    
    global $link;
    $User_RFID_Key = hexdec($User_RFID_Key);
    
    /* If using a Green tag, there will be a leading zero
    so add a zero to the left hand side if the length of teh code is 9
    */
    
    if (strlen($User_RFID_Key)==9){
        // Needs a leading zero
        $User_RFID_Key = "0".$User_RFID_Key;
        
    }
    
    $name = "Unknown";
    
    $sql = 'SELECT * FROM `RFID_Pupil` JOIN Pupils ON RFID_Pupil.Pupil_ID = Pupils.ID WHERE RFID_Pupil.RFID_Code like "'.$User_RFID_Key.'"';

         if($result = mysqli_query($link, $sql)){
            while($row = mysqli_fetch_array($result)){
          
                $name = $row['Forename']."\n".$row['Surname'];
            
            }
         }
         else
         {
             $name = "Unknown User?";
             
             // Perhaps it is a member of staff? or a special card?
             
         }
         
    return $name;
}


function pupil_id($User_RFID_Key)
{

    // This function takes in the scanned RFID and returns the name of the pupil.
    // This could be used to display a welcome message on the OLED of the scanner.
    
    global $link;
    $User_RFID_Key = hexdec($User_RFID_Key);
    
    if (strlen($User_RFID_Key)==9){
        // Needs a leading zero
        $User_RFID_Key = "0".$User_RFID_Key;
        
    }
    $name = "Unknown";
    
    $sql = 'SELECT  Pupil_ID FROM `RFID_Pupil` WHERE RFID_Pupil.RFID_Code like "'.$User_RFID_Key.'"';

         if($result = mysqli_query($link, $sql)){
            while($row = mysqli_fetch_array($result)){
          
                $name = $row['Pupil_ID'];
            
            }
         }
         else
         {
             $name = "Unknown User?";
         }
         
    return $name;
}




?>