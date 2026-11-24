<?php
/**
 * ======================================================
 * AUTENTICAÇÃO E CONTROLE DE SESSÃO
 * ======================================================
 * Incluir este arquivo no início de cada página protegida
 * 
 * Uso: <?php require_once 'includes/auth.php'; ?>
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Se não está autenticado, redirecionar para login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /processos/login.php');
    exit();
}

// Validar se a sessão está expirada (30 minutos de inatividade)
$inatividade = 30 * 60; // 30 minutos
if (isset($_SESSION['ultimo_acesso'])) {
    if (time() - $_SESSION['ultimo_acesso'] > $inatividade) {
        session_destroy();
        header('Location: /processos/login.php?msg=Sessão expirada');
        exit();
    }
}

// Atualizar último acesso
$_SESSION['ultimo_acesso'] = time();

// Extrair dados da sessão para facilitar uso nas views
$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$perfil = $_SESSION['perfil'];
$email = $_SESSION['email'];

?>
