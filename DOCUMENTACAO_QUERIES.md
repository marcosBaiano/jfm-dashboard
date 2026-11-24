# Camada de Consultas Otimizadas - Documentação

## 📍 Localização
`/includes/queries/` - Todas as classes de relatórios e indicadores

## 📦 Classes Disponíveis

### 1. RelatorioParadas
**Arquivo:** `RelatorioParadas.php`

Consultas otimizadas para análise de paradas com indicadores.

#### Métodos principais:
```php
$rel = new RelatorioParadas($pdo);

// Indicadores gerais
$indicadores = $rel->obterIndicadores($filtro);
// Retorna: quantidade, tempo_total, média, máx, mín, etc

// Pareto de motivos (Top 10 motivos que causam maior impacto)
$paretoMotivos = $rel->obterParetoMotivos($filtro, 10);

// Pareto de grupos
$paretoGrupos = $rel->obterParetoGrupos($filtro, 10);

// Agregações por dimensão
$porTurno = $rel->obterPorTurno($filtro);
$porOperador = $rel->obterPorOperador($filtro);
$porLinha = $rel->obterPorLinha($filtro);
$porSetor = $rel->obterSetor($filtro);

// Listagem com paginação
$resultado = $rel->obterListagem($filtro, $pagina = 1, $porPagina = 50);
// Retorna: ['dados' => [...], 'total' => 100, 'pagina' => 1, 'total_paginas' => 2]

// Tendência diária
$tendencia = $rel->obterTendenciaDiaria($filtro);

// Comparativo entre períodos
$comparativo = $rel->obterComparativos($filtro1, $filtro2);
```

#### Estrutura do Filtro:
```php
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'linha_id' => 5,           // Opcional
    'setor' => 'PO',           // Opcional
    'turno' => '1',            // Opcional
    'operador' => 'João',      // Opcional
    'motivo' => '12',          // Opcional
    'grupo' => 'Limpeza',      // Opcional
    'tipo' => 'Planejada'      // Opcional
];
```

---

### 2. RelatorioProducao
**Arquivo:** `RelatorioProducao.php`

Consultas otimizadas para análise de produção.

#### Métodos principais:
```php
$rel = new RelatorioProducao($pdo);

// Indicadores gerais
$indicadores = $rel->obterIndicadores($filtro);
// Retorna: total_producao, total_perdas, total_boa, taxa_sucesso_percentual

// Agregações por dimensão
$porMarca = $rel->obterPorMarca($filtro);
$porLinha = $rel->obterPorLinha($filtro);
$porSetor = $rel->obterPorSetor($filtro);
$porTurno = $rel->obterPorTurno($filtro);
$porOperador = $rel->obterPorOperador($filtro);

// Listagem com paginação
$resultado = $rel->obterListagem($filtro, $pagina = 1, $porPagina = 50);

// Tendência diária
$tendencia = $rel->obterTendenciaDiaria($filtro);
```

---

### 3. IndicadoresDashboard
**Arquivo:** `IndicadoresDashboard.php`

Cálculos de indicadores de performance: OEE, Disponibilidade, Eficiência, etc.

#### Métodos principais:
```php
$ind = new IndicadoresDashboard($pdo);

// OEE (Overall Equipment Effectiveness) - %
$oee = $ind->obterOEE($filtro);
// Retorna: ['oee' => 75.5, 'disponibilidade' => 95.0, 'eficiencia' => 92.0, 'velocidade' => 85.0]

// Disponibilidade - %
$disponibilidade = $ind->obterDisponibilidade($filtro);

// Eficiência - %
$eficiencia = $ind->obterEficiencia($filtro);

// Velocidade média
$velocidade = $ind->obterVelocidadeMedia($filtro);

// Paradas por hora
$paradasPorHora = $ind->obterParadasPorHora($filtro);

// Tempo parado diário (horas)
$tempoParado = $ind->obterTempoParadoDiario($filtro);

// Resumo de todos os indicadores
$resumo = $ind->obterResumo($filtro);

// Comparativo entre dois períodos
$comparativo = $ind->obterComparativo($filtro1, $filtro2);

// Produção total
$producao = $ind->obterProducaoTotal($filtro);

// Total de paradas
$paradas = $ind->obterTotalParadas($filtro);
```

---

### 4. ExportadorCSV
**Arquivo:** `ExportadorCSV.php`

Exportação de dados em CSV (respeitando filtros aplicados).

#### Métodos principais:
```php
$exp = new ExportadorCSV();

// Exportar paradas
$exp->paradas($dados, $nomeArquivo, $indicadores);

// Exportar produção
$exp->producao($dados, $nomeArquivo, $indicadores);

// Exportar Pareto de motivos
$exp->paretoMotivos($dados, $nomeArquivo);

// Exportar Pareto de grupos
$exp->paretoGrupos($dados, $nomeArquivo);

// Exportar análise por linhas
$exp->analiseLinhas($dados, $nomeArquivo, 'paradas');
$exp->analiseLinhas($dados, $nomeArquivo, 'producao');
```

---

## 🔧 Factory para Simplificar
**Arquivo:** `autoload.php`

```php
require_once '../includes/queries/autoload.php';

// Criar instâncias individuais
$paradas = QueryFactory::relatorioParadas($pdo);
$producao = QueryFactory::relatorioProducao($pdo);
$indicadores = QueryFactory::indicadores($pdo);

// Criar todas de uma vez
$todas = QueryFactory::todas($pdo);
```

---

## 📝 Exemplo Prático

### Relatório Completo de Paradas:
```php
<?php
require_once '../banco/conexao_pdo.php';
require_once '../includes/queries/autoload.php';

// Definir filtro
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => date('Y-m-d'),
    'setor' => 'PO'
];

// Criar instâncias
$paradas = new RelatorioParadas($pdo);
$exportador = new ExportadorCSV();

// Obter dados
$indicadores = $paradas->obterIndicadores($filtro);
$listagem = $paradas->obterListagem($filtro, $pagina = 1, $porPagina = 100);
$pareto = $paradas->obterParetoMotivos($filtro, 10);

// Exportar
$exportador->paradas($listagem['dados'], 'paradas.csv', $indicadores);
?>
```

---

## ✨ Características Técnicas

✅ **Sem SELECT \*** - Apenas campos necessários  
✅ **Prepared Statements** - Proteção contra SQL injection  
✅ **Filtros Dinâmicos** - WHERE construído dinamicamente  
✅ **Paginação** - Suporte a page/limit  
✅ **Tratamento de Erros** - Try/catch com logging  
✅ **Performance** - Queries otimizadas com JOINs eficientes  
✅ **Reutilizável** - PDO injection para portabilidade  

---

## 📊 Estrutura de Dados Retornada

### Indicadores de Paradas:
```php
[
    'quantidade_paradas' => 150,
    'tempo_total_minutos' => 4500,
    'tempo_total_horas' => 75.0,
    'tempo_medio_minutos' => 30.0,
    'tempo_maximo_minutos' => 120,
    'tempo_minimo_minutos' => 5,
    'quantidade_operadores' => 12,
    'quantidade_turnos' => 3,
    'quantidade_motivos_unicos' => 25
]
```

### Pareto de Motivos:
```php
[
    [
        'id' => 1,
        'codigo' => '12',
        'descricao' => 'Reunião/Treinamento',
        'grupo' => 'Adm/Qualidade',
        'subgrupo' => 'GESTÃO',
        'tipo' => 'Qualidade',
        'quantidade' => 45,
        'tempo_total_minutos' => 2700,
        'tempo_total_horas' => 45.0,
        'tempo_medio_minutos' => 60.0
    ],
    // ... mais itens
]
```

### Listagem Paginada:
```php
[
    'dados' => [
        [
            'id' => 1,
            'inicio' => '2026-06-28 08:30:00',
            'fim' => '2026-06-28 09:00:00',
            'duracao_minutos' => 30,
            'duracao_horas' => 0.5,
            'codigo' => '12',
            'descricao' => 'Reunião/Treinamento',
            'grupo' => 'Adm/Qualidade',
            // ... mais campos
        ]
    ],
    'total' => 1250,
    'pagina' => 1,
    'por_pagina' => 50,
    'total_paginas' => 25
]
```

---

## 🚀 Próximos Passos

1. ✅ **Camada de Consultas** (CONCLUÍDO)
2. ⬜ **Exportação XLSX** (PhpSpreadsheet)
3. ⬜ **Dashboards Avançados** (Com Chart.js)
4. ⬜ **Cache de Consultas** (Redis opcional)
5. ⬜ **API REST** (Endpoints adicionais)

---

## 📁 Arquivos Criados

- `/includes/queries/RelatorioParadas.php` - 480 linhas
- `/includes/queries/RelatorioProducao.php` - 380 linhas
- `/includes/queries/IndicadoresDashboard.php` - 420 linhas
- `/includes/queries/ExportadorCSV.php` - 320 linhas
- `/includes/queries/autoload.php` - 40 linhas
- `/relatorios/paradas_avancado.php` - 380 linhas (Exemplo prático)

**Total de novas linhas: ~2000 linhas de código**
