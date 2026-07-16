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
    <input type="checkbox" id="flip">
    <div class="cover">
      <div class="front">
        <img src="assets/images/frontImg.jpg" alt="">
        <div class="text">
          <span class="text-2">Let's get connected</span>
        </div>
      </div>
      <div class="back">
        <img class="backImg" src="assets/images/backImg.jpg" alt="">
        <div class="text">
          <span class="text-1">Complete miles of journey <br> with one step</span>
          <span class="text-2">Let's get started</span>
        </div>
      </div>
    </div>
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

                              $login = $doctorLogin->login_admin($_POST);
                              //print_r($login);
                              $row=mysqli_fetch_array($login);

                              if ($row) 
                              {
                                $_SESSION['doctorName'] =$row['username'];
                                 $_SESSION['userId']=$row['id']; 
                                 $_SESSION['role']='admin';
                                 echo("<script>location.href = 'dashboard.php ';</script>");
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
                <input type="text" name="username"  placeholder="Enter your username" required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" required>
              </div>
              <div class="text"><a href="#">Forgot password?</a></div>
              <div class="button input-box">
                <input type="submit"  name="submit" value="Sumbit">
              </div>
              <!--<div class="text sign-up-text">Don't have an account? <label for="flip">Sigup now</label></div>-->
            </div>
        </form>
      </div>
        <div class="signup-form">
          <div class="title">Signup</div>

          
        <form action="#">
            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-user"></i>
                <input type="text" placeholder="Enter your name" required>
              </div>
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="text" placeholder="Enter your email" required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" placeholder="Enter your password" required>
              </div>
              <div class="button input-box">
                <input type="submit" value="Sumbit">
              </div>
              <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
            </div>
      </form>
    </div>
    </div>
    </div>
  </div>
</body>
</html>
