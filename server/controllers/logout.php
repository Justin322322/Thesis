<?php
// server/controllers/logout.php
session_start();
session_unset();
session_destroy();
header('Location: ../../public/login.html'); // Correct relative path from controllers to public
exit;
?>
