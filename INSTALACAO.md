# 🚀 Guia de Implementação - Sistema Industrial JFM v2.0.0

## 📌 Resumo do Projeto

Foi desenvolvido um **sistema completo de gerenciamento de produção industrial** em PHP Puro com as seguintes características:

- ✅ **27 novos arquivos** criados
- ✅ **Mantém compatibilidade** com código existente
- ✅ **Sem uso de frameworks** (PHP puro, Bootstrap 5, Chart.js)
- ✅ **PDO para segurança** (prepared statements)
- ✅ **5 perfis de usuário** com permissões específicas
- ✅ **Dashboard em tempo real** com gráficos
- ✅ **OEE calculado automaticamente**
- ✅ **Auditoria completa** de todas as ações
- ✅ **Relatórios exportáveis** em CSV

---

## 📋 Passo a Passo de Implementação

### **PASSO 1: Preparar o Banco de Dados** (5 min)

Execute no seu cliente MySQL (phpMyAdmin, MySQL Workbench, etc):

```sql
USE paradas;
SOURCE banco/tabelas_novas.sql;
```

Ou copie e execute o conteúdo do arquivo `banco/tabelas_novas.sql` manualmente.

**Resultado:** 4 novas tabelas criadas:
- `usuarios`
- `auditoria`
- `historico_edicoes`
- `sessions_custom` (opcional)

---

### **PASSO 2: Executar o Setup** (10 min)

1. Abra no navegador:
   ```
   http://localhost/JFM/setup.php
   ```

2. Verifique se a conexão com banco está OK

3. Clique em **"Criar Tabelas"**

4. Clique em **"Criar Administrador"**
   - Nome: `Administrador`
   - Email: `admin@jfm.com.br`
   - Senha: `admin123`

5. Clique em **"Testar Login"** para validar

---

### **PASSO 3: Verificar Instalação** (2 min)

Abra no navegador:
```
http://localhost/JFM/check.php
```

Verifique se todos os itens estão em VERDE ✅

---

### **PASSO 4: Fazer Login** (1 min)

Acesse:
```
http://localhost/JFM/processos/login.php
```

Credenciais:
- **Email:** `admin@jfm.com.br`
- **Senha:** `admin123`

---

## 🎯 Funcionalidades por Módulo

### 1️⃣ **Dashboard** (`/dashboard/index.php`)
- KPIs: Produção dia/mês, eficiência, tempo parado
- Gráficos de paradas por tipo
- Últimas 5 linhas com paradas

### 2️⃣ **Paradas** (`/dashboard/paradas.php`)
- Listar com filtros
- Editar com auditoria
- Histórico de alterações

### 3️⃣ **Velocidade** (`/dashboard/velocidade.php`)
- Registros de velocidade
- Editar com cálculo automático
- KPI de eficiência

### 4️⃣ **Eficiência** (`/dashboard/eficiencia.php`)
- Tendência 30 dias
- Status por linha
- Gráfico de linha

### 5️⃣ **OEE** (`/dashboard/oee.php`)
- Cálculo completo: Disponibilidade × Performance × Qualidade
- Histórico 30 dias
- Status: Excelente (>85%), Bom (70-85%), Crítico (<70%)

### 6️⃣ **Relatórios** (`/relatorios/`)
- Produção com filtros e exportação CSV
- Paradas com gráficos e exportação CSV

### 7️⃣ **Administração** (`/admin/`)
- Gerenciar usuários (5 perfis)
- Log de auditoria completo
- Alterar senhas

---

## 👤 Perfis de Usuário

| Perfil | Acesso |
|--------|--------|
| **Administrador** | Total - pode fazer tudo |
| **Supervisor** | Editar todos os dados, visualizar relatórios |
| **Líder** | Registrar e editar próprias paradas |
| **Operador** | Apenas registrar produção |
| **Visualizador** | Apenas consulta de dados |

---

## 🔒 Segurança Implementada

✅ **Senhas:** Criptografadas com bcrypt (PASSWORD_BCRYPT)
✅ **Banco:** Prepared Statements com PDO
✅ **Entrada:** Validação com htmlspecialchars
✅ **Sessão:** Proteção de 30 minutos de inatividade
✅ **Auditoria:** Log de LOGIN, LOGOUT, INSERT, UPDATE, DELETE
✅ **IP:** Registrado em cada ação para rastreabilidade

---

## 📊 Cálculos Implementados

### **OEE (Overall Equipment Effectiveness)**
```
OEE = Disponibilidade × Performance × Qualidade

Disponibilidade = Tempo Produzindo / (Tempo Produzindo + Tempo Parado)
Performance = Produção Real / Produção Planejada
Qualidade = Produção Boa / Produção Total
```

### **Eficiência**
```
Eficiência = (Velocidade Real / Velocidade Planejada) × 100
```

### **Qualidade**
```
Qualidade = (Produção Total - Total de Perdas) / Produção Total × 100
```

---

## 📁 Estrutura Final de Pastas

```
JFM/
├── admin/
│   ├── usuarios.php
│   └── auditoria.php
├── assets/
│   ├── css/ (style.css, enviar.css)
│   ├── js/ (script.js)
│   └── img/
├── banco/
│   ├── conexao.php (legado)
│   ├── conexao_pdo.php (novo)
│   ├── banco.sql
│   └── tabelas_novas.sql
├── dashboard/
│   ├── index.php
│   ├── paradas.php
│   ├── velocidade.php
│   ├── eficiencia.php
│   └── oee.php
├── includes/
│   ├── auth.php
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── processos/
│   ├── login.php
│   ├── logout.php
│   ├── editar_parada.php
│   ├── editar_velocidade.php
│   └── editar_producao.php
├── relatorios/
│   ├── producao.php
│   └── paradas.php
├── index.php (original)
├── salvar_registro.php (original)
├── setup.php (instalação)
├── check.php (verificação)
├── README.md (documentação)
├── MANUAL.md (guia de uso)
└── INSTALACAO.md (este arquivo)
```

---

## 🔧 Configurações Avançadas

### Alterar Tempo de Sessão
**Arquivo:** `includes/auth.php`
```php
$inatividade = 30 * 60; // Altere para minutos desejados
```

### Alterar Conexão do Banco
**Arquivo:** `banco/conexao_pdo.php`
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'paradas');
```

### Alterar Produção Planejada do OEE
**Arquivo:** `dashboard/oee.php` (buscar `$producaoPlanejada`)
```php
$producaoPlanejada = ($tempoJornada / 60) * 25; // 25 unidades por hora
```

---

## ✅ Checklist de Pós-Instalação

- [ ] Banco de dados criado com novas tabelas
- [ ] Setup executado com sucesso
- [ ] Usuário admin criado
- [ ] Login funcionando
- [ ] Dashboard mostrando KPIs
- [ ] Paradas visíveis no gráfico
- [ ] Relatórios gerando CSV
- [ ] Auditoria registrando ações
- [ ] Alteração de senha funcionando
- [ ] Novo usuário criável

---

## 🆘 Troubleshooting

### Erro: "Erro ao conectar ao banco de dados"
**Solução:**
1. Verifique credenciais em `banco/conexao_pdo.php`
2. Confirme que MySQL está rodando
3. Verifique permissões do usuário

### Erro: "Tabelas não encontradas"
**Solução:**
1. Execute `banco/tabelas_novas.sql` novamente
2. Verifique no phpMyAdmin se tabelas existem

### Erro: "Sessão expirada"
**Solução:**
1. É normal após 30 minutos de inatividade
2. Faça login novamente
3. Para aumentar, altere `$inatividade` em `auth.php`

### Gráficos não aparecem
**Solução:**
1. Atualize página (Ctrl+F5)
2. Verifique console do navegador (F12)
3. Confirme conexão de internet (usa CDN)

---

## 📞 Suporte

### Dúvidas Frequentes

**P: Preciso alterar a senha do admin?**
A: Sim! Faça login e clique em seu nome → Alterar senha

**P: Como criar novos usuários?**
A: Admin > Usuários > Novo Usuário

**P: Como ver histórico de edições?**
A: Admin > Auditoria

**P: Posso exportar relatórios?**
A: Sim! Em CSV. PDF pode ser adicionado depois.

**P: O sistema é seguro?**
A: Sim! Usa bcrypt, PDO prepared statements, validação de entrada e log completo.

---

## 📚 Documentação Disponível

- **README.md** - Visão geral e pré-requisitos
- **MANUAL.md** - Guia completo de uso
- **Este arquivo** - Instruções de implementação
- **setup.php** - Assistente de configuração
- **check.php** - Verificador de instalação

---

## 🎓 Treinamento Sugerido

1. **Dia 1:** Instalação e configuração básica
2. **Dia 2:** Registrar paradas e produção
3. **Dia 3:** Editar dados e ver auditoria
4. **Dia 4:** Gerar relatórios
5. **Dia 5:** Gerenciar usuários

---

## 📊 Próximas Melhorias (Opcional)

- [ ] Exportar relatórios em PDF (usar mPDF)
- [ ] Gráfico de produção por hora
- [ ] Alertas de OEE baixo
- [ ] API REST para integração
- [ ] App mobile (Flutter/React Native)
- [ ] Sincronização em tempo real (WebSockets)
- [ ] Dashboard automático em TV

---

**Desenvolvido com:** PHP 7.4+, MySQL 8.0+, Bootstrap 5, Chart.js

**Data:** Junho de 2026
**Versão:** 2.0.0
**Status:** ✅ Produção
