<?php
// includes/auth.php - Central Authentication System

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ====================== COOKIE HELPERS ======================

function setAuthCookie($name, $value, $minutes = 60) {
    $expiry = time() + ($minutes * 60);
    setcookie($name, $value, $expiry, "/", "", false, true); // httponly = true
}

function clearAuthCookie($name) {
    setcookie($name, "", time() - 3600, "/", "", false, true);
    unset($_COOKIE[$name]);
}

// ====================== STAFF AUTH ======================

function isStaffLoggedIn() {
    return isset($_COOKIE['staff_id']) && $_COOKIE['staff_id'] > 0;
}

function isAdmin() {
    return isset($_COOKIE['staff_role']) && 
           in_array(strtolower(trim($_COOKIE['staff_role'] ?? '')), ['admin', 'administrator']);
}

function getCurrentStaff() {
    return [
        'id'   => $_COOKIE['staff_id'] ?? null,
        'name' => $_COOKIE['staff_name'] ?? 'Staff',
        'role' => $_COOKIE['staff_role'] ?? ''
    ];
}

function requireStaffLogin() {
    if (!isStaffLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: dashboard.php?error=access_denied");
        exit;
    }
}

// ====================== CUSTOMER AUTH ======================

function isCustomerLoggedIn() {
    return isset($_COOKIE['customer_id']) && $_COOKIE['customer_id'] > 0;
}

function getCurrentCustomer() {
    return [
        'id'    => $_COOKIE['customer_id'] ?? null,
        'name'  => $_COOKIE['customer_name'] ?? 'Customer',
        'tel'   => $_COOKIE['customer_tel'] ?? '',
        'company' => $_COOKIE['customer_company'] ?? ''
    ];
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// ====================== LOGOUT FUNCTIONS ======================

function staffLogout() {
    clearAuthCookie('staff_id');
    clearAuthCookie('staff_name');
    clearAuthCookie('staff_role');
    
    session_unset();
    session_destroy();
    
    header("Location: ../login.php");
    exit;
}

function customerLogout() {
    clearAuthCookie('customer_id');
    clearAuthCookie('customer_name');
    clearAuthCookie('customer_company');
    clearAuthCookie('customer_tel');
    
    session_unset();
    session_destroy();
    
    header("Location: ../login.php");
    exit;
}
?>