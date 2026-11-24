<?php
/**
 * =====================================================
 * AUTOLOAD - Classes de Relatórios e Indicadores
 * =====================================================
 * Carrega automaticamente as classes de consultas
 * 
 * Usar no início do arquivo:
 * require_once '../includes/queries/autoload.php';
 */

// Classes de Relatórios
require_once __DIR__ . '/RelatorioParadas.php';
require_once __DIR__ . '/RelatorioProducao.php';
require_once __DIR__ . '/IndicadoresDashboard.php';
require_once __DIR__ . '/ExportadorCSV.php';

/**
 * Factory para criar instâncias das classes
 * 
 * Uso:
 * $paradas = QueryFactory::relatorioParadas($pdo);
 * $producao = QueryFactory::relatorioProducao($pdo);
 * $indicadores = QueryFactory::indicadores($pdo);
 */
class QueryFactory {
    
    public static function relatorioParadas($pdo) {
        return new RelatorioParadas($pdo);
    }
    
    public static function relatorioProducao($pdo) {
        return new RelatorioProducao($pdo);
    }
    
    public static function indicadores($pdo) {
        return new IndicadoresDashboard($pdo);
    }
    
    /**
     * Criar instâncias de todas as classes
     */
    public static function todas($pdo) {
        return [
            'paradas' => new RelatorioParadas($pdo),
            'producao' => new RelatorioProducao($pdo),
            'indicadores' => new IndicadoresDashboard($pdo)
        ];
    }
}
