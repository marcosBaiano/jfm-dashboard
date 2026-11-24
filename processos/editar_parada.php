<?php
/**
 * ======================================================
 * EDITAR PARADA
 * ======================================================
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

// Verificar permissão
if (!temPermissao('editar_paradas')) {
    die(respostaJSON(false, 'Acesso negado'));
}

$paraId = $_GET['id'] ?? $_POST['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$paraId) {
    die(respostaJSON(false, 'ID não informado'));
}

try {
    // Se é GET, buscar dados e exibir formulário
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                l.setor,
                l.linha,
                r.operador,
                d.tipo
            FROM paradas p
            LEFT JOIN registros r ON p.registro_id = r.id
            LEFT JOIN linhas l ON r.linha_id = l.id
            LEFT JOIN descricoes d ON p.codigo = d.codigo
            WHERE p.id = :id
        ");
        
        $stmt->execute([':id' => $paraId]);
        $parada = $stmt->fetch();
        
        if (!$parada) {
            die(respostaJSON(false, 'Parada não encontrada'));
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Atualizar parada
        $inicio = $_POST['inicio'] ?? null;
        $fim = $_POST['fim'] ?? null;
        $descricao = $_POST['descricao'] ?? '';
        $observacoes = $_POST['observacoes'] ?? '';
        
        $stmt = $pdo->prepare("
            UPDATE paradas
            SET inicio = :inicio,
                fim = :fim,
                descricao = :descricao,
                observacoes = :observacoes
            WHERE id = :id
        ");
        
        $resultado = $stmt->execute([
            ':inicio' => $inicio,
            ':fim' => $fim,
            ':descricao' => $descricao,
            ':observacoes' => $observacoes,
            ':id' => $paraId
        ]);
        
        if ($resultado) {
            registrarAuditoria($pdo, 'Parada editada', 'parada_id=' . $paraId, 'SUCCESS');
            $mensagem = 'Parada atualizada com sucesso!';
            $tipo_mensagem = 'success';
            
            // Re-buscar dados atualizados
            $stmt = $pdo->prepare("
                SELECT p.*, l.setor, l.linha, r.operador, d.tipo
                FROM paradas p
                LEFT JOIN registros r ON p.registro_id = r.id
                LEFT JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $paraId]);
            $parada = $stmt->fetch();
        } else {
            $mensagem = 'Erro ao atualizar parada!';
            $tipo_mensagem = 'danger';
        }
    }
    
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'danger';
    error_log('Erro em editar_parada.php: ' . $e->getMessage());
}

// HTML modernizado
echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Editar Parada - Sistema Industrial JFM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(15,23,42,.85);
            border-radius: 8px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #e2e8f0;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 6px;
            color: #e2e8f0;
            font-family: inherit;
        }
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            background: rgba(255,255,255,.08);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .info-box {
            background: rgba(255,255,255,.02);
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,.05);
            margin-bottom: 1.5rem;
            color: #cbd5e1;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #0f172a, #111827, #1e293b); min-height: 100vh; color: #e2e8f0;">
    <div class="form-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; margin: 0;">
                <i class="fas fa-edit"><\/i> Editar Parada
            </h1>
            <a href="/dashboard/paradas.php" style="color: #2563eb; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"><\/i> Voltar
            </a>
        </div>
        
        ' . (!empty($mensagem) ? '<div class="alert alert-' . ($tipo_mensagem === 'success' ? 'success' : 'danger') . '">
            <i class="fas fa-' . ($tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle') . '"><\/i> ' . htmlspecialchars($mensagem) . '
        </div>' : '') . '
        
        <div class="info-box">
            <strong>Parada ID:</strong> ' . htmlspecialchars($parada['id'] ?? '') . '<br>
            <strong>Linha:</strong> ' . htmlspecialchars(($parada['setor'] ?? '') . '-' . str_pad($parada['linha'] ?? '', 2, '0', STR_PAD_LEFT)) . '<br>
            <strong>Operador:</strong> ' . htmlspecialchars($parada['operador'] ?? '-') . '
        </div>
        
        <form method="POST" style="margin-top: 1.5rem;">
            <input type="hidden" name="id" value="' . htmlspecialchars($paraId) . '">
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-clock"><\/i> Data e Hora de Início
                </label>
                <input type="datetime-local" name="inicio" class="form-input" value="' . (isset($parada['inicio']) ? str_replace(' ', 'T', substr($parada['inicio'], 0, 19)) : '') . '" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-clock"><\/i> Data e Hora de Fim
                </label>
                <input type="datetime-local" name="fim" class="form-input" value="' . (isset($parada['fim']) ? str_replace(' ', 'T', substr($parada['fim'], 0, 19)) : '') . '">
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-file-alt"><\/i> Descrição
                </label>
                <textarea name="descricao" class="form-input" rows="3" style="resize: vertical;">' . htmlspecialchars($parada['descricao'] ?? '') . '<\/textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-sticky-note"><\/i> Observações
                </label>
                <textarea name="observacoes" class="form-input" rows="3" style="resize: vertical;">' . htmlspecialchars($parada['observacoes'] ?? '') . '<\/textarea>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"><\/i> Salvar Alterações
                </button>
                <a href="/dashboard/paradas.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-times"><\/i> Cancelar
                </a>
            </div>
        </form>
    </div>
</body>
</html>';
?>
