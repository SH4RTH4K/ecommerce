<?php

//include '../database/connection.php';

class User{
    
    private $db;

    public function __construct() {
               $this->db= mysqli_connect("lucenttechbd.com", "lucenttechbd_ZH", "r+5x&([.KrKA", "lucenttechbd_db");
               // $con= mysqli_connect($host, $user, $password, $database, $port, $socket)
        $this->db->query("SET CHARACTER SET utf8");
      
         if(mysqli_connect_errno()){
           echo "Could Not Connect to Database".  mysql_errno();
           exit();      
     }
    }

    public function user_registration($name,$email,$password,$sex,$address,$phone)
          {
       
        echo $query="INSERT INTO user_login VALUES('','$name','$sex','$email','$phone','$password')";
        $result= mysqli_query($this->db, $query);
        return $result;

    }

public function appointment($name,$date,$doctor_id,$user_id,$phone)
          {
       
        echo $query="INSERT INTO appointment VALUES('','$date','$doctor_id','$user_id','$name','$phone')";
        $result= mysqli_query($this->db, $query);
        return $result;

    }
    public function user_login($data){
    echo   $query="SELECT * FROM user_login WHERE email='$data[email]' AND password='$data[password]' ";

    $result= mysqli_query($this->db, $query);
        return $result;

    }
    
    

public function cancelAppointment($id) {
        $query="DELETE from appointment WHERE id='$id'";
         $result= mysqli_query($this->db, $query);
        return $result;
              
    }



    public function doctor_info_by_id($id){
         $query="SELECT * FROM doctor_info WHERE doctor_id='$id'";
         $result= mysqli_query($this->db, $query);
         return $result;

     }
     
      public function getSchedule($id){
         $query="SELECT * FROM schedule WHERE doctor_id='$id'";
         $result= mysqli_query($this->db, $query);
         return $result;

     }

       public function getPaymentHistoryByUserId($id){
         $query=" SELECT * FROM `payment_history` WHERE  UserId ='$id'";
         //print_r ($query);
         $result= mysqli_query($this->db, $query);
         return $result;

     }
     
    
}


