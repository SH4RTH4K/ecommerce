<?php 

 session_start();
session_unset();
  ?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Fontawesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   </head>
<body>
  <div class="container">

    <div class="forms">
        <div class="form-content">
          <div class="login-form">
            <div class="title">Login</div>
            <?PHP 
                       
                           include('doctor_registration.php');
                          
                           $doctorLogin=new Doctor();
                         
                           if(isset($_POST['submit'])){
                          //if($_SERVER['REQUEST_METHOD']=='POST'){
                              extract($_POST);
                             // print_r($_POST);

                              $login = $doctorLogin->doctor_resetpassword($_POST);
                              //print_r($login);
                              $row=mysqli_fetch_array($login);
                              //print_r($row);
                              
                            //   echo("<script>location.href = 'login.php ';</script>");

                              if (mysqli_num_rows($login) == 1) 
                              {
                                 echo("<script>location.href = 'login.php ';</script>");
                              }
                              else
                                {
                                echo "Error";
                              }
                          }
                          
                          ?>

          <form method ="post">
            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-user"></i>
                <input type="text" name="email"  placeholder="Enter your username" required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="oldpassword" placeholder="Enter old password" required>
              </div>
              
                <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="newpassword" placeholder="Enter new password" required>
              </div>
             
              <div class="button input-box">
                <input type="submit"  name="submit" value="Sumbit">
              </div>
              <!--<div class="text sign-up-text">Don't have an account? <label for="flip">Sigup now</label></div>-->
            </div>
        </form>
      </div>

    </div>
    </div>
  </div>
</body>
</html>
