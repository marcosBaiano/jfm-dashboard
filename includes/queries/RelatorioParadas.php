<?php
/**
 * =====================================================
 * CLASSE: Relatório de Paradas
 * =====================================================
 * Consultas otimizadas para análise de paradas
 * Sem SELECT *, com prepared statements
 * 
 * Uso:
 * $rel = new RelatorioParadas($pdo);
 * $indicadores = $rel->obterIndicadores($filtro);
 * $paradas = $rel->obterListagem($filtro);
 * $pareto = $rel->obterParetoMotivos($filtro);
 */

class RelatorioParadas {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * INDICADORES GERAIS - Quantidade, tempo, média, máx, mín
     * 
     * @param array $filtro ['data_inicio', 'data_fim', 'linha_id', 'setor', 'turno', 'operador']
     * @return array
     */
    public function obterIndicadores($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COUNT(p.id) as quantidade_paradas,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    CASE 
                        WHEN COUNT(p.id) > 0 
                        THEN ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / COUNT(p.id), 2)
                        ELSE 0
                    END as tempo_medio_minutos,
                    COALESCE(MAX(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_maximo_minutos,
                    COALESCE(MIN(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_minimo_minutos,
                    COUNT(DISTINCT r.operador) as quantidade_operadores,
                    COUNT(DISTINCT r.turno) as quantidade_turnos,
                    COUNT(DISTINCT d.codigo) as quantidade_motivos_unicos
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'quantidade_paradas' => (int)$resultado['quantidade_paradas'],
                'tempo_total_minutos' => (int)$resultado['tempo_total_minutos'],
                'tempo_total_horas' => round($resultado['tempo_total_minutos'] / 60, 2),
                'tempo_medio_minutos' => (float)$resultado['tempo_medio_minutos'],
                'tempo_maximo_minutos' => (int)$resultado['tempo_maximo_minutos'],
                'tempo_minimo_minutos' => (int)$resultado['tempo_minimo_minutos'],
                'quantidade_operadores' => (int)$resultado['quantidade_operadores'],
                'quantidade_turnos' => (int)$resultado['quantidade_turnos'],
                'quantidade_motivos_unicos' => (int)$resultado['quantidade_motivos_unicos']
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter indicadores de paradas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARETO DE MOTIVOS - Top motivos que causam maior impacto
     * 
     * @param array $filtro
     * @param int $limite
     * @return array
     */
    public function obterParetoMotivos($filtro = [], $limite = 10) {
        try {
            $sql = "
                SELECT 
                    d.id,
                    d.codigo,
                    d.descricao,
                    d.grupo,
                    d.subgrupo,
                    d.tipo,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas,
                    CASE 
                        WHEN COUNT(p.id) > 0 
                        THEN ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / COUNT(p.id), 2)
                        ELSE 0
                    END as tempo_medio_minutos
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo AND (d.linha_id = l.id OR d.setor = l.setor OR d.linha_id IS NULL)
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY d.id, d.codigo, d.descricao
                ORDER BY tempo_total_minutos DESC
                LIMIT :limite
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter Pareto de motivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARETO DE GRUPOS - Top grupos que causam maior impacto
     * 
     * @param array $filtro
     * @param int $limite
     * @return array
     */
    public function obterParetoGrupos($filtro = [], $limite = 10) {
        try {
            $sql = "
                SELECT 
                    d.grupo,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo
                WHERE 1=1 AND d.grupo IS NOT NULL
            " . $this->montarWhere($filtro) . "
                GROUP BY d.grupo
                ORDER BY tempo_total_minutos DESC
                LIMIT :limite
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter Pareto de grupos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARADAS POR TURNO
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorTurno($filtro = []) {
        try {
            $sql = "
                SELECT 
                    r.turno,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY r.turno
                ORDER BY r.turno ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter paradas por turno: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARADAS POR OPERADOR
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorOperador($filtro = []) {
        try {
            $sql = "
                SELECT 
                    r.operador,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY r.operador
                ORDER BY tempo_total_minutos DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter paradas por operador: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARADAS POR LINHA
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorLinha($filtro = []) {
        try {
            $sql = "
                SELECT 
                    l.id,
                    l.codigo,
                    l.linha,
                    l.setor,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY l.id, l.codigo, l.linha, l.setor
                ORDER BY tempo_total_minutos DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter paradas por linha: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PARADAS POR SETOR
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorSetor($filtro = []) {
        try {
            $sql = "
                SELECT 
                    l.setor,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos,
                    ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) / 60, 2) as tempo_total_horas
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY l.setor
                ORDER BY tempo_total_minutos DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter paradas por setor: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * LISTAGEM COMPLETA COM FILTROS
     * 
     * @param array $filtro
     * @param int $pagina
     * @param int $porPagina
     * @return array ['dados' => [...], 'total' => 100]
     */
    public function obterListagem($filtro = [], $pagina = 1, $porPagina = 50) {
        try {
            $offset = ($pagina - 1) * $porPagina;
            
            // Total de registros
            $sqlTotal = "
                SELECT COUNT(p.id) as total
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sqlTotal);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Dados paginados
            $sql = "
                SELECT 
                    p.id,
                    p.inicio,
                    p.fim,
                    TIMESTAMPDIFF(MINUTE, p.inicio, p.fim) as duracao_minutos,
                    ROUND(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim) / 60, 2) as duracao_horas,
                    p.codigo,
                    d.descricao,
                    d.grupo,
                    d.subgrupo,
                    d.tipo,
                    r.operador,
                    r.turno,
                    r.data_registro,
                    l.id as linha_id,
                    l.codigo as codigo_linha,
                    l.setor,
                    l.linha,
                    p.observacoes,
                    p.criado_em
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                LEFT JOIN descricoes d ON p.codigo = d.codigo
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                ORDER BY r.data_registro DESC, p.inicio DESC
                LIMIT :offset, :por_pagina
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':por_pagina', $porPagina, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'total_paginas' => ceil($total / $porPagina)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter listagem de paradas: " . $e->getMessage());
            return ['dados' => [], 'total' => 0, 'pagina' => 1, 'por_pagina' => 50, 'total_paginas' => 0];
        }
    }
    
    /**
     * TENDÊNCIA DIÁRIA
     * 
     * @param array $filtro
     * @return array
     */
    public function obterTendenciaDiaria($filtro = []) {
        try {
            $sql = "
                SELECT 
                    DATE(p.inicio) as data,
                    COUNT(p.id) as quantidade,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.inicio, p.fim)), 0) as tempo_total_minutos
                FROM paradas p
                INNER JOIN registros r ON p.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY DATE(p.inicio)
                ORDER BY data ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter tendência diária: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * COMPARATIVO ENTRE PERÍODOS
     * 
     * @param array $filtro1
     * @param array $filtro2
     * @return array
     */
    public function obterComparativos($filtro1 = [], $filtro2 = []) {
        try {
            $periodo1 = $this->obterIndicadores($filtro1);
            $periodo2 = $this->obterIndicadores($filtro2);
            
            return [
                'periodo1' => $periodo1,
                'periodo2' => $periodo2,
                'variacao_quantidade' => $periodo2['quantidade_paradas'] - $periodo1['quantidade_paradas'],
                'variacao_tempo' => $periodo2['tempo_total_minutos'] - $periodo1['tempo_total_minutos'],
                'variacao_tempo_horas' => round(($periodo2['tempo_total_minutos'] - $periodo1['tempo_total_minutos']) / 60, 2)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter comparativos: " . $e->getMessage());
            return [];
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
            $where[] = "DATE(p.inicio) BETWEEN :data_inicio AND :data_fim";
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
        
        if (!empty($filtro['motivo'])) {
            $where[] = "p.codigo = :motivo";
        }
        
        if (!empty($filtro['grupo'])) {
            $where[] = "d.grupo = :grupo";
        }
        
        if (!empty($filtro['tipo'])) {
            $where[] = "d.tipo = :tipo";
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
        
        if (!empty($filtro['motivo'])) {
            $stmt->bindValue(':motivo', $filtro['motivo']);
        }
        
        if (!empty($filtro['grupo'])) {
            $stmt->bindValue(':grupo', $filtro['grupo']);
        }
        
        if (!empty($filtro['tipo'])) {
            $stmt->bindValue(':tipo', $filtro['tipo']);
        }
    }
}
