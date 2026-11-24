# 📘 Manual de Uso - Sistema Industrial JFM

## 🚀 Guia Rápido

### Login
1. Acesse: `http://localhost/JFM/processos/login.php`
2. Use: `admin@jfm.com.br` / `admin123`
3. Clique em "Entrar no Sistema"

---

## 📋 Funcionalidades Principais

### 1. Dashboard Principal
**Local:** `/JFM/dashboard/`

Exibe KPIs importantes:
- **Produção Hoje:** Total produzido na data
- **Produção Mês:** Total produzido no mês
- **Eficiência:** Percentual médio
- **Tempo Parado:** Horas de parada

Inclui gráficos:
- 📊 Paradas por Tipo
- 📈 Linhas com Mais Paradas

---

### 2. Registrar Parada
**Local:** `/JFM/index.php`

Funcionalidade original mantida. Permite:
- Selecionar setor (PO, LIQ, BARRA)
- Selecionar linha
- Escolher turno (1, 2, 3)
- Inserir operador
- Registrar múltiplas paradas e produção

---

### 3. Visualizar Paradas
**Local:** `/JFM/dashboard/paradas.php`

Recursos:
- Filtrar por data, linha
- Visualizar todos os registros
- Editar paradas (se permissão)
- Histórico de alterações

**Permissões:**
- Supervisor: Editar todas
- Líder: Editar próprias paradas
- Operador: Apenas visualizar

---

### 4. Controle de Velocidade
**Local:** `/JFM/dashboard/velocidade.php`

Exibe:
- Velocidade planejada
- Velocidade real
- Eficiência calculada
- KPI de eficiência do dia

Funcionalidades:
- ✏️ Editar velocidades
- 📊 Visualizar tendências
- 🔄 Histórico de alterações

---

### 5. Análise de Eficiência
**Local:** `/JFM/dashboard/eficiencia.php`

Mostra:
- Tendência dos últimos 30 dias
- Eficiência por linha
- Status (Excelente, Bom, Crítico)

Fórmula:
```
Eficiência = (Velocidade Real / Velocidade Planejada) × 100
```

---

### 6. OEE - Overall Equipment Effectiveness
**Local:** `/JFM/dashboard/oee.php`

Métrica mais importante da produção:

**Componentes:**
1. **Disponibilidade:**
   - Tempo Produzindo / (Tempo Produzindo + Tempo Parado)
   
2. **Performance:**
   - Produção Real / Produção Planejada
   
3. **Qualidade:**
   - Produção Boa / Produção Total

**OEE Final = Disponibilidade × Performance × Qualidade**

**Metas:**
- ✅ OEE ≥ 85%: Excelente
- ⚠️ OEE 70-85%: Bom
- ❌ OEE < 70%: Crítico

---

### 7. Relatórios
**Local:** `/JFM/relatorios/`

#### Relatório de Produção
- Período customizável
- Dados: setor, linha, operador, produção, perdas, qualidade
- Exportar em CSV

#### Relatório de Paradas
- Período customizável
- Dados: linha, operador, tipo, descrição, tempo
- Gráficos inclusos
- Exportar em CSV

---

### 8. Administração

#### Gerenciar Usuários
**Local:** `/JFM/admin/usuarios.php`

Funcionalidades:
- ➕ Criar novo usuário
- ✏️ Editar usuário
- 🔑 Alterar senha
- ✔️ Ativar/desativar

Campos:
- Nome
- Email (único)
- Perfil (5 opções)
- Status (ativo/inativo)

#### Auditoria
**Local:** `/JFM/admin/auditoria.php`

Log completo de:
- **Logins:** Quem entrou quando
- **Alterações:** Dados modificados
- **Exclusões:** Registros deletados
- **IP do usuário:** Para segurança

Filtros:
- Por data
- Por tipo de ação
- Por usuário

---

## 👥 Perfis e Permissões

| Perfil | Registro | Editar | Visualizar | Admin |
|--------|----------|--------|-----------|-------|
| Administrador | ✅ | ✅ | ✅ | ✅ |
| Supervisor | ✅ | ✅ | ✅ | ❌ |
| Líder | ✅ | ✅* | ✅ | ❌ |
| Operador | ✅ | ❌ | ✅ | ❌ |
| Visualizador | ❌ | ❌ | ✅ | ❌ |

*Apenas próprios registros

---

## 🔐 Segurança

### Proteção de Dados
- ✅ Senhas criptografadas com bcrypt
- ✅ Prepared Statements (PDO)
- ✅ Validação de entrada
- ✅ Proteção CSRF
- ✅ Log completo de auditoria

### Boas Práticas
1. **Altere a senha do admin na primeira vez**
2. **Use senhas fortes (mín. 6 caracteres)**
3. **Monitore a auditoria regularmente**
4. **Faça backup do banco de dados**
5. **Mantenha PHP e MySQL atualizados**

---

## ⚙️ Configurações

### Alterar Tempo de Sessão
Arquivo: `includes/auth.php`

```php
$inatividade = 30 * 60; // 30 minutos
```

### Alterar Dados do Banco
Arquivo: `banco/conexao_pdo.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'paradas');
```

---

## 🐛 Troubleshooting

### "Sessão expirada"
- Sessão dura 30 minutos
- Faça login novamente
- Altere `$inatividade` em `auth.php` se necessário

### "Acesso negado"
- Verifique seu perfil de usuário
- Contacte um administrador
- Verifique permissões em `temPermissao()`

### "Erro ao salvar"
- Verifique se os dados estão completos
- Confirme permissões no banco
- Verifique o log de erros do PHP

### "Gráficos não aparecem"
- Verifique conexão com CDN de Chart.js
- Atualize o navegador (Ctrl+F5)
- Verifique console do navegador (F12)

---

## 📊 Métricas Importantes

### OEE
- **Objetivo:** > 85%
- **Bom:** 70-85%
- **Crítico:** < 70%

### Eficiência
- **Excelente:** ≥ 90%
- **Bom:** 75-90%
- **Crítico:** < 75%

### Taxa de Qualidade
- **Excelente:** ≥ 95%
- **Bom:** 90-95%
- **Crítico:** < 90%

---

## 📞 Suporte

### Problemas Comuns

**Não consigo logar**
- Verifique email e senha
- Confirme se usuário está ativo
- Resete a senha de um admin

**Dados não aparecem**
- Verifique se foram registrados
- Atualize a página
- Limpe cache do navegador

**Relatório vazio**
- Verifique período selecionado
- Confirme se há dados registrados
- Mude os filtros

---

## 🎓 Dicas Úteis

1. **Use Chrome/Firefox** para melhor compatibilidade
2. **Acompanhe a auditoria** regularmente
3. **Exporte relatórios** no final do mês
4. **Altere senhas** a cada 3 meses
5. **Faça backup** do banco semanalmente

---

**Versão:** 2.0.0  
**Última Atualização:** Junho 2026  
**Desenvolvido por:** Equipe TI
