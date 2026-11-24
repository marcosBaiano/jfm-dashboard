<?php
/**
 * ======================================================
 * CONEXÃO PDO - SISTEMA INDUSTRIAL JFM
 * ======================================================
 * Gerencia conexão centralizada com o banco de dados
 * usando PDO (Prepared Statements) - PADRÃO ÚNICO
 * 
 * @version 2.0.0
 * @updated 2026-06-24
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '3512');
define('DB_NAME', 'paradas');

try {
    // Criar conexão PDO - Padrão único em todo projeto
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Para compatibilidade com código que usa $conn
    $conn = $pdo;
    
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}