# 📊 Camada de Consultas - Sistema Industrial JFM

## 🎯 O que é?

Um conjunto de classes reutilizáveis para consultas otimizadas do banco de dados, com indicadores industriais, filtros dinâmicos e exportação em CSV.

## 📦 Classes Disponíveis

### 1️⃣ RelatorioParadas
Análise completa de paradas com indicadores Pareto.

**Métodos:**
- `obterIndicadores()` - Quantidade, tempo, média, máx, mín
- `obterParetoMotivos()` - Top motivos de parada
- `obterParetoGrupos()` - Top grupos de parada
- `obterPorTurno()` - Agregação por turno
- `obterPorOperador()` - Agregação por operador
- `obterPorLinha()` - Agregação por linha
- `obterPorSetor()` - Agregação por setor
- `obterListagem()` - Dados completos com paginação
- `obterTendenciaDiaria()` - Gráfico de tendência
- `obterComparativos()` - Comparar dois períodos

### 2️⃣ RelatorioProducao
Análise de produção com taxa de sucesso.

**Métodos:**
- `obterIndicadores()` - Total, perdas, taxa sucesso
- `obterPorMarca()` - Agregação por marca
- `obterPorLinha()` - Agregação por linha
- `obterPorSetor()` - Agregação por setor
- `obterPorTurno()` - Agregação por turno
- `obterPorOperador()` - Agregação por operador
- `obterListagem()` - Dados com paginação
- `obterTendenciaDiaria()` - Gráfico de tendência

### 3️⃣ IndicadoresDashboard
Cálculos de KPIs: OEE, Disponibilidade, Eficiência, etc.

**Métodos:**
- `obterOEE()` - Overall Equipment Effectiveness
- `obterDisponibilidade()` - Percentual de disponibilidade
- `obterEficiencia()` - Percentual de eficiência
- `obterVelocidadeMedia()` - Velocidade média
- `obterParadasPorHora()` - Paradas por hora
- `obterTempoParadoDiario()` - Tempo parado em horas
- `obterResumo()` - Todos os indicadores de uma vez
- `obterComparativo()` - Comparar dois períodos
- `obterProducaoTotal()` - Total de produção
- `obterTotalParadas()` - Total de paradas

### 4️⃣ ExportadorCSV
Exportação de dados em CSV com indicadores.

**Métodos:**
- `paradas()` - Exportar paradas com indicadores
- `producao()` - Exportar produção com indicadores
- `paretoMotivos()` - Exportar Pareto de motivos
- `paretoGrupos()` - Exportar Pareto de grupos
- `analiseLinhas()` - Exportar análise por linhas

### 🏭 QueryFactory
Factory Pattern para criar instâncias facilmente.

```php
$paradas = QueryFactory::relatorioParadas($pdo);
$producao = QueryFactory::relatorioProducao($pdo);
$indicadores = QueryFactory::indicadores($pdo);
```

---

## 🚀 Como Usar

### Setup Básico
```php
<?php
require_once '../banco/conexao_pdo.php';
require_once 'autoload.php';

$relatorio = new RelatorioParradas($pdo);
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'setor' => 'PO'
];

$dados = $relatorio->obterIndicadores($filtro);
?>
```

### Exemplo: Pareto de Motivos
```php
$paretoMotivos = $relatorio->obterParetoMotivos($filtro, 10);

foreach ($paretoMotivos as $motivo) {
    echo $motivo['descricao'] . ": ";
    echo $motivo['quantidade'] . " ocorrências, ";
    echo $motivo['tempo_total_horas'] . " horas\n";
}
```

### Exemplo: Listagem com Paginação
```php
$resultado = $relatorio->obterListagem($filtro, $pagina = 1, $porPagina = 50);

echo "Total de registros: " . $resultado['total'];
echo "Página " . $resultado['pagina'] . " de " . $resultado['total_paginas'];

foreach ($resultado['dados'] as $item) {
    echo $item['descricao'] . "\n";
}
```

### Exemplo: Exportar em CSV
```php
$listagem = $relatorio->obterListagem($filtro);
$indicadores = $relatorio->obterIndicadores($filtro);

$exportador = new ExportadorCSV();
$exportador->paradas($listagem['dados'], 'paradas.csv', $indicadores);
// Gera download automático do arquivo
```

### Exemplo: OEE
```php
$indicadores = new IndicadoresDashboard($pdo);

$oee = $indicadores->obterOEE($filtro);

echo "OEE: " . $oee['oee'] . "%\n";
echo "Disponibilidade: " . $oee['disponibilidade'] . "%\n";
echo "Eficiência: " . $oee['eficiencia'] . "%\n";
echo "Velocidade: " . $oee['velocidade'] . "%\n";
```

---

## 📋 Estrutura de Filtros

```php
$filtro = [
    'data_inicio' => '2026-06-01',      // Opcional
    'data_fim' => '2026-06-28',         // Opcional
    'linha_id' => 5,                     // Opcional (ID da linha)
    'setor' => 'PO',                     // Opcional (PO, BAR, LIQ)
    'turno' => '1',                      // Opcional (1, 2, 3)
    'operador' => 'João Silva',          // Opcional
    'motivo' => '12',                    // Opcional (código)
    'grupo' => 'Limpeza',                // Opcional
    'tipo' => 'Planejada'                // Opcional
];
```

**Todos os filtros são opcionais e podem ser combinados.**

---

## 💡 Dicas Importantes

### ✅ Faça Assim
```php
// Usar factory para criar instâncias
$paradas = QueryFactory::relatorioParadas($pdo);

// Passar filtro como array
$filtro = ['data_inicio' => '2026-06-01', 'setor' => 'PO'];

// Usar prepared statements (automático)
// Todas as consultas são protegidas contra SQL injection
```

### ❌ Não Faça Assim
```php
// Não construir queries manualmente
// Não usar $_GET/$_POST diretamente
// Não usar SELECT *
// Não misturar bancos de dados
```

---

## 🔍 Vendo Erros

Todos os erros são registrados em `error_log()`.

```bash
# Verificar logs (Linux/Mac)
tail -f /var/log/php-errors.log

# No Windows, verificar
# c:\php\logs\php_errors.log (se configurado)
```

---

## 📈 Retorno de Dados

### Indicadores de Paradas
```php
[
    'quantidade_paradas' => 150,
    'tempo_total_minutos' => 4500,
    'tempo_total_horas' => 75.0,
    'tempo_medio_minutos' => 30.0,
    'tempo_maximo_minutos' => 120,
    'tempo_minimo_minutos' => 5,
    'quantidade_operadores' => 12,
    'quantidade_turnos' => 3
]
```

### Listagem Paginada
```php
[
    'dados' => [
        ['id' => 1, 'descricao' => '...', ...],
        ['id' => 2, 'descricao' => '...', ...]
    ],
    'total' => 1250,
    'pagina' => 1,
    'por_pagina' => 50,
    'total_paginas' => 25
]
```

### OEE
```php
[
    'oee' => 75.5,
    'disponibilidade' => 95.0,
    'eficiencia' => 92.0,
    'velocidade' => 85.0
]
```

---

## 📚 Documentação Completa

Veja [DOCUMENTACAO_QUERIES.md](../DOCUMENTACAO_QUERIES.md) para:
- Documentação técnica completa
- Todos os métodos listados
- Exemplos detalhados
- Estruturas de dados
- FAQ

---

## 🎯 Exemplo Prático Completo

Veja [paradas_avancado.php](../relatorios/paradas_avancado.php) para um relatório funcional completo com:
- Filtros avançados
- Indicadores em tempo real
- Pareto interativo
- Export CSV
- Paginação

---

## ✨ Características

✅ Prepared Statements (SQL Injection Safe)  
✅ Sem SELECT * (performance)  
✅ Filtros dinâmicos  
✅ Paginação automática  
✅ Tratamento de erros  
✅ Factory Pattern  
✅ 100% reutilizável  
✅ Bem documentado  

---

## 📞 Suporte

Para dúvidas:
1. Consulte a documentação em `autoload.php` (comentários das classes)
2. Veja exemplos em `paradas_avancado.php`
3. Leia `DOCUMENTACAO_QUERIES.md` completa

---

**Versão:** 1.0.0  
**Data:** 28/06/2026  
**Status:** ✅ Pronto para produção  
