<?php 

session_start();
//session_unset();


if($_SESSION['userId'] == null || $_SESSION['role'] == null){
    echo("<script>location.href = '../ ';</script>");
}

  ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>LBD</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="LBD/lib/animate/animate.min.css" rel="stylesheet">
    <link href="LBD/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="LBD/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="LBD/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="LBD/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar & Hero Start -->
        <div class="container-xxl position-relative p-0">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
                <a href="" class="navbar-brand p-0">
                    <h1 class="text-primary m-0"><i class=""></i>LBD -ADMIN</h1>
                    <!-- <img src="img/logo.png" alt="Logo"> -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                              <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0 pe-4">
                        <a href="#" class="nav-item nav-link">Home</a>
                        <a href="#" class="nav-item nav-link">About</a>
                        <!--<a href="../login.php" class="nav-item nav-link">Login</a>-->
                        </div>
                       
                    </div>
            </nav>

            <div class="container-xxl py-5 bg-dark hero-header mb-5">
                <div class="container text-center my-5 pt-5 pb-4">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Investor</h1>
                     <a href="../Investor/dashboard.php"><h2 class="text-primary m-0">Back</h2></a>
       
                </div>
               <div>
           
            </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->
<?PHP 

$_id=$_GET['id'];

include('doctor_registration.php');

$user=new Doctor();
$data=$user->get_investor_payment($_id);


 ?>

        <!-- Service Start -->
        <div class="container-xxl py-5">
            
            <div>
            <a href="../Investor/add.php?id=<?PHP echo $_id ?>">ADD PAYMENT</a>
            </div>
<table id="example" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Type</th>
                <th>Title</th>
                <!--<th>Office</th>-->
                <!--<th>Age</th>-->
                <th>Date</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
               <?php
                                    $depositAmount = 0;
                                    $withdrawalAmount = 0;
                                    
                 if(isset($data)){
                                        while ($row=mysqli_fetch_array($data)){
                                            //print_r($data);
                                            
                                                                                 if( strtolower($row['Type'])== "deposit"){
                                                $depositAmount = $depositAmount + (int) $row['Amount'];
                                            }
                                            else if(strtolower($row['Type']) == "withdraw"){
                                                $withdrawalAmount = $withdrawalAmount + (int) $row['Amount'];
                                                
                                            }

                ?>
            <tr>
                <td><?PHP echo $row['Type'] ?></td>
                   <td><?PHP echo $row['Title'] ?></td>
   
                <td><?PHP echo $row['CreatedDate'] ?></td>
                 <td><?PHP echo $row['Amount'] ?></td>
                  <td> <a href="../deletepayment.php?pid=<?PHP echo $row['Id'] ?>&&id=<?PHP echo $_id ?>" onclick="return confirm('Are you sure?');">DELETE</a></td>
            </tr>
            
               <?PHP  }} ?>
               
    
        </tbody>
        <tfoot>
            <tr>
                <th>Type</th>
                <th>Title</th>
                <!--<th>Office</th>-->
                <!--<th>Age</th>-->
                <th>Date</th>
                  <th>Amount</th>
                <th>Action</th>
            </tr>
        </tfoot>
    </table>
                

              
                
      
                    
                    
                    
                    
                    
            
        </div>
        
         <div>
              <?PHP

                                    // $depositAmount = 0;
                                    // $withdrawalAmount = 0;
                                    //   if(isset($data)){
                                    //       //print_r($data);
                                    //     while ($row=mysqli_fetch_array($data)){
                                    //       // print_r($data);

                                    //         if( strtolower($row['Type'])== "deposit"){
                                    //             $depositAmount = $depositAmount + (int) $row['Amount'];
                                    //         }
                                    //         else if(strtolower($row['Type']) == "withdraw"){
                                    //             $withdrawalAmount = $withdrawalAmount + (int) $row['Amount'];
                                    //         }
                                            //$depositAmount = $depositAmount+ 10+10
                                        ?>
                                        
            <h2>Balance  : <?php echo ($depositAmount-$withdrawalAmount )  ?> </h2>
            </div>
        <!-- Service End -->
        

        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
            <!-- <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Company</h4>
                        <a class="btn btn-link" href="">About Us</a>
                        <a class="btn btn-link" href="">Contact Us</a>
                        <a class="btn btn-link" href="">Reservation</a>
                        <a class="btn btn-link" href="">Privacy Policy</a>
                        <a class="btn btn-link" href="">Terms & Condition</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@example.com</p>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Opening</h4>
                        <h5 class="text-light fw-normal">Monday - Saturday</h5>
                        <p>09AM - 09PM</p>
                        <h5 class="text-light fw-normal">Sunday</h5>
                        <p>10AM - 08PM</p>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Newsletter</h4>
                        <p>Dolor amet sit justo amet elitr clita ipsum elitr est.</p>
                        <div class="position-relative mx-auto" style="max-width: 400px;">
                            <input class="form-control border-primary w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                            <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="#">ZH</a>, All Right Reserved. 
                        </div>
               
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
      <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
	    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
		    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
			    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
				    <script src="https://cdn.datatables.net/2.0.1/js/dataTables.js"></script>
					    <script src="https://cdn.datatables.net/2.0.1/js/dataTables.bootstrap.js"></script>
		
		
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="LBD/lib/wow/wow.min.js"></script>
    <script src="LBD/lib/easing/easing.min.js"></script>
    <script src="LBD/lib/waypoints/waypoints.min.js"></script>
    <script src="LBD/lib/counterup/counterup.min.js"></script>
    <script src="LBD/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="LBD/lib/tempusdominus/js/moment.min.js"></script>
    <script src="LBD/lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="LBD/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="LBD/js/main.js"></script>
    	
	<script>
	//new DataTable('#example');
	
	oTable = $("#example").dataTable({
  columnDefs: [{
    "defaultContent": "-",
    "targets": "_all"
  }]
});

	</script>
    
</body>

</html>