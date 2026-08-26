<?php
session_start();
require_once 'db.php';

$message = '';
$statusClass = '';
$programs = [];

// Fetch available programs for the dropdown menu
try {
    // Tries fetching from 'programs' or 'programmes'
    $programsStmt = $pdo->query("
        SELECT id, 
               COALESCE(
                   NULLIF(program_name, ''), 
                   NULLIF(name, ''), 
                   'Program ' || id
               ) AS program_name 
        FROM programs 
        ORDER BY id ASC
    ");
    $programs = $programsStmt->fetchAll();
} catch (PDOException $e) {
    // Fallback if table is named 'programmes' instead
    try {
        $programsStmt = $pdo->query("SELECT id, name AS program_name FROM programmes ORDER BY id ASC");
        $programs = $programsStmt->fetchAll();
    } catch (PDOException $ex) {
        $programs = [];
    }
}
