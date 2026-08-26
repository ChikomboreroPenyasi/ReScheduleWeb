<?php
// C:\xampp\htdocs\ReSchedule\db.php

// --- SUPABASE REST API CONFIGURATION ---
define('SUPABASE_URL', 'https://fxahpgeprmhwowxdrpeo.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_s7K7MqBhl6cPzW-LJfUFhQ_Mfmwnwoo');

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


// --- SUPABASE DIRECT POSTGRESQL PDO CONNECTION ---
// Get these exact credentials from: Supabase Dashboard -> Project Settings -> Database -> Connection string (URI/PDO)

$host = 'db.rprdeqymghipjhjrvagr.supabase.co'; // Your project ID host
$db   = 'postgres';                            // Default database name
$user = 'postgres';                            // Default user name
$pass = 'eC0UQPLqSsOYMUuy';           // Replace with your actual database password
$port = '5432';                                // Port 5432 or 6543 for transaction pooler

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
