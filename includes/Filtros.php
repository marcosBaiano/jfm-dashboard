<?php
/**
 * =====================================================
 * CLASSE: Filtro Global
 * =====================================================
 * Gerencia todos os filtros compartilhados entre dashboards
 * 
 * Uso:
 * $filtro = new FiltroGlobal($pdo);
 * $filtro->aplicarFiltros($_GET);
 * $where = $filtro->getWhereClauses();
 */

class FiltroGlobal {
    private $pdo;
    private $filtros = [];
    private $params = [];
    
    /**
     * Construtor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->inicializarFiltrosDefault();
    }
    
    /**
     * Inicializa filtros com valores padrão
     */
    private function inicializarFiltrosDefault() {
        $this->filtros = [
            'data_inicio' => date('Y-m-01'),
            'data_fim' => date('Y-m-d'),
            'turno' => null,
            'setor_id' => null,
            'linha_id' => null,
            'maquina_id' => null,
            'grupo_parada_id' => null,
            'motivo_parada_id' => null,
            'filtro_rapido' => null
        ];
    }
    
    /**
     * Aplicar filtros do GET/POST
     */
    public function aplicarFiltros($dados = []) {
        // Atalhos rápidos
        if (!empty($dados['filtro_rapido'])) {
            $this->aplicarFiltroRapido($dados['filtro_rapido']);
        }
        
        // Data
        if (!empty($dados['data_inicio']) && $this->validarData($dados['data_inicio'])) {
            $this->filtros['data_inicio'] = $dados['data_inicio'];
        }
        
        if (!empty($dados['data_fim']) && $this->validarData($dados['data_fim'])) {
            $this->filtros['data_fim'] = $dados['data_fim'];
        }
        
        // Dropdowns
        if (!empty($dados['turno'])) {
            $this->filtros['turno'] = $dados['turno'];
        }
        
        if (!empty($dados['setor_id'])) {
            $this->filtros['setor_id'] = (int)$dados['setor_id'];
        }
        
        if (!empty($dados['linha_id'])) {
            $this->filtros['linha_id'] = (int)$dados['linha_id'];
        }
        
        if (!empty($dados['maquina_id'])) {
            $this->filtros['maquina_id'] = (int)$dados['maquina_id'];
        }
        
        if (!empty($dados['grupo_parada_id'])) {
            $this->filtros['grupo_parada_id'] = (int)$dados['grupo_parada_id'];
        }
        
        if (!empty($dados['motivo_parada_id'])) {
            $this->filtros['motivo_parada_id'] = (int)$dados['motivo_parada_id'];
        }
    }
    
    /**
     * Aplicar filtros rápidos
     */
    private function aplicarFiltroRapido($filtro) {
        $hoje = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));
        
        switch($filtro) {
            case 'hoje':
                $this->filtros['data_inicio'] = $hoje;
                $this->filtros['data_fim'] = $hoje;
                break;
            case 'ontem':
                $this->filtros['data_inicio'] = $ontem;
                $this->filtros['data_fim'] = $ontem;
                break;
            case 'ultimos_7':
                $this->filtros['data_inicio'] = date('Y-m-d', strtotime('-7 days'));
                $this->filtros['data_fim'] = $hoje;
                break;
            case 'ultimos_30':
                $this->filtros['data_inicio'] = date('Y-m-d', strtotime('-30 days'));
                $this->filtros['data_fim'] = $hoje;
                break;
            case 'ultimos_90':
                $this->filtros['data_inicio'] = date('Y-m-d', strtotime('-90 days'));
                $this->filtros['data_fim'] = $hoje;
                break;
            case 'mes_atual':
                $this->filtros['data_inicio'] = date('Y-m-01');
                $this->filtros['data_fim'] = $hoje;
                break;
            case 'mes_anterior':
                $primeiro = date('Y-m-01', strtotime('-1 month'));
                $ultimo = date('Y-m-t', strtotime('-1 month'));
                $this->filtros['data_inicio'] = $primeiro;
                $this->filtros['data_fim'] = $ultimo;
                break;
        }
    }
    
    /**
     * Validar formato de data
     */
    private function validarData($data) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? true : false;
    }
    
    /**
     * Obter filtros atual
     */
    public function getFiltros() {
        return $this->filtros;
    }
    
    /**
     * Construir WHERE clauses para queries
     */
    public function getWhereClauses($alias_registros = 'r', $alias_paradas = 'p') {
        $clauses = [];
        
        // Data (sempre aplicada em registros)
        if ($this->filtros['data_inicio'] && $this->filtros['data_fim']) {
            $clauses[] = "DATE({$alias_registros}.data_registro) BETWEEN '{$this->filtros['data_inicio']}' AND '{$this->filtros['data_fim']}'";
        }
        
        // Turno
        if ($this->filtros['turno']) {
            $clauses[] = "{$alias_registros}.turno = '{$this->filtros['turno']}'";
        }
        
        // Setor
        if ($this->filtros['setor_id']) {
            $clauses[] = "{$alias_registros}.setor_id = {$this->filtros['setor_id']}";
        }
        
        // Linha
        if ($this->filtros['linha_id']) {
            $clauses[] = "{$alias_registros}.linha_id = {$this->filtros['linha_id']}";
        }
        
        // Máquina
        if ($this->filtros['maquina_id']) {
            $clauses[] = "{$alias_registros}.maquina_id = {$this->filtros['maquina_id']}";
        }
        
        // Grupo de Parada
        if ($this->filtros['grupo_parada_id']) {
            $clauses[] = "{$alias_paradas}.grupo_parada_id = {$this->filtros['grupo_parada_id']}";
        }
        
        // Motivo de Parada
        if ($this->filtros['motivo_parada_id']) {
            $clauses[] = "{$alias_paradas}.motivo_parada_id = {$this->filtros['motivo_parada_id']}";
        }
        
        return !empty($clauses) ? implode(' AND ', $clauses) : '1=1';
    }
    
    /**
     * Obter URL com parâmetros de filtro (para paginação/exportação)
     */
    public function getUrlParams() {
        $params = [];
        
        foreach ($this->filtros as $chave => $valor) {
            if ($valor !== null && $valor !== '') {
                $params[] = "{$chave}=" . urlencode($valor);
            }
        }
        
        return implode('&', $params);
    }
    
    /**
     * Obter dados para dropdown de Setores
     */
    public function getSetores() {
        $stmt = $this->pdo->query("SELECT id, nome FROM setores WHERE ativa = 1 ORDER BY nome");
        return $stmt->fetchAll();
    }
    
    /**
     * Obter dados para dropdown de Linhas
     */
    public function getLinhas() {
        $stmt = $this->pdo->query("SELECT id, linha FROM linhas ORDER BY linha");
        return $stmt->fetchAll();
    }
    
    /**
     * Obter dados para dropdown de Máquinas
     */
    public function getMaquinas() {
        $sql = "SELECT id, nome FROM maquinas WHERE ativa = 1";
        
        if ($this->filtros['setor_id']) {
            $sql .= " AND setor_id = {$this->filtros['setor_id']}";
        }
        
        $sql .= " ORDER BY nome";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obter dados para dropdown de Grupos de Parada
     */
    public function getGruposParada() {
        $stmt = $this->pdo->query("SELECT id, nome FROM grupos_parada ORDER BY nome");
        return $stmt->fetchAll();
    }
    
    /**
     * Obter dados para dropdown de Motivos de Parada
     */
    public function getMotivosParada() {
        $sql = "SELECT id, codigo, motivo FROM motivos_parada WHERE ativo = 1";
        
        if ($this->filtros['grupo_parada_id']) {
            $sql .= " AND grupo_parada_id = {$this->filtros['grupo_parada_id']}";
        }
        
        $sql .= " ORDER BY codigo";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}

?>
