# 🏭 PLANO DE REESTRUTURAÇÃO - SISTEMA INDUSTRIAL JFM

**Data:** 2026-06-21  
**Versão:** 3.0.0  
**Status:** 📋 Em Planejamento  

---

## 📊 RESUMO EXECUTIVO

Transformar o Sistema Industrial JFM de uma ferramenta básica de dashboard em uma **plataforma profissional de análise industrial** capaz de:

✅ Análise gerencial completa da fábrica  
✅ Identificação rápida dos maiores problemas  
✅ Drill-down desde nível geral até registro individual  
✅ Exportação de dados em múltiplos formatos  
✅ Suporte à tomada de decisão baseada em dados  

---

## 🎯 FASES DE IMPLEMENTAÇÃO

### **FASE 1: Análise e Planejamento (ATUAL)**
- [x] Documentar estrutura existente
- [x] Identificar gaps e melhorias necessárias
- [x] Criar plano de desenvolvimento
- [x] Definir prioridades

**Deliverables:**
- Este documento de planejamento
- Lista de mudanças no BD
- Estrutura de componentes reutilizáveis

**Timeline:** 2026-06-21

---

### **FASE 2: Melhorias no Banco de Dados**
- [ ] Criar tabela `setores`
- [ ] Criar tabela `maquinas`
- [ ] Criar tabela `grupos_parada`
- [ ] Criar tabela `motivos_parada`
- [ ] Adicionar relacionamentos em `registros` e `paradas`
- [ ] Criar índices para performance
- [ ] Documentar novo schema

**Scripts SQL:**
```sql
-- Adicionar colunas em registros
ALTER TABLE registros ADD COLUMN setor_id INT;
ALTER TABLE registros ADD COLUMN maquina_id INT;

-- Criar tabela setores
CREATE TABLE setores (id, nome, descricao, ativa);

-- Criar tabela maquinas
CREATE TABLE maquinas (id, setor_id, linha_id, nome, descricao, ativa);

-- Criar tabela grupos_parada
CREATE TABLE grupos_parada (id, nome, descricao);

-- Criar tabela motivos_parada
CREATE TABLE motivos_parada (id, grupo_id, codigo, motivo, impacto);

-- Adicionar FK em paradas
ALTER TABLE paradas ADD COLUMN grupo_parada_id INT;
ALTER TABLE paradas ADD COLUMN motivo_parada_id INT;
```

**Timeline:** 1-2 dias

---

### **FASE 3: Componentes Reutilizáveis**
- [ ] Criar classe `FiltroGlobal` (classe PHP)
- [ ] Criar componente de filtro (HTML/CSS/JS)
- [ ] Criar classe `QueryBuilder` para queries dinâmicas
- [ ] Criar classe `KPICalculador` para cálculos comuns
- [ ] Criar classe `ExportadorDados` (Excel, CSV, PDF)

**Estrutura de Pastas:**
```
includes/
├── Filtros.php          (classe FiltroGlobal)
├── QueryBuilder.php     (queries dinâmicas)
├── KPIs.php            (cálculos)
├── Exportador.php      (Excel, CSV, PDF)
├── filtro-componente.html (template reutilizável)

assets/
├── js/
│   └── filtro-global.js (lógica do filtro)
├── css/
│   └── filtro-global.css (estilos)
```

**Timeline:** 2-3 dias

---

### **FASE 4: Dashboard Executivo**
- [ ] Criar layout base com filtro global
- [ ] Implementar 13 KPI cards
- [ ] Criar gráfico Pareto
- [ ] Implementar Top 10 Motivos de Parada
- [ ] Implementar Top 10 Máquinas com Mais Paradas
- [ ] Implementar Top 10 Setores com Mais Paradas
- [ ] Implementar Top 10 Códigos de Parada
- [ ] Integrar drill-down para análise detalhada

**Arquivo:** `dashboard/executivo.php`

**KPIs:**
```
1. OEE Geral (%)
2. Disponibilidade (%)
3. Performance (%)
4. Qualidade (%)
5. Produção Total (un)
6. Produção Planejada (un)
7. Eficiência Média (%)
8. Tempo Total Parado (min)
9. Quantidade de Paradas
10. Perdas Totais (un)
11. Linhas Ativas
12. Máquinas Ativas
13. Registros Processados
```

**Timeline:** 4-5 dias

---

### **FASE 5: Dashboard de Paradas Especializado**
- [ ] Refatorar `dashboard/paradas.php` com novo filtro
- [ ] Adicionar ranking de motivos
- [ ] Adicionar ranking de máquinas
- [ ] Adicionar ranking de setores
- [ ] Adicionar ranking de grupos
- [ ] Implementar tabela detalhada com paginação
- [ ] Adicionar drill-down

**Timeline:** 2-3 dias

---

### **FASE 6: Dashboard de Produção**
- [ ] Criar `dashboard/producao.php`
- [ ] Produção por período
- [ ] Produção por setor
- [ ] Produção por linha
- [ ] Produção por máquina
- [ ] Produção por turno
- [ ] Comparativos (Dia/Semana/Mês/Ano)

**Timeline:** 3-4 dias

---

### **FASE 7: Dashboard de OEE Avançado**
- [ ] Refatorar `dashboard/oee.php`
- [ ] OEE por setor
- [ ] OEE por linha
- [ ] OEE por máquina
- [ ] OEE por turno
- [ ] Ranking de melhores/piores
- [ ] Comparativos históricos

**Timeline:** 2-3 dias

---

### **FASE 8: Dashboard de Velocidade Avançado**
- [ ] Refatorar `dashboard/velocidade.php`
- [ ] Velocidade média/máxima/mínima
- [ ] Real x Planejada
- [ ] Ranking de máquinas
- [ ] Análise por turno/setor

**Timeline:** 1-2 dias

---

### **FASE 9: Dashboard de Eficiência Avançado**
- [ ] Refatorar `dashboard/eficiencia.php`
- [ ] Eficiência por máquina
- [ ] Eficiência por linha
- [ ] Eficiência por setor
- [ ] Eficiência por turno
- [ ] Ranking completo
- [ ] Heatmap de desempenho

**Timeline:** 2-3 dias

---

### **FASE 10: Exportação de Dados**
- [ ] Instalar PhpSpreadsheet
- [ ] Criar classe `ExportadorExcel`
- [ ] Criar classe `ExportadorCSV`
- [ ] Criar classe `ExportadorPDF`
- [ ] Adicionar botões de exportação em todos os dashboards
- [ ] Respeitando filtros aplicados

**Timeline:** 2-3 dias

---

### **FASE 11: Testes e Otimização**
- [ ] Testes de performance
- [ ] Otimizar queries lentas
- [ ] Adicionar cache
- [ ] Testar com grande volume de dados
- [ ] Documentar APIs

**Timeline:** 2-3 dias

---

### **FASE 12: Documentação Final**
- [ ] Documentar novas tabelas
- [ ] Documentar classes criadas
- [ ] Documentar componentes reutilizáveis
- [ ] Criar manual do usuário
- [ ] Criar guia de manutenção

**Timeline:** 1-2 dias

---

## 🗄️ MUDANÇAS NO BANCO DE DADOS

### **Novas Tabelas**

#### **1. Tabela `setores`**
```sql
CREATE TABLE setores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    ativa TINYINT DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (nome)
);
```

**Dados Padrão:**
- Envase
- Embalagem
- Corte
- Dobra
- Prensagem
- Confecção
- Acabamento
- Qualidade

---

#### **2. Tabela `maquinas`**
```sql
CREATE TABLE maquinas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setor_id INT NOT NULL,
    linha_id INT,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255),
    ativa TINYINT DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id),
    FOREIGN KEY (linha_id) REFERENCES linhas(id),
    INDEX (setor_id),
    INDEX (linha_id),
    INDEX (nome)
);
```

---

#### **3. Tabela `grupos_parada`**
```sql
CREATE TABLE grupos_parada (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    impacto ENUM('alto', 'medio', 'baixo') DEFAULT 'medio',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (nome)
);
```

**Dados Padrão:**
- Mecânico
- Elétrico
- Hidráulico
- Falta de Material
- Falta de Pessoa
- Programação
- Qualidade
- Manutenção Preventiva
- Limpeza
- Outro

---

#### **4. Tabela `motivos_parada`**
```sql
CREATE TABLE motivos_parada (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grupo_parada_id INT NOT NULL,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    motivo VARCHAR(255) NOT NULL,
    descricao TEXT,
    impacto ENUM('critico', 'alto', 'medio', 'baixo') DEFAULT 'medio',
    ativo TINYINT DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_parada_id) REFERENCES grupos_parada(id),
    INDEX (codigo),
    INDEX (grupo_parada_id)
);
```

---

### **Alterações em Tabelas Existentes**

#### **1. Tabela `registros`**
```sql
ALTER TABLE registros 
ADD COLUMN setor_id INT,
ADD COLUMN maquina_id INT,
ADD FOREIGN KEY (setor_id) REFERENCES setores(id),
ADD FOREIGN KEY (maquina_id) REFERENCES maquinas(id),
ADD INDEX (setor_id),
ADD INDEX (maquina_id);
```

#### **2. Tabela `paradas`**
```sql
ALTER TABLE paradas
ADD COLUMN grupo_parada_id INT,
ADD COLUMN motivo_parada_id INT,
ADD FOREIGN KEY (grupo_parada_id) REFERENCES grupos_parada(id),
ADD FOREIGN KEY (motivo_parada_id) REFERENCES motivos_parada(id),
ADD INDEX (grupo_parada_id),
ADD INDEX (motivo_parada_id);
```

---

### **Índices para Performance**
```sql
-- Índices para queries de filtro
CREATE INDEX idx_registros_data ON registros(data_registro);
CREATE INDEX idx_registros_setor_data ON registros(setor_id, data_registro);
CREATE INDEX idx_registros_maquina_data ON registros(maquina_id, data_registro);
CREATE INDEX idx_registros_turno_data ON registros(turno, data_registro);

CREATE INDEX idx_paradas_data ON paradas(inicio, fim);
CREATE INDEX idx_paradas_registro_data ON paradas(registro_id, created_at);
CREATE INDEX idx_paradas_motivo ON paradas(motivo_parada_id);
CREATE INDEX idx_paradas_grupo ON paradas(grupo_parada_id);

CREATE INDEX idx_producao_data ON producao(created_at);
CREATE INDEX idx_producao_registro_data ON producao(registro_id, created_at);

CREATE INDEX idx_velocidades_data ON velocidades(created_at);
CREATE INDEX idx_velocidades_registro_data ON velocidades(registro_id, created_at);
```

---

## 🛠️ COMPONENTES REUTILIZÁVEIS

### **1. Classe FiltroGlobal (includes/Filtros.php)**

```php
<?php
class FiltroGlobal {
    private $pdo;
    private $filtros = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->inicializarFiltros();
    }
    
    public function inicializarFiltros() {
        $this->filtros = [
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d'),
            'turno' => $_GET['turno'] ?? null,
            'setor_id' => $_GET['setor_id'] ?? null,
            'linha_id' => $_GET['linha_id'] ?? null,
            'maquina_id' => $_GET['maquina_id'] ?? null,
            'grupo_parada_id' => $_GET['grupo_parada_id'] ?? null,
            'motivo_parada_id' => $_GET['motivo_parada_id'] ?? null,
        ];
    }
    
    public function getFiltros() {
        return $this->filtros;
    }
    
    public function getWhereClauses() {
        $clauses = [];
        
        if ($this->filtros['data_inicio'] && $this->filtros['data_fim']) {
            $clauses[] = "DATE(r.data_registro) BETWEEN '{$this->filtros['data_inicio']}' AND '{$this->filtros['data_fim']}'";
        }
        
        if ($this->filtros['turno']) {
            $clauses[] = "r.turno = '{$this->filtros['turno']}'";
        }
        
        if ($this->filtros['setor_id']) {
            $clauses[] = "r.setor_id = {$this->filtros['setor_id']}";
        }
        
        // ... mais filtros
        
        return implode(' AND ', $clauses);
    }
}
?>
```

---

### **2. Classe QueryBuilder (includes/QueryBuilder.php)**

```php
<?php
class QueryBuilder {
    private $pdo;
    private $filtro;
    
    public function __construct($pdo, FiltroGlobal $filtro) {
        $this->pdo = $pdo;
        $this->filtro = $filtro;
    }
    
    public function getOEEGeral() {
        $where = $this->filtro->getWhereClauses();
        return $this->pdo->query("
            SELECT 
                ROUND(AVG(oee), 2) as oee_media
            FROM (
                -- Cálculo de OEE
            ) WHERE {$where}
        ")->fetch();
    }
    
    public function getDisponibilidade() {
        // ... implementação
    }
    
    // ... mais métodos para cada KPI
}
?>
```

---

### **3. Classe ExportadorDados (includes/Exportador.php)**

```php
<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportadorDados {
    private $pdo;
    private $filtro;
    
    public function __construct($pdo, FiltroGlobal $filtro) {
        $this->pdo = $pdo;
        $this->filtro = $filtro;
    }
    
    public function exportarExcel($dados, $nomeArquivo) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Adicionar dados
        // ... implementação
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($nomeArquivo);
    }
    
    public function exportarCSV($dados, $nomeArquivo) {
        // ... implementação
    }
    
    public function exportarPDF($dados, $nomeArquivo) {
        // ... implementação
    }
}
?>
```

---

## 📊 ESTRUTURA DO DASHBOARD EXECUTIVO

### **Layout**
```
┌─────────────────────────────────────────┐
│  LOGO | FILTRO GLOBAL | EXPORTAR        │
├─────────────────────────────────────────┤
│  KPI 1 | KPI 2 | KPI 3 | KPI 4 | KPI 5  │
│  KPI 6 | KPI 7 | KPI 8 | KPI 9 | KPI 10 │
│  KPI 11| KPI 12| KPI 13                 │
├─────────────────────────────────────────┤
│            PARETO DE PARADAS             │
├─────────────────────────────────────────┤
│  TOP MOTIVOS    │  TOP MÁQUINAS         │
│                 │                       │
├─────────────────────────────────────────┤
│  TOP SETORES    │  TOP CÓDIGOS          │
│                 │                       │
└─────────────────────────────────────────┘
```

---

## ⚙️ FLUXO DE DRILL-DOWN

```
Dashboard Executivo
    ↓
  Top Paradas (Motivos)
    ↓
Máquinas Afetadas por Motivo
    ↓
Setores Afetados
    ↓
Linhas Afetadas
    ↓
Registros Detalhados
    ↓
Registro Individual
```

---

## 📈 ESTIMATIVA DE TEMPO

| Fase | Descrição | Dias | Status |
|------|-----------|------|--------|
| 1 | Análise e Planejamento | 0.5 | ✅ |
| 2 | Melhorias no BD | 2 | ⏳ |
| 3 | Componentes Reutilizáveis | 3 | ⏳ |
| 4 | Dashboard Executivo | 5 | ⏳ |
| 5 | Dashboard Paradas | 3 | ⏳ |
| 6 | Dashboard Produção | 4 | ⏳ |
| 7 | Dashboard OEE | 3 | ⏳ |
| 8 | Dashboard Velocidade | 2 | ⏳ |
| 9 | Dashboard Eficiência | 3 | ⏳ |
| 10 | Exportação | 3 | ⏳ |
| 11 | Testes/Otimização | 3 | ⏳ |
| 12 | Documentação | 2 | ⏳ |
| **TOTAL** | | **34 dias** | |

---

## 🎯 PRÓXIMOS PASSOS

1. ✅ **Análise Concluída** - Este documento
2. ⏳ **Fase 2** - Scripts SQL para melhorias no BD
3. ⏳ **Fase 3** - Criar classes reutilizáveis
4. ⏳ **Fase 4** - Dashboard Executivo
5. ...

---

## 📝 NOTAS IMPORTANTES

- **Dados Manuais:** Sistema não recebe dados de CLP/SCADA
- **Foco:** Análise histórica e tomada de decisão
- **Visual:** Manter dark theme atual + Bootstrap 5
- **Performance:** Implementar índices e paginação
- **UX:** Mensagens amigáveis quando sem dados
- **Exportação:** Excel, CSV, PDF com filtros aplicados

---

**Próxima Reunião:** Após aprovação deste plano

**Responsável:** Equipe de Desenvolvimento

**Data:** 2026-06-21
