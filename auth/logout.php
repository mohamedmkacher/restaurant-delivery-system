<?php
require_once __DIR__ . '/../includes/functions.php';

// Clear all session data
session_destroy();

// Redirect to login page with success message
flash('You have been successfully logged out.', 'success');
redirect('/auth/login.php');
?>
