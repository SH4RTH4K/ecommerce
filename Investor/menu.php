<?PHP 
session_start();
//$_SESSION['doctorId']=$_GET['id'];

if($_SESSION['userId'] == null){
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


<?PHP 
include('payment_history.php');

$user=new User();
$date= date("Y-m-d");
$data=$user->getPaymentHistoryByUserId($_SESSION['userId']);
//$data=$user->getPaymentHistoryByUserId(1);



//$schedule=$doctor_info->getScheduleById($_GET['id']);
//$row_schedule=mysqli_fetch_array($schedule);
 ?>

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
                    <h1 class="text-primary m-0"><i class=""></i>LBD</h1>
                    <!-- <img src="img/logo.png" alt="Logo"> -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0 pe-4">
                        <a href="#" class="nav-item nav-link">Home</a>
                        <a href="#" class="nav-item nav-link">About</a>
                        <a href="../Investor" class="nav-item nav-link">Logout</a>
                        </div>
                       
                    </div>
           
                <!--</div>-->
            </nav>

            <div class="container-xxl py-5 bg-dark hero-header mb-5">
                <div class="container text-center my-5 pt-5 pb-4">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Payment History</h1>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->


        <!-- Menu Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Payment History</h5>
                    <h1 class="mb-5">Recent Transaction</h1>
                </div>
                <div class="tab-class text-center wow fadeInUp" data-wow-delay="0.1s">
                    <ul class="nav nav-pills d-inline-flex justify-content-center border-bottom mb-5">
                        <!--<li class="nav-item">-->
                        <!--    <a class="d-flex align-items-center text-start mx-3 ms-0 pb-3 active" data-bs-toggle="pill" href="#tab-1">-->
                        <!--        <i class="fa fa-money fa-2x text-primary"></i>-->
                        <!--        <div class="ps-3">-->
                        <!--            <small class="text-body">YEAR</small>-->
                        <!--            <h6 class="mt-n1 mb-0">2024</h6>-->
                        <!--        </div>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <!-- <li class="nav-item">
                            <a class="d-flex align-items-center text-start mx-3 pb-3" data-bs-toggle="pill" href="#tab-2">
                                <i class="fa fa-hamburger fa-2x text-primary"></i>
                                <div class="ps-3">
                                    <small class="text-body">Special</small>
                                    <h6 class="mt-n1 mb-0">Launch</h6>
                                </div>
                            </a>
                        </li> -->
                        <!-- <li class="nav-item">
                            <a class="d-flex align-items-center text-start mx-3 me-0 pb-3" data-bs-toggle="pill" href="#tab-3">
                                <i class="fa fa-utensils fa-2x text-primary"></i>
                                <div class="ps-3">
                                    <small class="text-body">Lovely</small>
                                    <h6 class="mt-n1 mb-0">Dinner</h6>
                                </div>
                            </a>
                        </li> -->
                    </ul>
                    <div class="tab-content">
                        <div id="tab-1" class="tab-pane fade show p-0 active">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                <span>TITLE</span>
                                                <span class=""> Transaction DATE</span>
                                                <!-- <span class="">TYPE</span> -->
                                                <span class="">AMOUNT</span>
                                            </h5>
                                        </div>
                                    </div>
                                </div>

                                <?PHP

                                    $depositAmount = 0;
                                    $withdrawalAmount = 0;
                                       if(isset($data)){
                                           //print_r($data);
                                        while ($row=mysqli_fetch_array($data)){
                                           // print_r($data);

                                            if( strtolower($row['Type'])== "deposit"){
                                                $depositAmount = $depositAmount + (int) $row['Amount'];
                                            }
                                            else if(strtolower($row['Type']) == "withdraw"){
                                                $withdrawalAmount = $withdrawalAmount + (int) $row['Amount'];
                                            }
                                            //$depositAmount = $depositAmount+ 10+10
                                        ?>

                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <!-- <img class="flex-shrink-0 img-fluid rounded" src="LBD/img/menu-1.jpg" alt="" style="width: 80px;"> -->
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                            <span><?PHP echo $row['Type'] ?></span>
                                            <span><?PHP echo $row['CreatedDate'] ?></span>
                                                <!-- <span><?PHP echo $row['Type'] ?></span> -->
                                                <span class="text-primary">TK <?PHP echo $row['Amount'] ?></span>
                                            </h5>
                                            <small class="fst-italic"><span><?PHP echo $row['Title'] ?></span></small>
                                        </div>
                                    </div>
                                </div>
                                <?PHP } }?>
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <!-- <img class="flex-shrink-0 img-fluid rounded" src="LBD/img/menu-2.jpg" alt="" style="width: 80px;"> -->
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                <span>TOTAL</span>
                                                <span class="text-primary"> TK <?php echo ($depositAmount-$withdrawalAmount )  ?></span>
                                            </h5>
                                            <!-- <small class="fst-italic">Ipsum ipsum clita erat amet dolor justo diam</small> -->
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <img class="flex-shrink-0 img-fluid rounded" src="LBD/img/menu-2.jpg" alt="" style="width: 80px;">
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                <span>Chicken Burger</span>
                                                <span class="text-primary">$115</span>
                                            </h5>
                                            <small class="fst-italic">Ipsum ipsum clita erat amet dolor justo diam</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <img class="flex-shrink-0 img-fluid rounded" src="LBD/img/menu-3.jpg" alt="" style="width: 80px;">
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                <span>Chicken Burger</span>
                                                <span class="text-primary">$115</span>
                                            </h5>
                                            <small class="fst-italic">Ipsum ipsum clita erat amet dolor justo diam</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center">
                                        <img class="flex-shrink-0 img-fluid rounded" src="LBD/img/menu-4.jpg" alt="" style="width: 80px;">
                                        <div class="w-100 d-flex flex-column text-start ps-4">
                                            <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                <span>Chicken Burger</span>
                                                <span class="text-primary">$115</span>
                                                <span class="text-primary">$115</span>
                                                
                                            </h5>
                                            <small class="fst-italic">Ipsum ipsum clita erat amet dolor justo diam</small>
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
              
            
                    </div>
                </div>
            </div>
        </div>
        <!-- Menu End -->
        

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
</body>

</html>