<?php
/**
 * =====================================================
 * CLASSE: Exportador CSV
 * =====================================================
 * Exporta dados de relatórios em CSV
 * 
 * Uso:
 * $exp = new ExportadorCSV();
 * $exp->paradas($dados, 'relatorio_paradas.csv');
 * $exp->producao($dados, 'relatorio_producao.csv');
 */

class ExportadorCSV {
    
    /**
     * Exportar relatório de paradas em CSV
     * 
     * @param array $dados
     * @param string $nomeArquivo
     * @param array $indicadores (opcional)
     */
    public function paradas($dados = [], $nomeArquivo = null, $indicadores = null) {
        if (empty($nomeArquivo)) {
            $nomeArquivo = 'relatorio_paradas_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $this->enviarHeaders($nomeArquivo);
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalhos de indicadores
        if (!empty($indicadores)) {
            fputcsv($output, ['RELATÓRIO DE PARADAS'], ';');
            fputcsv($output, ['Data de Geração', date('d/m/Y H:i:s')], ';');
            fputcsv($output, [], ';');
            
            fputcsv($output, ['INDICADORES'], ';');
            fputcsv($output, ['Quantidade de Paradas', $indicadores['quantidade_paradas'] ?? 0], ';');
            fputcsv($output, ['Tempo Total (minutos)', $indicadores['tempo_total_minutos'] ?? 0], ';');
            fputcsv($output, ['Tempo Total (horas)', $indicadores['tempo_total_horas'] ?? 0], ';');
            fputcsv($output, ['Tempo Médio (minutos)', $indicadores['tempo_medio_minutos'] ?? 0], ';');
            fputcsv($output, ['Tempo Máximo (minutos)', $indicadores['tempo_maximo_minutos'] ?? 0], ';');
            fputcsv($output, ['Tempo Mínimo (minutos)', $indicadores['tempo_minimo_minutos'] ?? 0], ';');
            fputcsv($output, ['Quantidade de Operadores', $indicadores['quantidade_operadores'] ?? 0], ';');
            fputcsv($output, ['Quantidade de Turnos', $indicadores['quantidade_turnos'] ?? 0], ';');
            fputcsv($output, [], ';');
        }
        
        // Cabeçalhos das colunas
        fputcsv($output, [
            'Data',
            'Hora Início',
            'Hora Fim',
            'Linha',
            'Setor',
            'Código',
            'Motivo',
            'Grupo',
            'Tipo',
            'Operador',
            'Turno',
            'Duração (min)',
            'Duração (h)',
            'Observações'
        ], ';');
        
        // Dados
        foreach ($dados as $item) {
            fputcsv($output, [
                isset($item['data_registro']) ? $this->formatarData($item['data_registro']) : '',
                isset($item['inicio']) ? $this->formatarHora($item['inicio']) : '',
                isset($item['fim']) ? $this->formatarHora($item['fim']) : '',
                $item['linha'] ?? '',
                $item['setor'] ?? '',
                $item['codigo'] ?? '',
                $item['descricao'] ?? '',
                $item['grupo'] ?? '',
                $item['tipo'] ?? '',
                $item['operador'] ?? '',
                $item['turno'] ?? '',
                $item['duracao_minutos'] ?? 0,
                $item['duracao_horas'] ?? 0,
                $item['observacoes'] ?? ''
            ], ';');
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Exportar relatório de produção em CSV
     * 
     * @param array $dados
     * @param string $nomeArquivo
     * @param array $indicadores (opcional)
     */
    public function producao($dados = [], $nomeArquivo = null, $indicadores = null) {
        if (empty($nomeArquivo)) {
            $nomeArquivo = 'relatorio_producao_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $this->enviarHeaders($nomeArquivo);
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalhos de indicadores
        if (!empty($indicadores)) {
            fputcsv($output, ['RELATÓRIO DE PRODUÇÃO'], ';');
            fputcsv($output, ['Data de Geração', date('d/m/Y H:i:s')], ';');
            fputcsv($output, [], ';');
            
            fputcsv($output, ['INDICADORES'], ';');
            fputcsv($output, ['Total de Produção', $indicadores['total_producao'] ?? 0], ';');
            fputcsv($output, ['Total de Perdas', $indicadores['total_perdas'] ?? 0], ';');
            fputcsv($output, ['Total de Produção Boa', $indicadores['total_boa'] ?? 0], ';');
            fputcsv($output, ['Taxa de Sucesso (%)', $indicadores['taxa_sucesso_percentual'] ?? 0], ';');
            fputcsv($output, ['Quantidade de Marcas', $indicadores['quantidade_marcas'] ?? 0], ';');
            fputcsv($output, [], ';');
        }
        
        // Cabeçalhos das colunas
        fputcsv($output, [
            'Data',
            'Marca',
            'Formato',
            'Produção',
            'Perda 1',
            'Perda 2',
            'Perda 3',
            'Perda 4',
            'Total Perdas',
            'Produção Boa',
            'Linha',
            'Setor',
            'Operador',
            'Turno'
        ], ';');
        
        // Dados
        foreach ($dados as $item) {
            $totalPerdas = ($item['perda_1'] ?? 0) + ($item['perda_2'] ?? 0) + ($item['perda_3'] ?? 0) + ($item['perda_4'] ?? 0);
            $producaoBoa = ($item['producao'] ?? 0) - $totalPerdas;
            
            fputcsv($output, [
                isset($item['data_registro']) ? $this->formatarData($item['data_registro']) : '',
                $item['marca'] ?? '',
                $item['formato'] ?? '',
                $item['producao'] ?? 0,
                $item['perda_1'] ?? 0,
                $item['perda_2'] ?? 0,
                $item['perda_3'] ?? 0,
                $item['perda_4'] ?? 0,
                $totalPerdas,
                $producaoBoa,
                $item['linha'] ?? '',
                $item['setor'] ?? '',
                $item['operador'] ?? '',
                $item['turno'] ?? ''
            ], ';');
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Exportar Pareto de motivos em CSV
     * 
     * @param array $dados
     * @param string $nomeArquivo
     */
    public function paretoMotivos($dados = [], $nomeArquivo = null) {
        if (empty($nomeArquivo)) {
            $nomeArquivo = 'pareto_motivos_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $this->enviarHeaders($nomeArquivo);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['ANÁLISE PARETO - MOTIVOS DE PARADA'], ';');
        fputcsv($output, ['Data de Geração', date('d/m/Y H:i:s')], ';');
        fputcsv($output, [], ';');
        
        fputcsv($output, [
            'Código',
            'Motivo',
            'Grupo',
            'Subgrupo',
            'Tipo',
            'Quantidade',
            'Tempo Total (h)',
            'Tempo Médio (min)'
        ], ';');
        
        $acumulado = 0;
        $total = array_sum(array_column($dados, 'tempo_total_minutos'));
        
        foreach ($dados as $item) {
            $acumulado += $item['tempo_total_minutos'];
            $percentualAcumulado = ($total > 0) ? round(($acumulado / $total) * 100, 2) : 0;
            
            fputcsv($output, [
                $item['codigo'] ?? '',
                $item['descricao'] ?? '',
                $item['grupo'] ?? '',
                $item['subgrupo'] ?? '',
                $item['tipo'] ?? '',
                $item['quantidade'] ?? 0,
                $item['tempo_total_horas'] ?? 0,
                $item['tempo_medio_minutos'] ?? 0,
                $percentualAcumulado . '%'
            ], ';');
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Exportar Pareto de grupos em CSV
     * 
     * @param array $dados
     * @param string $nomeArquivo
     */
    public function paretoGrupos($dados = [], $nomeArquivo = null) {
        if (empty($nomeArquivo)) {
            $nomeArquivo = 'pareto_grupos_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $this->enviarHeaders($nomeArquivo);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['ANÁLISE PARETO - GRUPOS DE PARADA'], ';');
        fputcsv($output, ['Data de Geração', date('d/m/Y H:i:s')], ';');
        fputcsv($output, [], ';');
        
        fputcsv($output, [
            'Grupo',
            'Quantidade',
            'Tempo Total (h)',
            'Percentual Acumulado'
        ], ';');
        
        $acumulado = 0;
        $total = array_sum(array_column($dados, 'tempo_total_minutos'));
        
        foreach ($dados as $item) {
            $acumulado += $item['tempo_total_minutos'];
            $percentualAcumulado = ($total > 0) ? round(($acumulado / $total) * 100, 2) : 0;
            
            fputcsv($output, [
                $item['grupo'] ?? 'Não classificado',
                $item['quantidade'] ?? 0,
                $item['tempo_total_horas'] ?? 0,
                $percentualAcumulado . '%'
            ], ';');
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Exportar análise por linha em CSV
     * 
     * @param array $dados
     * @param string $nomeArquivo
     * @param string $tipo ('paradas' ou 'producao')
     */
    public function analiseLinhas($dados = [], $nomeArquivo = null, $tipo = 'paradas') {
        if (empty($nomeArquivo)) {
            $nomeArquivo = 'analise_linhas_' . $tipo . '_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $this->enviarHeaders($nomeArquivo);
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['ANÁLISE POR LINHA - ' . strtoupper($tipo)], ';');
        fputcsv($output, ['Data de Geração', date('d/m/Y H:i:s')], ';');
        fputcsv($output, [], ';');
        
        if ($tipo === 'paradas') {
            fputcsv($output, [
                'Linha',
                'Código',
                'Setor',
                'Quantidade',
                'Tempo Total (h)'
            ], ';');
            
            foreach ($dados as $item) {
                fputcsv($output, [
                    $item['linha'] ?? '',
                    $item['codigo'] ?? '',
                    $item['setor'] ?? '',
                    $item['quantidade'] ?? 0,
                    $item['tempo_total_horas'] ?? 0
                ], ';');
            }
        } else {
            fputcsv($output, [
                'Linha',
                'Código',
                'Setor',
                'Quantidade Registros',
                'Total Produção',
                'Total Perdas'
            ], ';');
            
            foreach ($dados as $item) {
                fputcsv($output, [
                    $item['linha'] ?? '',
                    $item['codigo'] ?? '',
                    $item['setor'] ?? '',
                    $item['quantidade_registros'] ?? 0,
                    $item['total_producao'] ?? 0,
                    $item['total_perdas'] ?? 0
                ], ';');
            }
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * =====================================================
     * MÉTODOS AUXILIARES
     * =====================================================
     */
    
    private function enviarHeaders($nomeArquivo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    private function formatarData($data) {
        if (empty($data)) return '';
        try {
            return date('d/m/Y', strtotime($data));
        } catch (Exception $e) {
            return '';
        }
    }
    
    private function formatarHora($hora) {
        if (empty($hora)) return '';
        try {
            return date('H:i:s', strtotime($hora));
        } catch (Exception $e) {
            return '';
        }
    }
}
