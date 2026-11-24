<?php
/**
 * ======================================================
 * LOGOUT - ENCERRAR SESSÃO
 * ======================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexão para registrar logout em auditoria
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

// Registrar logout em auditoria antes de destruir a sessão
if (isset($_SESSION['usuario_id'])) {
    try {
        $ip = obterIP();
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, usuario_nome, acao, ip)
            VALUES (:usuario_id, :usuario_nome, :acao, :ip)
        ");
        
        $stmt->execute([
            ':usuario_id' => $_SESSION['usuario_id'],
            ':usuario_nome' => $_SESSION['usuario_nome'],
            ':acao' => 'LOGOUT',
            ':ip' => $ip
        ]);
    } catch (Exception $e) {
        error_log('Erro ao registrar logout: ' . $e->getMessage());
    }
}

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: /processos/login.php?msg=Você foi desconectado com sucesso');
exit();
