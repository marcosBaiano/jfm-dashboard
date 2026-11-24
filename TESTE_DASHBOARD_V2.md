# ✅ TESTE RÁPIDO - DASHBOARD V2

## Como Testar Agora

### 1. Abrir Dashboard
```
URL: http://localhost/dashboard/
```

### 2. Verificar Carregamento

**Deve exibir:**
- ✅ Página com 12 KPIs em cards azuis/verdes
- ✅ Filtros no topo (Período, Setor, Turno)
- ✅ 7 gráficos coloridos com dados
- ✅ Sem erro de banco de dados
- ✅ Fundo escuro (dark theme)

### 3. Testar Filtros

```
[ ] Clicar em "Período: Hoje" → Deve recarregar com dados de hoje
[ ] Clicar em "Setor: PO" → Deve filtrar apenas setor PO
[ ] Clicar em "Turno: 1" → Deve filtrar apenas turno 1
[ ] Combinar filtros → Deve aplicar todos
```

### 4. Verificar Gráficos

```
[ ] Gráfico 1: "Evolução OEE 30 Dias" (linha azul) - Deve ter 15-30 pontos
[ ] Gráfico 2: "Top 5 Piores Linhas" (barras) - Deve ter até 5 linhas
[ ] Gráfico 3: "Pareto de Paradas" (pizza) - Deve ter cores diferentes
[ ] Gráfico 4: "Top 5 Motivos" (barras) - Deve ter motivos de parada
[ ] Gráfico 5: "Produção por Linha" (barras verdes) - Deve mostrar produção
[ ] Gráfico 6: "Produção por Turno" (barras laranja) - Deve ter 3 turnos
[ ] Gráfico 7: "Velocidade 30 dias" - (se implementado)
```

### 5. Validar Números

```
KPI Verificações:
[ ] OEE Geral: Deve estar entre 0-100%
[ ] Disponibilidade: Deve estar entre 0-100%
[ ] Performance: Deve estar entre 0-100%
[ ] Qualidade: Deve estar entre 0-100%
[ ] Produção Real: Deve ser > 0
[ ] Linhas Ativas: Deve ser > 0
[ ] Tempo Parado: Deve ser horas (ex: 2.5h)
```

### 6. Auto-Refresh

```
[ ] Deixar página aberta por 5 minutos
[ ] Página deve recarregar automaticamente
[ ] Dados devem atualizar se houver novos registros
```

---

## Problemas Comuns & Soluções

### ❌ "Erro ao Banco"

**Solução:**
1. Verificar se MySQL está rodando
2. Verificar credenciais em `banco/conexao_pdo.php`
3. Ver console do navegador (F12 → Console)

```php
// Teste rápido:
php -r "require 'banco/conexao_pdo.php'; echo 'Conexão OK';"
```

### ❌ Gráficos vazios

**Solução:**
1. Verificar se há dados de produção no banco (últimos 30 dias)
2. Executar:

```sql
SELECT COUNT(*) FROM registros WHERE DATE(data_registro) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
```

Se retorna 0, preencher dados de teste.

### ❌ Filtros não funcionam

**Solução:**
1. Limpar cache do navegador: Ctrl+Shift+Delete
2. Recarregar página: Ctrl+F5
3. Verificar se "Setor" e "Turno" existem no banco

```sql
SELECT DISTINCT setor FROM linhas;
SELECT DISTINCT turno FROM registros;
```

---

## Dados Esperados

Para cada linha do gráfico "Top 5 Piores Linhas", deve haver:
- Nome da linha (ex: "PO-01")
- Tempo de parada em minutos
- Cor vermelha/laranja/amarela

Para "Pareto", deve haver:
- Tipos de parada (ex: "Manutenção", "Troca Produto")
- Quantidade em cada tipo
- Pizza com cores diferentes

---

## Performance Check

```
Métrica                    | Esperado  | Seu Sistema
---------------------------|-----------|----------
Tempo carregamento inicial  | < 2s      | ___s
Tempo filtro               | < 1s      | ___s
Tamanho página             | 150-200KB | ___KB
Requests                   | < 5       | ___
```

Teste com F12 → Network → Reload

---

## Rollback (Se Necessário)

Se descobrir problemas, revertir para versão anterior:

```bash
cd c:\www\ESPUMIL2.0\JFM\dashboard
copy index.php index_problema.php
copy index_backup_v1.php index.php
# Recarregar página
```

---

## ✅ Validação Final

Quando tudo acima estiver OK, marcar:

```
[x] Dashboard carrega sem erro
[x] Todos os 12 KPIs exibem números
[x] Todos os 7 gráficos renderizam
[x] Filtros funcionam
[x] Auto-refresh ativado (5 min)
[x] Responsivo no mobile
[x] Dados conferem com banco
[x] Pronto para produção!
```

---

**Dashboard v2 está ATIVO e pronto para uso!** 🚀
