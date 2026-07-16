<?php

// include 'connection.php';

class Doctor{
    
    private $db;

    // public function __construct() {
    //       $this->db= mysqli_connect("103.48.119.244", "lucenttechbd_ZH", "r+5x&([.KrKA", "lucenttechbd_db");
    //   // $con= mysqli_connect($host, $user, $password, $database, $port, $socket)
    //     $this->db->query("SET CHARACTER SET utf8");
      
    //      if(mysqli_connect_errno()){
    //       echo "Could Not Connect to Database".  mysql_errno();
    //       exit();      
    //  }
    // }
    
        public function __construct() {
            
            $mysqli = new mysqli("lucenttechbd.com","lucenttechbd@localhost","r+5x&([.KrKA","lucenttechbd_db");

            // Check connection
            if ($mysqli -> connect_errno) {
              echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
              exit();
}
    }

    public function doctor_registration($name,$designation,$email,$department_id,$phone,$location_id,$password,$reg_no,$description,$gender,$area_id,$hospital_id,$address,$photo)
          {
        //print_r($data);
      /// echo  $query="INSERT INTO doctor_info VALUES(null,'$data[department_id]','$data[location_id]','$data[hospital_id]','$data[name]','$data[email]','$data[phone]','$data[gender]','$data[designation]','$data[degree]','$data[description]','$data[reg_no]','0','$data[image]','$data[password]')";

         $query="INSERT INTO doctor_info VALUES('','$department_id','$area_id','$hospital_id','$name','$email','$phone','$gender','$designation','$description','','$reg_no','500','$photo','$password','','')";
        $result= mysqli_query($this->db, $query);
        return $result;

    }


    public function doctor_login($data){
       $query="SELECT * FROM investors_info WHERE email='$data[email]' AND password='$data[password]' ";
           // print_r($query);
    $result= mysqli_query($this->db, $query);
        return $result;

    }
    
    public function changePassword($id,$password) {
           $query="UPDATE doctor_info SET password='$password' WHERE doctor_id='$id'";

    $result= mysqli_query($this->db, $query);
        return $result;
    }
}

