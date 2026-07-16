<?php 
session_start();
//session_unset();

if($_SESSION['userId'] == null || $_SESSION['role'] == null){
    echo("<script>location.href = '../ ';</script>");
}

include('doctor_registration.php');


$_pid=$_GET['pid'];
$_id=$_GET['id'];

$user=new Doctor();
$data=$user->delete_investor_payment($_pid);

header("Location: details.php?id=".$_id);
  ?>