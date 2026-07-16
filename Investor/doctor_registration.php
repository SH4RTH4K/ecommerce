<?php

// include 'connection.php';

class Doctor{
    
    private $db;

    public function __construct() {
        
        
//         DB_HOST=127.0.0.1
// DB_PORT=3306
// DB_DATABASE=lucenttechbd_db
// DB_USERNAME=lucenttechbd_db
// DB_PASSWORD=1j{.DPS_E^u7


$servername = "127.0.0.1";
$username = "lucenttechbd_db";
$password = "1j{.DPS_E^u7";
$database = "lucenttechbd_db";

// $servername = "lucenttechbd.com:2083";
// $username = "lucenttechbd_ZH";
// $password = "r+5x&([.KrKA";
// $database = "lucenttechbd_db";
// Create connection
 
// $conn = mysqli_connect($servername, $username, $password, $database);
 
// // Check connection
 
// if (!$conn) {
 
//     die("Connection failed: " . mysqli_connect_error());
 
// }
// //echo "Connected successfully";
// mysqli_close($conn);




          //echo "Could Not Connect to Database";
     $this->db= mysqli_connect($servername, $username, $password, $database);
      // $con= mysqli_connect($host, $user, $password, $database, $port, $socket)
        $this->db->query("SET CHARACTER SET utf8");
      
        //echo "Could Not Connect to Database";
         if(mysqli_connect_errno()){
          echo "Could Not Connect to Database".  mysql_errno();
          exit();      
     }
    }

    // public function doctor_registration($name,$designation,$email,$department_id,$phone,$location_id,$password,$reg_no,$description,$gender,$area_id,$hospital_id,$address,$photo)
    //       {
    //     //print_r($data);
    //   /// echo  $query="INSERT INTO doctor_info VALUES(null,'$data[department_id]','$data[location_id]','$data[hospital_id]','$data[name]','$data[email]','$data[phone]','$data[gender]','$data[designation]','$data[degree]','$data[description]','$data[reg_no]','0','$data[image]','$data[password]')";

    //      $query="INSERT INTO doctor_info VALUES('','$department_id','$area_id','$hospital_id','$name','$email','$phone','$gender','$designation','$description','','$reg_no','500','$photo','$password','','')";
    //     $result= mysqli_query($this->db, $query);
    //     return $result;

    // }


    public function doctor_login($data){
       $query="SELECT * FROM investors_info WHERE email='$data[email]' AND password='$data[password]' ";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;

    }
    
       public function login_admin($data){
       $query="SELECT * FROM investor_admin WHERE username='$data[username]' AND password=MD5('$data[password]') ";
           //print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;

    }
    
     public function get_investor(){
       $query="SELECT * FROM investors_info WHERE IsActive = 1 ";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;
    }
    
           public function save_payment($data){
       $query="INSERT INTO `payment_history` (`UserId`, `CreatedDate`, `Title`, `Type`, `Amount`) 
            VALUES ('$data[id]', '$data[createdDate]', '$data[title]', '$data[type]', '$data[amount]')";
           print_r($query);
    $result= mysqli_query($this->db, $query);
      
        return $result;

    }
    
         public function get_investor_payment($id){
       $query="SELECT * FROM payment_history WHERE UserId = '$id' ORDER BY `payment_history`.`CreatedDate` DESC";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;
    }
    
             public function delete_investor_payment($id){
       $query="DELETE FROM payment_history WHERE id = '$id' ";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;
    }
    
    
       public function doctor_resetpassword($data){
       $query="UPDATE `investors_info` set `password` ='$data[newpassword]' WHERE email='$data[email]' AND password='$data[oldpassword]' ";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;
    }
    
    
    // public function changePassword($id,$password) {
    //       $query="UPDATE doctor_info SET password='$password' WHERE doctor_id='$id'";

    // $result= mysqli_query($this->db, $query);
    //     return $result;
    // }
}

