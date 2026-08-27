<?php
session_start();
require_once 'db.php';

$message = '';
$statusClass = '';
$programs = [];

// Fetch available programs dynamically handling different column names
try {
    $programsStmt = $pdo->query("
        SELECT id, 
               COALESCE(
                   NULLIF(to_jsonb(p)->>'program_name', ''),
                   NULLIF(to_jsonb(p)->>'programme_name', ''),
                   NULLIF(to_jsonb(p)->>'title', ''),
                   NULLIF(to_jsonb(p)->>'name', ''),
                   'Program #' || id
               ) AS program_name 
        FROM programmes p 
        ORDER BY id ASC
    ");
    $programs = $programsStmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error loading programs: " . $e->getMessage();
    $statusClass = "error";
}
