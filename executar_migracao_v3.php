<?php
/**
 * Script de Migração do Banco de Dados - v3.0
 * Executa todas as melhorias da Fase 2 do projeto
 * Data: 2026-06-21
 */

require_once 'banco/conexao_pdo.php';

echo "=== INICIANDO MIGRAÇÃO DO BANCO DE DADOS v3.0 ===\n\n";

try {
    // Desabilitar check de foreign key temporariamente
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // =====================================================
    // FASE 1: CRIAR NOVAS TABELAS
    // =====================================================
    
    echo "📋 FASE 1: Criando novas tabelas...\n";
    
    // 1. Tabela Setores
    echo "  ✓ Criando tabela 'setores'...";
    $sql = "CREATE TABLE IF NOT EXISTS setores (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL UNIQUE,
        descricao VARCHAR(255),
        ativa TINYINT DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nome (nome),
        INDEX idx_ativa (ativa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo " ✅\n";
    
    // 2. Tabela Máquinas
    echo "  ✓ Criando tabela 'maquinas'...";
    $sql = "CREATE TABLE IF NOT EXISTS maquinas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setor_id INT NOT NULL,
        linha_id INT,
        nome VARCHAR(100) NOT NULL,
        descricao VARCHAR(255),
        ativa TINYINT DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE RESTRICT,
        FOREIGN KEY (linha_id) REFERENCES linhas(id) ON DELETE SET NULL,
        INDEX idx_setor_id (setor_id),
        INDEX idx_linha_id (linha_id),
        INDEX idx_nome (nome),
        INDEX idx_ativa (ativa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo " ✅\n";
    
    // 3. Tabela Grupos de Parada
    echo "  ✓ Criando tabela 'grupos_parada'...";
    $sql = "CREATE TABLE IF NOT EXISTS grupos_parada (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL UNIQUE,
        descricao VARCHAR(255),
        impacto ENUM('critico', 'alto', 'medio', 'baixo') DEFAULT 'medio',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nome (nome),
        INDEX idx_impacto (impacto)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo " ✅\n";
    
    // 4. Tabela Motivos de Parada
    echo "  ✓ Criando tabela 'motivos_parada'...";
    $sql = "CREATE TABLE IF NOT EXISTS motivos_parada (
        id INT PRIMARY KEY AUTO_INCREMENT,
        grupo_parada_id INT NOT NULL,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        motivo VARCHAR(255) NOT NULL,
        descricao TEXT,
        impacto ENUM('critico', 'alto', 'medio', 'baixo') DEFAULT 'medio',
        ativo TINYINT DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (grupo_parada_id) REFERENCES grupos_parada(id) ON DELETE RESTRICT,
        INDEX idx_codigo (codigo),
        INDEX idx_grupo_parada_id (grupo_parada_id),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo " ✅\n";
    
    // =====================================================
    // FASE 2: POPULAR TABELAS
    // =====================================================
    
    echo "\n📊 FASE 2: Populando tabelas com dados iniciais...\n";
    
    // Verificar se já existem dados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM setores");
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        echo "  ✓ Inserindo setores...";
        $setores = [
            ['Envase', 'Setor de envase e embalagem primária'],
            ['Embalagem', 'Setor de embalagem secundária'],
            ['Corte', 'Setor de corte de materiais'],
            ['Dobra', 'Setor de dobra e conformação'],
            ['Prensagem', 'Setor de prensagem'],
            ['Confecção', 'Setor de confecção'],
            ['Acabamento', 'Setor de acabamento'],
            ['Qualidade', 'Setor de controle de qualidade']
        ];
        
        $sql = "INSERT IGNORE INTO setores (nome, descricao, ativa) VALUES (?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($setores as $setor) {
            $stmt->execute($setor);
        }
        echo " ✅ (" . count($setores) . " registros)\n";
    }
    
    // Verificar grupos de parada
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM grupos_parada");
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        echo "  ✓ Inserindo grupos de parada...";
        $grupos = [
            ['Mecânico', 'Problemas de natureza mecânica', 'alto'],
            ['Elétrico', 'Problemas de natureza elétrica', 'alto'],
            ['Hidráulico', 'Problemas de natureza hidráulica', 'medio'],
            ['Falta de Material', 'Ausência de matéria prima ou insumos', 'critico'],
            ['Falta de Pessoa', 'Ausência de operador ou supervisor', 'alto'],
            ['Programação', 'Problemas de software/programação', 'medio'],
            ['Qualidade', 'Ajustes para conformidade de qualidade', 'medio'],
            ['Manutenção Preventiva', 'Manutenção preventiva programada', 'baixo'],
            ['Limpeza', 'Limpeza e higiene da máquina', 'baixo'],
            ['Outro', 'Outros motivos não classificados', 'medio']
        ];
        
        $sql = "INSERT IGNORE INTO grupos_parada (nome, descricao, impacto) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($grupos as $grupo) {
            $stmt->execute($grupo);
        }
        echo " ✅ (" . count($grupos) . " registros)\n";
    }
    
    // Verificar máquinas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM maquinas");
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        echo "  ✓ Inserindo máquinas...";
        
        $stmt = $pdo->query("SELECT id FROM linhas LIMIT 5");
        $linhas = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT id FROM setores LIMIT 8");
        $setores_ids = $stmt->fetchAll();
        
        $maquinas = [
            [1, 1, 'Máquina 01', 'Enchedor A - Envase'],
            [1, 1, 'Máquina 02', 'Tampadeira - Envase'],
            [1, 2, 'Máquina 03', 'Etiquetadora - Envase'],
            [1, 2, 'Máquina 04', 'Codificadora - Envase'],
            [2, 3, 'Máquina 05', 'Embaladora - Embalagem'],
            [2, 3, 'Máquina 06', 'Seladora - Embalagem'],
            [3, 4, 'Máquina 07', 'Cortadora A - Corte'],
            [3, 4, 'Máquina 08', 'Cortadora B - Corte'],
            [4, 5, 'Máquina 09', 'Dobradeira A - Dobra'],
            [4, 5, 'Máquina 10', 'Dobradeira B - Dobra']
        ];
        
        $sql = "INSERT IGNORE INTO maquinas (setor_id, linha_id, nome, descricao, ativa) VALUES (?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($maquinas as $maquina) {
            $stmt->execute($maquina);
        }
        echo " ✅ (" . count($maquinas) . " registros)\n";
    }
    
    // Verificar motivos de parada
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM motivos_parada");
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        echo "  ✓ Inserindo motivos de parada...";
        
        $motivos = [
            [1, 'MEC001', 'Correia quebrada', 'Correia de transmissão rompida', 'alto'],
            [1, 'MEC002', 'Rolamento travado', 'Rolamento com problemas de funcionamento', 'alto'],
            [1, 'MEC003', 'Engrenagem desgastada', 'Desgaste de engrenagens', 'medio'],
            [1, 'MEC004', 'Cilindro defeituoso', 'Cilindro pneumático com mau funcionamento', 'alto'],
            [2, 'ELE001', 'Disjuntor desarmado', 'Proteção elétrica acionada', 'medio'],
            [2, 'ELE002', 'Motor queimado', 'Motor elétrico com mau funcionamento', 'critico'],
            [2, 'ELE003', 'Falta de energia', 'Problema na alimentação elétrica', 'critico'],
            [2, 'ELE004', 'Sensor defeituoso', 'Sensor de posição com falha', 'alto'],
            [3, 'HID001', 'Vazamento hidráulico', 'Vazamento em tubulação', 'medio'],
            [3, 'HID002', 'Pressão baixa', 'Pressão abaixo do especificado', 'medio'],
            [3, 'HID003', 'Óleo sujo', 'Necessidade de troca de óleo', 'baixo'],
            [4, 'MAT001', 'Sem embalagem', 'Falta de embalagem primária', 'critico'],
            [4, 'MAT002', 'Sem rótulo', 'Falta de rótulo/etiqueta', 'critico'],
            [4, 'MAT003', 'Sem matéria prima', 'Falta de matéria prima', 'critico'],
            [4, 'MAT004', 'Falta de insumo', 'Falta de insumo especificado', 'alto'],
            [5, 'PES001', 'Falta de operador', 'Ausência do operador', 'alto'],
            [5, 'PES002', 'Falta de supervisor', 'Ausência do supervisor', 'medio'],
            [5, 'PES003', 'Doença', 'Operador de licença médica', 'alto'],
            [5, 'PES004', 'Férias', 'Operador em férias', 'alto'],
            [7, 'QUA001', 'Ajuste de calibre', 'Calibração de máquina', 'medio'],
            [7, 'QUA002', 'Inspeção de qualidade', 'Inspeção programada de qualidade', 'baixo'],
            [7, 'QUA003', 'Rejeição de lote', 'Lote rejeitado por qualidade', 'alto'],
            [8, 'MAN001', 'Limpeza programada', 'Limpeza preventiva da máquina', 'baixo'],
            [8, 'MAN002', 'Lubrificação', 'Lubrificação preventiva', 'baixo'],
            [8, 'MAN003', 'Inspeção preventiva', 'Inspeção de manutenção preventiva', 'baixo'],
            [10, 'OUT001', 'Outros', 'Motivo não especificado', 'medio']
        ];
        
        $sql = "INSERT IGNORE INTO motivos_parada (grupo_parada_id, codigo, motivo, descricao, impacto, ativo) 
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($motivos as $motivo) {
            $stmt->execute($motivo);
        }
        echo " ✅ (" . count($motivos) . " registros)\n";
    }
    
    // =====================================================
    // FASE 3: ADICIONAR COLUNAS EM TABELAS EXISTENTES
    // =====================================================
    
    echo "\n🔧 FASE 3: Adicionando colunas às tabelas existentes...\n";
    
    // Verificar se coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM registros LIKE 'setor_id'");
    if ($stmt->rowCount() == 0) {
        echo "  ✓ Adicionando coluna 'setor_id' em registros...";
        $pdo->exec("ALTER TABLE registros ADD COLUMN setor_id INT");
        echo " ✅\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM registros LIKE 'maquina_id'");
    if ($stmt->rowCount() == 0) {
        echo "  ✓ Adicionando coluna 'maquina_id' em registros...";
        $pdo->exec("ALTER TABLE registros ADD COLUMN maquina_id INT");
        echo " ✅\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM paradas LIKE 'grupo_parada_id'");
    if ($stmt->rowCount() == 0) {
        echo "  ✓ Adicionando coluna 'grupo_parada_id' em paradas...";
        $pdo->exec("ALTER TABLE paradas ADD COLUMN grupo_parada_id INT");
        echo " ✅\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM paradas LIKE 'motivo_parada_id'");
    if ($stmt->rowCount() == 0) {
        echo "  ✓ Adicionando coluna 'motivo_parada_id' em paradas...";
        $pdo->exec("ALTER TABLE paradas ADD COLUMN motivo_parada_id INT");
        echo " ✅\n";
    }
    
    // =====================================================
    // FASE 4: CRIAR ÍNDICES
    // =====================================================
    
    echo "\n⚡ FASE 4: Criando índices para performance...\n";
    
    // Função para adicionar índice com segurança
    $addIndex = function($tabela, $nome_index, $colunas) use ($pdo) {
        try {
            $pdo->exec("ALTER TABLE $tabela ADD INDEX $nome_index ($colunas)");
        } catch (Exception $e) {
            // Índice já existe, é seguro ignorar
        }
    };
    
    // Índices em registros
    $addIndex('registros', 'idx_data_registro', 'data_registro');
    $addIndex('registros', 'idx_setor_data', 'setor_id, data_registro');
    $addIndex('registros', 'idx_maquina_data', 'maquina_id, data_registro');
    $addIndex('registros', 'idx_turno_data', 'turno, data_registro');
    
    // Índices em paradas
    $addIndex('paradas', 'idx_data_inicio', 'inicio');
    $addIndex('paradas', 'idx_data_fim', 'fim');
    $addIndex('paradas', 'idx_motivo_parada', 'motivo_parada_id');
    $addIndex('paradas', 'idx_grupo_parada', 'grupo_parada_id');
    
    echo "  ✓ Índices criados ✅\n";
    
    // Reabilitar check de foreign key
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // =====================================================
    // FASE 5: RESUMO FINAL
    // =====================================================
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📈 RESUMO DA MIGRAÇÃO:\n";
    echo str_repeat("=", 50) . "\n";
    
    $tabelas = ['setores', 'maquinas', 'grupos_parada', 'motivos_parada'];
    foreach ($tabelas as $tabela) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabela");
        $result = $stmt->fetch();
        echo "  $tabela: {$result['total']} registros\n";
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "\n✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "📅 Data: " . date('Y-m-d H:i:s') . "\n";
    echo "📍 Versão: 3.0\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO NA MIGRAÇÃO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}

?>
