<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AcadMeter</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS for layout and styling -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/index_styles.css">
</head>
<body>

<!-- Hero Section with Animation -->
<div class="hero-section text-center text-white">
    <div class="overlay"></div>
    <div class="content animate__animated animate__fadeIn">
        <img src="assets/img/acadmeter_logo.png" alt="AcadMeter Logo" class="logo animate__animated animate__bounceIn animate__delay-1s">
        <h1 class="animate__animated animate__fadeInDown animate__delay-1s">Welcome to AcadMeter</h1>
        <p class="animate__animated animate__fadeIn animate__delay-2s">Your comprehensive academic performance monitoring system.</p>
        <div class="cta-buttons animate__animated animate__fadeInUp animate__delay-2s">
            <a href="login.html" class="btn btn-primary btn-lg">Log In</a>
            <a href="register.html" class="btn btn-outline-light btn-lg">Sign Up</a>
        </div>
    </div>
</div>

<!-- Features Section with Immediate Animation -->
<section class="features-section py-5">
    <div class="container">
        <h2 class="text-center mb-5 animate__animated animate__fadeInDown">Features of AcadMeter</h2>
        <div class="row text-center">
            <div class="col-md-4 feature animate__animated animate__zoomIn animate__delay-1s">
                <i class="fas fa-chart-line feature-icon mb-3"></i>
                <h4>Real-Time Analytics</h4>
                <p>Monitor and analyze student performance with ease.</p>
            </div>
            <div class="col-md-4 feature animate__animated animate__zoomIn animate__delay-1.5s">
                <i class="fas fa-user-shield feature-icon mb-3"></i>
                <h4>Secure Access</h4>
                <p>Advanced security to protect user data and ensure privacy.</p>
            </div>
            <div class="col-md-4 feature animate__animated animate__zoomIn animate__delay-2s">
                <i class="fas fa-users feature-icon mb-3"></i>
                <h4>User-Friendly Interface</h4>
                <p>Designed with simplicity and efficiency in mind.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer text-center py-3 text-white">
    <p>&copy; <?php echo date("Y"); ?> AcadMeter. All rights reserved.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="assets/js/index_script.js"></script>

</body>
</html>