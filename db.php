<?php
// C:\xampp\htdocs\ReSchedule\db.php

// --- SUPABASE REST API CONFIGURATION ---
define('SUPABASE_URL', 'https://fsnqbmokuvifwgnjrcmw.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_yZjMnhFnsLoVmw6RTi7U1Q_qlSt5dVN');

/**
 * Helper function to send requests to Supabase REST API
 */
function supabase_request($endpoint, $method = 'GET', $data = null) {
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH' || $method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}


// --- SUPABASE IPV4 POOLER PDO CONNECTION ---
// Configured for Render compatibility using Supabase's transaction pooler

$host = 'aws-1-eu-west-1.pooler.supabase.com'; // Pooled IPv4 host (Update region if different in Supabase Connect modal)
$db   = 'postgres';                                // Default database name
$user = 'postgres.fsnqbmokuvifwgnjrcmw';           // Pooler username includes your project reference
$pass = 'underdog@6002';                        // Your database password
$port = '6543';                                    // Port 6543 for transaction pooling

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
