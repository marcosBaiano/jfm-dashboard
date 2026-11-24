<?php
/**
 * =====================================================
 * CLASSE: Indicadores de Dashboard
 * =====================================================
 * Cálculos de OEE, Disponibilidade, Eficiência, etc
 * 
 * Uso:
 * $ind = new IndicadoresDashboard($pdo);
 * $oee = $ind->obterOEE($filtro);
 * $disponibilidade = $ind->obterDisponibilidade($filtro);
 */

class IndicadoresDashboard {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * DISPONIBILIDADE (%)
     * Fórmula: (Tempo jornada - Tempo parado) / Tempo jornada * 100
     * Assume jornada de 16 horas
     * 
     * @param array $filtro
     * @return float
     */
    public function obterDisponibilidade($filtro = []) {
        try {
            $tempoJornada = 16 * 60; // 960 minutos
            
            $sql = "
                SELECT 
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado_minutos,
                    COUNT(DISTINCT r.id) as quantidade_registros
                FROM registros r
                LEFT JOIN paradas p ON r.id = p.registro_id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$resultado['quantidade_registros'] == 0) {
                return 0;
            }
            
            $tempoParado = (int)$resultado['tempo_parado_minutos'];
            $tempoDisponivel = ($tempoJornada * (int)$resultado['quantidade_registros']) - $tempoParado;
            $disponibilidade = ($tempoDisponivel / ($tempoJornada * (int)$resultado['quantidade_registros'])) * 100;
            
            return round($disponibilidade, 2);
            
        } catch (Exception $e) {
            error_log("Erro ao calcular disponibilidade: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * EFICIÊNCIA (%)
     * Fórmula: Produção boa / Total produção * 100
     * 
     * @param array $filtro
     * @return float
     */
    public function obterEficiencia($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalProducao = (float)$resultado['total_producao'];
            $totalPerdas = (float)$resultado['total_perdas'];
            
            if ($totalProducao == 0) {
                return 0;
            }
            
            $eficiencia = (($totalProducao - $totalPerdas) / $totalProducao) * 100;
            return round($eficiencia, 2);
            
        } catch (Exception $e) {
            error_log("Erro ao calcular eficiência: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * VELOCIDADE MÉDIA (%)
     * Fórmula: Velocidade média / Velocidade planejada * 100
     * Velocidade planejada assumida como 100 para cálculo percentual
     * 
     * @param array $filtro
     * @return float
     */
    public function obterVelocidadeMedia($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COALESCE(AVG(v.velocidade), 0) as velocidade_media
                FROM velocidades v
                INNER JOIN registros r ON v.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return round((float)$resultado['velocidade_media'], 2);
            
        } catch (Exception $e) {
            error_log("Erro ao calcular velocidade média: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * OEE (Overall Equipment Effectiveness) (%)
     * Fórmula: Disponibilidade * Eficiência * Velocidade / 10000
     * 
     * @param array $filtro
     * @return array
     */
    public function obterOEE($filtro = []) {
        try {
            $disponibilidade = $this->obterDisponibilidade($filtro);
            $eficiencia = $this->obterEficiencia($filtro);
            $velocidade = $this->obterVelocidadeMedia($filtro);
            
            // Se velocidade for 0, usar 100 como referência
            if ($velocidade == 0) {
                $velocidade = 100;
            }
            
            $oee = ($disponibilidade * $eficiencia * $velocidade) / 10000;
            
            return [
                'oee' => round($oee, 2),
                'disponibilidade' => $disponibilidade,
                'eficiencia' => $eficiencia,
                'velocidade' => $velocidade
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao calcular OEE: " . $e->getMessage());
            return ['oee' => 0, 'disponibilidade' => 0, 'eficiencia' => 0, 'velocidade' => 0];
        }
    }
    
    /**
     * PARADAS POR HORA (quantidade média)
     * 
     * @param array $filtro
     * @return float
     */
    public function obterParadasPorHora($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COUNT(p.id) as total_paradas,
                    COUNT(DISTINCT r.id) as quantidade_jornadas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $quantidade = (int)$resultado['quantidade_jornadas'];
            if ($quantidade == 0) {
                return 0;
            }
            
            // Jornada de 16 horas
            $horasTotais = $quantidade * 16;
            $paradasPorHora = (float)$resultado['total_paradas'] / $horasTotais;
            
            return round($paradasPorHora, 2);
            
        } catch (Exception $e) {
            error_log("Erro ao calcular paradas por hora: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * TEMPO PARADO DIÁRIO (horas)
     * 
     * @param array $filtro
     * @return float
     */
    public function obterTempoParadoDiario($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_parado_minutos,
                    COUNT(DISTINCT r.id) as quantidade_jornadas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $quantidade = (int)$resultado['quantidade_jornadas'];
            if ($quantidade == 0) {
                return 0;
            }
            
            $tempoDiario = ((float)$resultado['tempo_parado_minutos'] / 60) / $quantidade;
            return round($tempoDiario, 2);
            
        } catch (Exception $e) {
            error_log("Erro ao calcular tempo parado diário: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * INDICADORES RESUMIDOS - Retorna todos de uma vez
     * 
     * @param array $filtro
     * @return array
     */
    public function obterResumo($filtro = []) {
        try {
            return [
                'disponibilidade' => $this->obterDisponibilidade($filtro),
                'eficiencia' => $this->obterEficiencia($filtro),
                'velocidade_media' => $this->obterVelocidadeMedia($filtro),
                'oee' => $this->obterOEE($filtro),
                'paradas_por_hora' => $this->obterParadasPorHora($filtro),
                'tempo_parado_diario' => $this->obterTempoParadoDiario($filtro)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter resumo de indicadores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * COMPARATIVO DE PERÍODO
     * Compara dois períodos e mostra variação
     * 
     * @param array $filtro1
     * @param array $filtro2
     * @return array
     */
    public function obterComparativo($filtro1 = [], $filtro2 = []) {
        try {
            $resumo1 = $this->obterResumo($filtro1);
            $resumo2 = $this->obterResumo($filtro2);
            
            return [
                'periodo1' => $resumo1,
                'periodo2' => $resumo2,
                'variacao_disponibilidade' => round($resumo2['disponibilidade'] - $resumo1['disponibilidade'], 2),
                'variacao_eficiencia' => round($resumo2['eficiencia'] - $resumo1['eficiencia'], 2),
                'variacao_oee' => round($resumo2['oee']['oee'] - $resumo1['oee']['oee'], 2),
                'variacao_paradas' => round($resumo2['paradas_por_hora'] - $resumo1['paradas_por_hora'], 2)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao calcular comparativo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO TOTAL
     * 
     * @param array $filtro
     * @return array
     */
    public function obterProducaoTotal($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas,
                    COALESCE(SUM(prod.producao), 0) - COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_boa
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_producao' => (float)$resultado['total_producao'],
                'total_perdas' => (float)$resultado['total_perdas'],
                'total_boa' => (float)$resultado['total_boa']
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter produção total: " . $e->getMessage());
            return ['total_producao' => 0, 'total_perdas' => 0, 'total_boa' => 0];
        }
    }
    
    /**
     * TOTAL DE PARADAS
     * 
     * @param array $filtro
     * @return array
     */
    public function obterTotalParadas($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'quantidade' => (int)$resultado['quantidade'],
                'tempo_total_minutos' => (int)$resultado['tempo_total_minutos'],
                'tempo_total_horas' => round((float)$resultado['tempo_total_minutos'] / 60, 2)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter total de paradas: " . $e->getMessage());
            return ['quantidade' => 0, 'tempo_total_minutos' => 0, 'tempo_total_horas' => 0];
        }
    }
    
    /**
     * =====================================================
     * MÉTODOS AUXILIARES
     * =====================================================
     */
    
    /**
     * Montar cláusula WHERE dinamicamente
     */
    private function montarWhere($filtro = []) {
        $where = [];
        
        if (!empty($filtro['data_inicio']) && !empty($filtro['data_fim'])) {
            $where[] = "DATE(r.data_registro) BETWEEN :data_inicio AND :data_fim";
        }
        
        if (!empty($filtro['linha_id'])) {
            $where[] = "r.linha_id = :linha_id";
        }
        
        if (!empty($filtro['setor'])) {
            $where[] = "l.setor = :setor";
        }
        
        if (!empty($filtro['turno'])) {
            $where[] = "r.turno = :turno";
        }
        
        if (!empty($filtro['operador'])) {
            $where[] = "r.operador = :operador";
        }
        
        return count($where) > 0 ? " AND " . implode(" AND ", $where) : "";
    }
    
    /**
     * Vincular parâmetros à query
     */
    private function vincularParametros(&$stmt, $filtro = []) {
        if (!empty($filtro['data_inicio'])) {
            $stmt->bindValue(':data_inicio', $filtro['data_inicio']);
        }
        
        if (!empty($filtro['data_fim'])) {
            $stmt->bindValue(':data_fim', $filtro['data_fim']);
        }
        
        if (!empty($filtro['linha_id'])) {
            $stmt->bindValue(':linha_id', $filtro['linha_id'], PDO::PARAM_INT);
        }
        
        if (!empty($filtro['setor'])) {
            $stmt->bindValue(':setor', $filtro['setor']);
        }
        
        if (!empty($filtro['turno'])) {
            $stmt->bindValue(':turno', $filtro['turno']);
        }
        
        if (!empty($filtro['operador'])) {
            $stmt->bindValue(':operador', $filtro['operador']);
        }
    }
}
