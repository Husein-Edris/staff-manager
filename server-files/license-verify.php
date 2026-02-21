<?php

/**
 * License Verification Endpoint for RT Employee Manager V2
 * Deploy to: /home/u507465150/public_html/product-licenses/employee-manager/api/license-verify.php
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read input
$license_key = isset($_POST['license_key']) ? trim($_POST['license_key']) : '';
$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';

// Validate input
if (empty($license_key) || empty($domain)) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Missing license_key or domain']);
    exit;
}

// Strip protocol and trailing slashes from domain
$domain = preg_replace('#^https?://#', '', $domain);
$domain = rtrim($domain, '/');
$domain = strtolower($domain);

// Load licenses
$licenses_file = __DIR__ . '/licenses.json';
if (!file_exists($licenses_file)) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'License database not found']);
    exit;
}

$licenses = json_decode(file_get_contents($licenses_file), true);
if (!is_array($licenses)) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'License database corrupt']);
    exit;
}

// Check if license key exists
if (!isset($licenses[$license_key])) {
    echo json_encode(['valid' => false, 'error' => 'Invalid license key']);
    exit;
}

// Check if domain is allowed for this key
$allowed_domains = array_map('strtolower', $licenses[$license_key]);
if (in_array($domain, $allowed_domains, true)) {
    echo json_encode(['valid' => true]);
    exit;
}

echo json_encode(['valid' => false, 'error' => 'Domain not authorized for this license key']);
