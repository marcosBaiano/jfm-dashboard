# 🎨 LAYOUT VISUAL - DASHBOARD V3 MODERNO

## PREVIEW DO NOVO LAYOUT

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  DASHBOARD CONTAINER (100vw × 100vh)                                             ┃
┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┫
┃                                                                                   ┃
┃  ┌─ SIDEBAR ─────┬─ MAIN CONTENT AREA ──────────────────────────────────────┐   ┃
┃  │  280px (w)    │                                                           │   ┃
┃  │               │  ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓    │   ┃
┃  │ ┌───────────┐ │  ┃  HEADER (70px h)                              ┃    │   ┃
┃  │ │ JFM       │ │  ┃  ☰  📊 Dashboard Industrial                 🟢 ┃    │   ┃
┃  │ │Dashboard  │ │  ┃     Monitoramento em tempo real  Sistema Ativo┃    │   ┃
┃  │ └───────────┘ │  ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛    │   ┃
┃  │               │                                                           │   ┃
┃  │ [Navegação]   │  ┌─ CONTENT SCROLL (flex: 1) ─────────────────────────┐ │   ┃
┃  │ • Dashboard   │  │                                                     │ │   ┃
┃  │ • OEE         │  │  ┌─ FILTROS ────────────────────────────────────┐  │ │   ┃
┃  │ • Paradas     │  │  │ [Período▼] [Setor▼] [Turno▼] [Período Sel]  │  │ │   ┃
┃  │ • Velocidade  │  │  └─────────────────────────────────────────────┘  │ │   ┃
┃  │ • Eficiência  │  │                                                     │ │   ┃
┃  │               │  │  ┌─ LINHA 1: KPIs (Grid 6 colunas) ──────────────┐ │ │   ┃
┃  │ • Usuários    │  │  │ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐┌─────┐│ │ │   ┃
┃  │ • Auditoria   │  │  │ │OEE% │ │Disp │ │Perf │ │Qual │ │Prod ││Linhas││ │ │   ┃
┃  │ • Sair        │  │  │ │ 85% │ │ 90%  │ │ 92% │ │ 87% │ │ 320 ││  5   ││ │ │   ┃
┃  │               │  │  │ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘└─────┘│ │ │   ┃
┃  │ ┌─────────┐   │  │  └──────────────────────────────────────────────────┘ │ │   ┃
┃  │ │ User    │   │  │                                                     │ │   ┃
┃  │ │ Operador│   │  │  ┌─ LINHA 2: KPIs (Grid 6 colunas) ──────────────┐ │ │   ┃
┃  │ └─────────┘   │  │  │ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐┌─────┐│ │ │   ┃
┃  │               │  │  │ │Parad │ │Vel  │ │Efic │ │Perd │ │Taxa ││Reg  ││ │ │   ┃
┃  │               │  │  │ │ 2.5h │ │8.3  │ │ 92% │ │ 45  │ │ 86% ││ 89  ││ │ │   ┃
┃  │               │  │  │ └─────┘ └─────┘ └─────┘ └─────┘ └─────┘└─────┘│ │ │   ┃
┃  │               │  │  └──────────────────────────────────────────────────┘ │ │   ┃
┃  └───────────────┘  │                                                     │ │   ┃
┃                     │  ┌─ LINHA 3: GRÁFICOS (2 col) ───────────────────┐ │ │   ┃
┃                     │  │ ┌──────────────────┐ ┌──────────────────┐    │ │ │   ┃
┃                     │  │ │  Evolução OEE    │ │ Top 5 Piores     │    │ │ │   ┃
┃                     │  │ │  [LINE CHART]    │ │ [HORIZONTAL BAR] │    │ │ │   ┃
┃                     │  │ └──────────────────┘ └──────────────────┘    │ │ │   ┃
┃                     │  └──────────────────────────────────────────────┘ │ │   ┃
┃                     │                                                     │ │   ┃
┃                     │  ┌─ LINHA 4: GRÁFICOS (2 col) ───────────────────┐ │ │   ┃
┃                     │  │ ┌──────────────────┐ ┌──────────────────┐    │ │ │   ┃
┃                     │  │ │ Pareto Paradas   │ │ Top 5 Motivos    │    │ │ │   ┃
┃                     │  │ │  [PIE CHART]     │ │[HORIZONTAL BAR]  │    │ │ │   ┃
┃                     │  │ └──────────────────┘ └──────────────────┘    │ │ │   ┃
┃                     │  └──────────────────────────────────────────────┘ │ │   ┃
┃                     │                                                     │ │   ┃
┃                     │  ┌─ LINHA 5: GRÁFICOS (2 col) ───────────────────┐ │ │   ┃
┃                     │  │ ┌──────────────────┐ ┌──────────────────┐    │ │ │   ┃
┃                     │  │ │ Prod. por Linha  │ │ Prod. por Turno  │    │ │ │   ┃
┃                     │  │ │  [BAR CHART]     │ │  [BAR CHART]     │    │ │ │   ┃
┃                     │  │ └──────────────────┘ └──────────────────┘    │ │ │   ┃
┃                     │  └──────────────────────────────────────────────┘ │ │   ┃
┃                     │                                                     │ │   ┃
┃                     └─────────────────────────────────────────────────────┘ │   ┃
┃                                                                             │   ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛   ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
```

---

## COMPONENTES DETALHADOS

### 1. SIDEBAR

```
┌─────────────────────┐
│  🟠 JFM             │  ← Logo + Brand
│  Dashboard          │
│  Industrial         │
├─────────────────────┤
│ PAINÉIS             │  ← Section header
│ 📊 Dashboard Princ. │  ← Nav item (ativo)
│ ⭐ OEE Análise      │
│ ⏸️  Paradas         │
│ 🏎️  Velocidade      │
│ 📈 Eficiência       │
│                     │
│ SISTEMA             │  ← Section header
│ 👥 Usuários         │
│ 📜 Auditoria        │
│ 🚪 Sair             │
├─────────────────────┤
│ 👤 João Silva       │  ← User footer
│ Supervisor          │
└─────────────────────┘

Dimensões:
├─ Expandido: 280px
├─ Colapsado: 80px
├─ Transição: 0.3s ease
└─ Modo Mobile: overlay (absolute)
```

### 2. KPI CARD

```
┌──────────────────────┐
│                      │  ← Hover: +4px shadow
│      🔵 ICON        │  ← Font Awesome icon
│    (2rem size)       │
│                      │
│   OEE GERAL          │  ← Label (uppercase)
│   (0.8rem, accent)   │
│                      │
│    85.5%             │  ← Value (1.8rem, bold)
│  (color coded)       │
│                      │
│   [Excelente]        │  ← Status badge
│  (inline-block)      │
│                      │
│ Tempo: 13.5 / 16 h   │  ← Detail (0.75rem)
│                      │
└──────────────────────┘

Colors:
├─ OEE:     var(--cor-status-oee)
├─ Disp:    #3b82f6 (Azul)
├─ Perf:    #8b5cf6 (Roxo)
├─ Qual:    #ec4899 (Rosa)
├─ Prod:    #10b981 (Verde)
├─ Linhas:  #f59e0b (Laranja)
└─ Status:  background 20% opacity
```

### 3. CHART CARD

```
┌────────────────────────────────┐
│ 📊 Evolução OEE - 30 Dias      │ ← Header (1.5rem padding)
├────────────────────────────────┤
│                                │
│      ╔════════════════╗        │
│      ║  [CANVAS AQUI] ║        │ ← 300px height
│      ║  (Chart.js)    ║        │
│      ╚════════════════╝        │
│                                │
│                                │
└────────────────────────────────┘

Estilos:
├─ Background: rgba(15,23,42,.85)
├─ Border: 1px solid rgba(255,255,255,.06)
├─ Border-radius: 12px
├─ Padding: 1.5rem
├─ Box-shadow: 0 10px 25px...
├─ Hover: border azul, shadow maior
└─ Transition: 0.3s ease
```

### 4. LAYOUT RESPONSIVO

**Desktop (1200px+)**
```
┌─────────────────────────────────────────┐
│ Sidebar (280px) │ Main (flex: 1)        │
│                 │                       │
│                 │ KPIs: grid 6 colunas  │
│                 │ Charts: grid 2 colunas│
│                 │ Filtros: grid 4 cols  │
└─────────────────────────────────────────┘
```

**Tablet (768px-1199px)**
```
┌─────────────────────────────────────────┐
│ Sidebar (280px) │ Main (flex: 1)        │
│                 │                       │
│                 │ KPIs: grid 3-4 cols   │
│                 │ Charts: grid 1-2 col  │
│                 │ Filtros: grid 2 cols  │
└─────────────────────────────────────────┘
```

**Mobile (< 768px)**
```
┌─────────────────────────────┐
│ Header com hamburger        │
├─────────────────────────────┤
│ Content (full width)        │
│                             │
│ KPIs: grid 2 colunas        │
│ Charts: grid 1 coluna       │
│ Filtros: grid 1 coluna      │
│                             │
│ Sidebar: overlay (-280px)   │
└─────────────────────────────┘
```

---

## FLUXO DE CORES

### Paleta Principal

```
Dark Background:        #0f172a (RGB: 15, 23, 42)
Card Background:        rgba(15, 23, 42, .85)
Text Color:             #e2e8f0
Border:                 rgba(255, 255, 255, .06)

Status Colors:
├─ Excelente:  #10b981 (Verde)
├─ Bom:        #f59e0b (Laranja)
└─ Crítico:    #ef4444 (Vermelho)

KPI Colors:
├─ OEE:        Status-based
├─ Disp:       #3b82f6 (Azul)
├─ Perf:       #8b5cf6 (Roxo)
├─ Qual:       #ec4899 (Rosa)
├─ Prod:       #10b981 (Verde)
├─ Parad:      #ef4444 (Vermelho)
└─ Linhas:     #f59e0b (Laranja)
```

---

## ANIMAÇÕES

```
Transição Card Hover:
├─ Border-color: 0.2s ease
├─ Box-shadow:   0.3s ease
├─ Transform:    translateY(-4px) 0.3s ease

Pulse Animation (Status Dot):
├─ Opacity:     0 → 1 → 0
├─ Duration:    2s infinite
├─ Timing:      ease-in-out

Sidebar Toggle:
├─ Width:       280px ↔ 80px
├─ Duration:    0.3s ease
├─ Text:        fade out/in
```

---

## GRÁFICOS (6 TOTAL)

### 1. Evolução OEE (Line Chart)
```
Dados: Últimos 30 dias, 1 ponto por dia
Eixo Y: 0-100%
Cores: 
├─ Line:  #2563eb
├─ Fill:  rgba(37, 99, 235, 0.1)
└─ Points: #2563eb com border branco
```

### 2. Top 5 Piores Linhas (Horizontal Bar)
```
Dados: TOP 5 linhas com mais tempo parado
Eixo X: Tempo (minutos)
Cores: Gradiente vermelho → verde
```

### 3. Pareto de Paradas (Pie/Doughnut)
```
Dados: Distribuição por tipo de parada (top 5)
Cores: Tons de azul
Legend: Bottom
```

### 4. Top 5 Motivos (Horizontal Bar)
```
Dados: TOP 5 motivos com tempo total
Cores: Vermelho (#ef4444)
Index Axis: Y (horizontal)
```

### 5. Produção por Linha (Bar Chart)
```
Dados: Top 5 linhas por volume
Cores: Verde (#10b981)
```

### 6. Produção por Turno (Bar Chart)
```
Dados: Turno 1, 2, 3 (total dia)
Cores: Laranja (#f59e0b)
```

---

## ESTADO INTERATIVO

### Sidebar Expandido
```
✓ Texto visível
✓ 280px width
✓ Hover items com background
```

### Sidebar Colapsado
```
✓ Ícones visíveis
✓ 80px width
✓ Texto hidden
✓ Tooltip ao hover (futuro)
```

### Filtro Selecionado
```
✓ Form auto-submits
✓ Recarrega dados
✓ Status visual do período
```

### Card Hover
```
✓ Border → azul
✓ Box-shadow amplificado
✓ Elevação (+4px)
✓ Transição suave
```

---

## COMPATIBILIDADE

✅ Chrome 90+  
✅ Firefox 88+  
✅ Safari 14+  
✅ Edge 90+  
✅ Mobile Safari (iOS 12+)  
✅ Chrome Mobile (Android 8+)  

---

**Layout Responsivo e Premium - Dashboard v3.0**
