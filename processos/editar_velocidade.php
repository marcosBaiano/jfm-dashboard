<?php
/**
 * ======================================================
 * EDITAR VELOCIDADE
 * ======================================================
 */

require_once '../includes/auth.php';
require_once '../banco/conexao_pdo.php';
require_once '../includes/functions.php';

// Verificar permissão
if (!temPermissao('editar_velocidades')) {
    die(respostaJSON(false, 'Acesso negado'));
}

$velId = $_GET['id'] ?? $_POST['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$velId) {
    die(respostaJSON(false, 'ID não informado'));
}

try {
    // Se é GET, buscar dados e exibir formulário
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $stmt = $pdo->prepare("
            SELECT 
                v.*,
                l.setor,
                l.linha,
                r.operador
            FROM velocidades v
            LEFT JOIN registros r ON v.registro_id = r.id
            LEFT JOIN linhas l ON r.linha_id = l.id
            WHERE v.id = :id
        ");
        
        $stmt->execute([':id' => $velId]);
        $velocidade = $stmt->fetch();
        
        if (!$velocidade) {
            die(respostaJSON(false, 'Velocidade não encontrada'));
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Atualizar velocidade
        $velocidade_planejada = $_POST['velocidade_planejada'] ?? 0;
        $velocidade_real = $_POST['velocidade_real'] ?? 0;
        $observacoes = $_POST['observacoes'] ?? '';
        
        $stmt = $pdo->prepare("
            UPDATE velocidades
            SET velocidade_planejada = :vel_planejada,
                velocidade_real = :vel_real,
                observacoes = :observacoes
            WHERE id = :id
        ");
        
        $resultado = $stmt->execute([
            ':vel_planejada' => $velocidade_planejada,
            ':vel_real' => $velocidade_real,
            ':observacoes' => $observacoes,
            ':id' => $velId
        ]);
        
        if ($resultado) {
            registrarAuditoria($pdo, 'Velocidade editada', 'velocidade_id=' . $velId, 'SUCCESS');
            $mensagem = 'Velocidade atualizada com sucesso!';
            $tipo_mensagem = 'success';
            
            // Re-buscar dados atualizados
            $stmt = $pdo->prepare("
                SELECT v.*, l.setor, l.linha, r.operador
                FROM velocidades v
                LEFT JOIN registros r ON v.registro_id = r.id
                LEFT JOIN linhas l ON r.linha_id = l.id
                WHERE v.id = :id
            ");
            $stmt->execute([':id' => $velId]);
            $velocidade = $stmt->fetch();
        } else {
            $mensagem = 'Erro ao atualizar velocidade!';
            $tipo_mensagem = 'danger';
        }
    }
    
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'danger';
    error_log('Erro em editar_velocidade.php: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Editar Velocidade - Sistema Industrial JFM</title>
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
        .efficiency-bar {
            width: 100%;
            height: 24px;
            background: rgba(255,255,255,.1);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .efficiency-fill {
            height: 100%;
            background: linear-gradient(90deg, #7c3aed, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #0f172a, #111827, #1e293b); min-height: 100vh; color: #e2e8f0;">
    <div class="form-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; margin: 0;">
                <i class="fas fa-edit"></i> Editar Velocidade
            </h1>
            <a href="/dashboard/velocidade.php" style="color: #2563eb; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo ($tipo_mensagem === 'success' ? 'success' : 'danger'); ?>">
            <i class="fas fa-<?php echo ($tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle'); ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>Velocidade ID:</strong> <?php echo htmlspecialchars($velocidade['id'] ?? ''); ?><br>
            <strong>Linha:</strong> <?php echo htmlspecialchars(($velocidade['setor'] ?? '') . '-' . str_pad($velocidade['linha'] ?? '', 2, '0', STR_PAD_LEFT)); ?><br>
            <strong>Operador:</strong> <?php echo htmlspecialchars($velocidade['operador'] ?? '-'); ?><br>
            <strong>Data de Registro:</strong> <?php echo (isset($velocidade['data_registro']) ? formatarDataHora($velocidade['data_registro']) : '-'); ?>
        </div>
        
        <form method="POST" style="margin-top: 1.5rem;">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($velId); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-target"></i> Velocidade Planejada (unid/min)
                    </label>
                    <input type="number" step="0.01" name="velocidade_planejada" class="form-input" value="<?php echo htmlspecialchars($velocidade['velocidade_planejada'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tachometer"></i> Velocidade Real (unid/min)
                    </label>
                    <input type="number" step="0.01" name="velocidade_real" class="form-input" value="<?php echo htmlspecialchars($velocidade['velocidade_real'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-chart-pie"></i> Eficiência
                </label>
                <div class="info-box" style="margin-bottom: 0;">
                    <div class="efficiency-bar">
                        <div class="efficiency-fill" id="efficiencyFill" style="width: 0%">
                            <span id="efficiencyPercent">0%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-sticky-note"></i> Observações
                </label>
                <textarea name="observacoes" class="form-input" rows="3" style="resize: vertical;"><?php echo htmlspecialchars($velocidade['observacoes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <a href="/dashboard/velocidade.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <script>
        function updateEfficiency() {
            const planejada = parseFloat(document.querySelector('input[name="velocidade_planejada"]').value) || 0;
            const real = parseFloat(document.querySelector('input[name="velocidade_real"]').value) || 0;
            
            if (planejada > 0) {
                const efficiency = (real / planejada) * 100;
                const percent = Math.min(100, Math.max(0, efficiency));
                
                document.getElementById('efficiencyFill').style.width = percent + '%';
                document.getElementById('efficiencyPercent').textContent = Math.round(percent) + '%';
            }
        }
        
        document.querySelector('input[name="velocidade_planejada"]').addEventListener('change', updateEfficiency);
        document.querySelector('input[name="velocidade_real"]').addEventListener('change', updateEfficiency);
        
        updateEfficiency();
    </script>
</body>
</html>
<?php include '../includes/footer.php'; ?>
