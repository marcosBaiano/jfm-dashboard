<?php
/**
 * =====================================================
 * CLASSE: Relatório de Produção
 * =====================================================
 * Consultas otimizadas para análise de produção
 * 
 * Uso:
 * $rel = new RelatorioProducao($pdo);
 * $indicadores = $rel->obterIndicadores($filtro);
 * $producao = $rel->obterListagem($filtro);
 */

class RelatorioProducao {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * INDICADORES GERAIS - Quantidade, perdas, taxa de sucesso
     * 
     * @param array $filtro
     * @return array
     */
    public function obterIndicadores($filtro = []) {
        try {
            $sql = "
                SELECT 
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas,
                    COALESCE(SUM(prod.producao), 0) - COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_boa,
                    COUNT(DISTINCT prod.marca) as quantidade_marcas,
                    COUNT(DISTINCT prod.formato) as quantidade_formatos,
                    ROUND(
                        (COALESCE(SUM(prod.producao), 0) - COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0)) /
                        NULLIF(COALESCE(SUM(prod.producao), 0), 0) * 100,
                        2
                    ) as taxa_sucesso_percentual
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
                'quantidade_registros' => (int)$resultado['quantidade_registros'],
                'total_producao' => (float)$resultado['total_producao'],
                'total_perdas' => (float)$resultado['total_perdas'],
                'total_boa' => (float)$resultado['total_boa'],
                'quantidade_marcas' => (int)$resultado['quantidade_marcas'],
                'quantidade_formatos' => (int)$resultado['quantidade_formatos'],
                'taxa_sucesso_percentual' => (float)$resultado['taxa_sucesso_percentual'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao obter indicadores de produção: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO POR MARCA
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorMarca($filtro = []) {
        try {
            $sql = "
                SELECT 
                    prod.marca,
                    COUNT(prod.id) as quantidade,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas,
                    ROUND(
                        (COALESCE(SUM(prod.producao), 0) - COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0)) /
                        NULLIF(COALESCE(SUM(prod.producao), 0), 0) * 100,
                        2
                    ) as taxa_sucesso_percentual
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY prod.marca
                ORDER BY total_producao DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter produção por marca: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO POR LINHA
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
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY l.id, l.codigo, l.linha, l.setor
                ORDER BY total_producao DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter produção por linha: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO POR SETOR
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorSetor($filtro = []) {
        try {
            $sql = "
                SELECT 
                    l.setor,
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY l.setor
                ORDER BY total_producao DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter produção por setor: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO POR TURNO
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorTurno($filtro = []) {
        try {
            $sql = "
                SELECT 
                    r.turno,
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
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
            error_log("Erro ao obter produção por turno: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * PRODUÇÃO POR OPERADOR
     * 
     * @param array $filtro
     * @return array
     */
    public function obterPorOperador($filtro = []) {
        try {
            $sql = "
                SELECT 
                    r.operador,
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY r.operador
                ORDER BY total_producao DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter produção por operador: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * LISTAGEM COMPLETA COM FILTROS
     * 
     * @param array $filtro
     * @param int $pagina
     * @param int $porPagina
     * @return array
     */
    public function obterListagem($filtro = [], $pagina = 1, $porPagina = 50) {
        try {
            $offset = ($pagina - 1) * $porPagina;
            
            // Total de registros
            $sqlTotal = "
                SELECT COUNT(prod.id) as total
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro);
            
            $stmt = $this->pdo->prepare($sqlTotal);
            $this->vincularParametros($stmt, $filtro);
            $stmt->execute();
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Dados paginados
            $sql = "
                SELECT 
                    prod.id,
                    prod.marca,
                    prod.formato,
                    prod.producao,
                    prod.perda_1,
                    prod.perda_2,
                    prod.perda_3,
                    prod.perda_4,
                    (prod.producao - COALESCE(prod.perda_1, 0) - COALESCE(prod.perda_2, 0) - COALESCE(prod.perda_3, 0) - COALESCE(prod.perda_4, 0)) as producao_boa,
                    (prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)) as total_perdas,
                    r.data_registro,
                    r.turno,
                    r.operador,
                    l.id as linha_id,
                    l.codigo as codigo_linha,
                    l.setor,
                    l.linha,
                    prod.criado_em
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                ORDER BY r.data_registro DESC
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
            error_log("Erro ao obter listagem de produção: " . $e->getMessage());
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
                    DATE(r.data_registro) as data,
                    COUNT(prod.id) as quantidade_registros,
                    COALESCE(SUM(prod.producao), 0) as total_producao,
                    COALESCE(SUM(prod.perda_1 + COALESCE(prod.perda_2, 0) + COALESCE(prod.perda_3, 0) + COALESCE(prod.perda_4, 0)), 0) as total_perdas
                FROM producao prod
                INNER JOIN registros r ON prod.registro_id = r.id
                INNER JOIN linhas l ON r.linha_id = l.id
                WHERE 1=1
            " . $this->montarWhere($filtro) . "
                GROUP BY DATE(r.data_registro)
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
        
        if (!empty($filtro['marca'])) {
            $where[] = "prod.marca = :marca";
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
        
        if (!empty($filtro['marca'])) {
            $stmt->bindValue(':marca', $filtro['marca']);
        }
    }
}
