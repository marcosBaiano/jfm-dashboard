# 🚀 QUICK START - Camada de Queries

## ⚡ 5 Minutos de Setup

### 1️⃣ Copiar e Colar (Relatório Simples)

```php
<?php
// seu_relatorio.php

require_once '../banco/conexao_pdo.php';
require_once '../includes/queries/autoload.php';

// Definir período
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => date('Y-m-d')
];

// Obter indicadores
$paradas = new RelatorioParadas($pdo);
$ind = $paradas->obterIndicadores($filtro);

// Exibir
echo "Quantidade de Paradas: " . $ind['quantidade_paradas'] . "\n";
echo "Tempo Total: " . $ind['tempo_total_horas'] . " horas\n";
echo "Tempo Médio: " . $ind['tempo_medio_minutos'] . " minutos\n";
?>
```

**Saída:**
```
Quantidade de Paradas: 150
Tempo Total: 75.5 horas
Tempo Médio: 30.2 minutos
```

---

## 🎯 10 Exemplos Práticos

### Exemplo 1: Paradas por Turno
```php
$paradas = new RelatorioParadas($pdo);

$porTurno = $paradas->obterPorTurno([
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28'
]);

foreach ($porTurno as $turno) {
    echo "Turno {$turno['turno']}: {$turno['quantidade']} paradas\n";
}
```

### Exemplo 2: Top 5 Motivos
```php
$paretoMotivos = $paradas->obterParetoMotivos($filtro, 5);

foreach ($paretoMotivos as $motivo) {
    echo "{$motivo['descricao']}: {$motivo['tempo_total_horas']}h\n";
}
```

### Exemplo 3: Produção por Marca
```php
$producao = new RelatorioProducao($pdo);

$porMarca = $producao->obterPorMarca([
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28'
]);

foreach ($porMarca as $marca) {
    echo "{$marca['marca']}: {$marca['total_producao']} unidades\n";
}
```

### Exemplo 4: OEE
```php
$ind = new IndicadoresDashboard($pdo);

$oee = $ind->obterOEE([
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'setor' => 'PO'
]);

echo "OEE: " . $oee['oee'] . "%\n";
echo "Disponibilidade: " . $oee['disponibilidade'] . "%\n";
```

### Exemplo 5: Paradas com Paginação
```php
$resultado = $paradas->obterListagem($filtro, $pagina = 1, $porPagina = 20);

foreach ($resultado['dados'] as $parada) {
    echo $parada['descricao'] . " - " . $parada['duracao_horas'] . "h\n";
}

echo "Página " . $resultado['pagina'] . " de " . $resultado['total_paginas'];
```

### Exemplo 6: Exportar CSV
```php
$exportador = new ExportadorCSV();
$listagem = $paradas->obterListagem($filtro);
$indicadores = $paradas->obterIndicadores($filtro);

$exportador->paradas($listagem['dados'], 'paradas.csv', $indicadores);
// Gera download automático!
```

### Exemplo 7: Comparar Dois Períodos
```php
$periodo1 = ['data_inicio' => '2026-06-01', 'data_fim' => '2026-06-14'];
$periodo2 = ['data_inicio' => '2026-06-15', 'data_fim' => '2026-06-28'];

$comparativo = $paradas->obterComparativos($periodo1, $periodo2);

echo "Variação de Paradas: " . $comparativo['variacao_quantidade'];
echo "Variação de Tempo: " . $comparativo['variacao_tempo_horas'] . "h";
```

### Exemplo 8: Eficiência
```php
$eficiencia = $ind->obterEficiencia($filtro);
echo "Eficiência: " . $eficiencia . "%";
```

### Exemplo 9: Paradas por Hora
```php
$paradasPorHora = $ind->obterParadasPorHora($filtro);
echo "Paradas por hora: " . $paradasPorHora;
```

### Exemplo 10: Resumo Completo
```php
$resumo = $ind->obterResumo($filtro);

echo "Disponibilidade: " . $resumo['disponibilidade'] . "%\n";
echo "Eficiência: " . $resumo['eficiencia'] . "%\n";
echo "Velocidade: " . $resumo['velocidade_media'] . "\n";
echo "OEE: " . $resumo['oee']['oee'] . "%\n";
echo "Paradas/hora: " . $resumo['paradas_por_hora'] . "\n";
```

---

## 🔗 Estrutura de Filtros

```php
// Todos opcionais - combinar conforme necessário
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'linha_id' => 5,          // ID da linha (1-21)
    'setor' => 'PO',          // PO, BAR, ou LIQ
    'turno' => '1',           // 1, 2 ou 3
    'operador' => 'João',
    'motivo' => '12',         // Código da descrição
    'grupo' => 'Limpeza',
    'tipo' => 'Planejada'
];
```

---

## 📊 Retorno de Dados

### Indicadores Simples
```php
$ind = $paradas->obterIndicadores($filtro);

// Retorna array com:
// - quantidade_paradas
// - tempo_total_minutos
// - tempo_total_horas
// - tempo_medio_minutos
// - tempo_maximo_minutos
// - tempo_minimo_minutos
```

### Listagem com Paginação
```php
$resultado = $paradas->obterListagem($filtro, $pagina, $porPagina);

// Retorna array com:
// - dados: array de registros
// - total: total de registros
// - pagina: página atual
// - por_pagina: registros por página
// - total_paginas: total de páginas
```

### Agregações
```php
$porTurno = $paradas->obterPorTurno($filtro);

// Retorna array com:
// - turno
// - quantidade
// - tempo_total_minutos
// - tempo_total_horas
```

---

## 🎨 No HTML

### Indicadores em Bootstrap
```html
<?php
$ind = new IndicadoresDashboard($pdo);
$oee = $ind->obterOEE($filtro);
?>

<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary">
            <div class="card-body">
                <h4><?php echo $oee['oee']; ?>%</h4>
                <p>OEE</p>
            </div>
        </div>
    </div>
</div>
```

### Tabela de Paradas
```html
<?php
$paradas = new RelatorioParadas($pdo);
$dados = $paradas->obterListagem($filtro);
?>

<table class="table">
    <thead>
        <tr>
            <th>Data</th>
            <th>Motivo</th>
            <th>Tempo (h)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($dados['dados'] as $item): ?>
            <tr>
                <td><?php echo $item['data_registro']; ?></td>
                <td><?php echo $item['descricao']; ?></td>
                <td><?php echo $item['duracao_horas']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

---

## 🎯 Casos de Uso Comuns

### Relatório Diário
```php
$filtro = [
    'data_inicio' => date('Y-m-d'),
    'data_fim' => date('Y-m-d')
];
```

### Relatório Semanal
```php
$filtro = [
    'data_inicio' => date('Y-m-d', strtotime('-7 days')),
    'data_fim' => date('Y-m-d')
];
```

### Relatório Mensal
```php
$filtro = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d')
];
```

### Relatório por Setor
```php
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'setor' => 'PO'
];
```

### Relatório por Linha
```php
$filtro = [
    'data_inicio' => '2026-06-01',
    'data_fim' => '2026-06-28',
    'linha_id' => 5
];
```

### Relatório por Período Customizado
```php
$filtro = $_GET; // GET direto do formulário
```

---

## ⚡ Dicas de Performance

### ✅ Com Paginação (Recomendado)
```php
$resultado = $paradas->obterListagem($filtro, $pagina = 1, $porPagina = 50);
// Rápido, mesmo com 100k registros
```

### ❌ Sem Paginação (Não Recomendado)
```php
$resultado = $paradas->obterListagem($filtro, $pagina = 1, $porPagina = 999999);
// Pode ficar lento com muitos registros
```

---

## 🐛 Troubleshooting

### "Class not found"
```php
// Esqueceu o require?
require_once '../includes/queries/autoload.php';
```

### "No rows returned"
```php
// Filtro muito restritivo?
// Verificar datas, setor, turno, etc

$filtro = []; // Sem filtro = todos os registros
```

### "Memory exhausted"
```php
// Usar paginação!
$resultado = $paradas->obterListagem($filtro, 1, 50);
// Não pegar tudo de uma vez
```

---

## 📚 Documentação Completa

Para mais detalhes:
- [DOCUMENTACAO_QUERIES.md](../DOCUMENTACAO_QUERIES.md)
- [README.md em queries/](README.md)
- [Exemplo completo: paradas_avancado.php](../relatorios/paradas_avancado.php)

---

## 🚀 Já Está Pronto!

Copie, cole e adapte. Tudo está documentado e testado.

Dúvidas? Veja os exemplos em:
```
/relatorios/paradas_avancado.php
```

Boa sorte! 🎉
