<?php
/**
 * =====================================================
 * CLASSE: Query Builder
 * =====================================================
 * Constrói queries dinâmicas baseadas em filtros
 * 
 * Uso:
 * $builder = new QueryBuilder($pdo, $filtro);
 * $oee = $builder->getOEEGeral();
 * $paradas = $builder->getTopParadas(10);
 */

class QueryBuilder {
    private $pdo;
    private $filtro;
    
    public function __construct($pdo, FiltroGlobal $filtro) {
        $this->pdo = $pdo;
        $this->filtro = $filtro;
    }
    
    /**
     * Obter OEE Geral
     */
    public function getOEEGeral() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                ROUND(AVG(disp.disponibilidade) * AVG(perf.performance) * AVG(qual.qualidade) / 10000, 2) as oee_geral,
                ROUND(AVG(disp.disponibilidade), 2) as disponibilidade,
                ROUND(AVG(perf.performance), 2) as performance,
                ROUND(AVG(qual.qualidade), 2) as qualidade
            FROM (
                SELECT 
                    r.id,
                    ((1440 - COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0)) / 1440 * 100) as disponibilidade
                FROM registros r
                LEFT JOIN paradas p ON r.id = p.registro_id
                WHERE $where
                GROUP BY r.id
            ) disp
            CROSS JOIN (
                SELECT ROUND(AVG(prod.producao / NULLIF(prod.producao + (prod.perda_1 + prod.perda_2 + prod.perda_3 + prod.perda_4), 0)) * 100, 2) as performance
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                WHERE $where
            ) perf
            CROSS JOIN (
                SELECT ROUND(AVG(COALESCE(SUM(v.velocidade), 0) / NULLIF(COALESCE(SUM(v.velocidade_planejada), 1), 0) * 100), 2) as qualidade
                FROM velocidades v
                INNER JOIN registros r ON v.registro_id = r.id
                WHERE $where
                GROUP BY r.id
            ) qual
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetch() ?: ['oee_geral' => 0, 'disponibilidade' => 0, 'performance' => 0, 'qualidade' => 0];
        } catch (Exception $e) {
            error_log("Erro ao calcular OEE: " . $e->getMessage());
            return ['oee_geral' => 0, 'disponibilidade' => 0, 'performance' => 0, 'qualidade' => 0];
        }
    }
    
    /**
     * Obter Produção Total
     */
    public function getProducaoTotal() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                COALESCE(SUM(prod.producao), 0) as total_producao,
                COALESCE(SUM(prod.perda_1 + prod.perda_2 + prod.perda_3 + prod.perda_4), 0) as total_perdas
            FROM producao prod
            INNER JOIN registros r ON prod.registro_id = r.id
            WHERE $where
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch();
            return [
                'producao' => (int)($result['total_producao'] ?? 0),
                'perdas' => (int)($result['total_perdas'] ?? 0),
                'boa' => (int)(($result['total_producao'] ?? 0) - ($result['total_perdas'] ?? 0))
            ];
        } catch (Exception $e) {
            error_log("Erro ao calcular produção: " . $e->getMessage());
            return ['producao' => 0, 'perdas' => 0, 'boa' => 0];
        }
    }
    
    /**
     * Obter Total de Paradas
     */
    public function getTotalParadas() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                COUNT(p.id) as quantidade,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            WHERE $where
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch();
            return [
                'quantidade' => (int)($result['quantidade'] ?? 0),
                'tempo_total' => (int)($result['tempo_total'] ?? 0),
                'tempo_medio' => (int)($result['quantidade'] > 0 ? $result['tempo_total'] / $result['quantidade'] : 0)
            ];
        } catch (Exception $e) {
            error_log("Erro ao calcular paradas: " . $e->getMessage());
            return ['quantidade' => 0, 'tempo_total' => 0, 'tempo_medio' => 0];
        }
    }
    
    /**
     * Obter Top Motivos de Parada
     */
    public function getTopMotivos($limite = 10) {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                mp.id,
                mp.codigo,
                mp.motivo,
                gp.nome as grupo,
                COUNT(p.id) as quantidade,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_minutos
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            LEFT JOIN motivos_parada mp ON p.motivo_parada_id = mp.id
            LEFT JOIN grupos_parada gp ON mp.grupo_parada_id = gp.id
            WHERE $where
            GROUP BY mp.id, mp.motivo
            ORDER BY tempo_total DESC
            LIMIT $limite
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter top motivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Top Máquinas com Mais Paradas
     */
    public function getTopMaquinas($limite = 10) {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                m.id,
                m.nome,
                s.nome as setor,
                COUNT(p.id) as quantidade_paradas,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            LEFT JOIN maquinas m ON r.maquina_id = m.id
            LEFT JOIN setores s ON m.setor_id = s.id
            WHERE $where
            GROUP BY m.id, m.nome
            ORDER BY tempo_total DESC
            LIMIT $limite
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter top máquinas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Top Setores com Mais Paradas
     */
    public function getTopSetores($limite = 10) {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                s.id,
                s.nome,
                COUNT(p.id) as quantidade_paradas,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            LEFT JOIN setores s ON r.setor_id = s.id
            WHERE $where
            GROUP BY s.id, s.nome
            ORDER BY tempo_total DESC
            LIMIT $limite
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter top setores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Top Códigos de Parada
     */
    public function getTopCodigos($limite = 10) {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                p.codigo,
                p.descricao,
                COUNT(*) as quantidade,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            WHERE $where AND p.codigo IS NOT NULL
            GROUP BY p.codigo
            ORDER BY tempo_total DESC
            LIMIT $limite
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter top códigos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter dados para Pareto (motivos de parada)
     */
    public function getPareto() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                mp.motivo,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total,
                COUNT(p.id) as quantidade,
                ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 
                    (SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p2.inicio, p2.fim)), 1) 
                     FROM paradas p2 INNER JOIN registros r2 ON p2.registro_id = r2.id WHERE $where) * 100, 2) as percentual
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            LEFT JOIN motivos_parada mp ON p.motivo_parada_id = mp.id
            WHERE $where
            GROUP BY p.motivo_parada_id, mp.motivo
            ORDER BY tempo_total DESC
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            $dados = $stmt->fetchAll();
            
            // Calcular percentual acumulado
            $acumulado = 0;
            foreach ($dados as &$item) {
                $acumulado += $item['percentual'];
                $item['percentual_acumulado'] = $acumulado;
            }
            
            return $dados;
        } catch (Exception $e) {
            error_log("Erro ao obter Pareto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter KPIs Gerais
     */
    public function getKPIsGerais() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        try {
            $oee = $this->getOEEGeral();
            $producao = $this->getProducaoTotal();
            $paradas = $this->getTotalParadas();
            
            $sql = "
                SELECT 
                    COUNT(DISTINCT r.linha_id) as linhas_ativas,
                    COUNT(DISTINCT m.id) as maquinas_ativas,
                    COUNT(DISTINCT r.id) as registros_processados
                FROM registros r
                LEFT JOIN maquinas m ON r.maquina_id = m.id
                WHERE $where
            ";
            
            $stmt = $this->pdo->query($sql);
            $geral = $stmt->fetch();
            
            // Calcular eficiência média
            $sql_eficiencia = "
                SELECT ROUND(AVG(COALESCE(v.velocidade, 0) / NULLIF(COALESCE(v.velocidade_planejada, 1), 0) * 100), 2) as eficiencia
                FROM velocidades v
                INNER JOIN registros r ON v.registro_id = r.id
                WHERE $where
            ";
            
            $stmt_ef = $this->pdo->query($sql_eficiencia);
            $ef = $stmt_ef->fetch();
            
            return [
                'oee_geral' => $oee['oee_geral'] ?? 0,
                'disponibilidade' => $oee['disponibilidade'] ?? 0,
                'performance' => $oee['performance'] ?? 0,
                'qualidade' => $oee['qualidade'] ?? 0,
                'producao_total' => $producao['producao'],
                'producao_boa' => $producao['boa'],
                'perdas_totais' => $producao['perdas'],
                'eficiencia_media' => $ef['eficiencia'] ?? 0,
                'tempo_total_parado' => $paradas['tempo_total'],
                'quantidade_paradas' => $paradas['quantidade'],
                'linhas_ativas' => (int)($geral['linhas_ativas'] ?? 0),
                'maquinas_ativas' => (int)($geral['maquinas_ativas'] ?? 0),
                'registros_processados' => (int)($geral['registros_processados'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("Erro ao obter KPIs gerais: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter dados de Velocidade para dashboards
     */
    public function getVelocidades($limite = null) {
        $where = $this->filtro->getWhereClauses('r', 'v');
        
        $sql = "
            SELECT 
                v.id,
                v.hora,
                v.velocidade_planejada,
                v.velocidade,
                ROUND((v.velocidade / NULLIF(v.velocidade_planejada, 0)) * 100, 2) as eficiencia,
                r.linha_id,
                l.nome as linha_nome
            FROM velocidades v
            INNER JOIN registros r ON v.registro_id = r.id
            LEFT JOIN linhas l ON r.linha_id = l.id
            WHERE $where
            ORDER BY v.hora DESC
        ";
        
        if ($limite) {
            $sql .= " LIMIT $limite";
        }
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter velocidades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Eficiência por Linha
     */
    public function getEficienciaLinhas() {
        $where = $this->filtro->getWhereClauses('r', 'v');
        
        $sql = "
            SELECT 
                l.id,
                l.nome as linha,
                COUNT(v.id) as registros,
                ROUND(AVG(v.velocidade), 2) as velocidade_media,
                ROUND(AVG(v.velocidade_planejada), 2) as velocidade_planejada_media,
                ROUND(AVG(COALESCE(v.velocidade, 0) / NULLIF(COALESCE(v.velocidade_planejada, 1), 0) * 100), 2) as eficiencia_media
            FROM velocidades v
            INNER JOIN registros r ON v.registro_id = r.id
            LEFT JOIN linhas l ON r.linha_id = l.id
            WHERE $where
            GROUP BY l.id, l.nome
            ORDER BY eficiencia_media DESC
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter eficiência por linhas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Estatísticas de Paradas por Hora
     */
    public function getParadasPorHora() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                HOUR(p.inicio) as hora,
                COUNT(p.id) as quantidade,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total
            FROM paradas p
            INNER JOIN registros r ON p.registro_id = r.id
            WHERE $where
            GROUP BY HOUR(p.inicio)
            ORDER BY hora ASC
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter paradas por hora: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Resumo Diário
     */
    public function getResumoDiario() {
        $where = $this->filtro->getWhereClauses('r', 'p');
        
        $sql = "
            SELECT 
                DATE(r.data_registro) as data,
                COUNT(DISTINCT r.id) as registros,
                COALESCE(SUM(prod.producao), 0) as producao_total,
                COALESCE(SUM(prod.perda_1 + prod.perda_2 + prod.perda_3 + prod.perda_4), 0) as perdas_total,
                COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado_minutos,
                COUNT(DISTINCT p.id) as total_paradas
            FROM registros r
            LEFT JOIN producao prod ON r.id = prod.registro_id
            LEFT JOIN paradas p ON r.id = p.registro_id
            WHERE $where
            GROUP BY DATE(r.data_registro)
            ORDER BY data DESC
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao obter resumo diário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter Eficiência Média Geral
     */
    public function getEficienciaMedia() {
        $where = $this->filtro->getWhereClauses('r', 'v');
        
        $sql = "
            SELECT 
                ROUND(AVG(COALESCE(v.velocidade, 0) / NULLIF(COALESCE(v.velocidade_planejada, 1), 0) * 100), 2) as eficiencia,
                ROUND(AVG(v.velocidade), 2) as velocidade_media,
                ROUND(AVG(v.velocidade_planejada), 2) as planejada_media
            FROM velocidades v
            INNER JOIN registros r ON v.registro_id = r.id
            WHERE $where
        ";
        
        try {
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch();
            return [
                'eficiencia' => (float)($result['eficiencia'] ?? 0),
                'velocidade_media' => (float)($result['velocidade_media'] ?? 0),
                'planejada_media' => (float)($result['planejada_media'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("Erro ao obter eficiência média: " . $e->getMessage());
            return ['eficiencia' => 0, 'velocidade_media' => 0, 'planejada_media' => 0];
        }
    }
}

?>
