<?php
/**
 * ======================================================
 * ARQUIVO LEGADO - MANUTENÇÃO DE COMPATIBILIDADE
 * ======================================================
 * Este arquivo foi consolidado em buscar.php
 * Mantido apenas para compatibilidade com código existente
 */

// Redirecionar para endpoint único
$_GET['tipo'] = 'descricao';
require_once __DIR__ . '/buscar.php';
?>