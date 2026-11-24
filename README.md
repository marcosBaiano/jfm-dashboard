# 🏭 Sistema Industrial JFM - Gerenciamento de Produção

**Versão:** 2.0.0  
**Desenvolvedor:** Equipe TI  
**Última Atualização:** 2026-06-17

## 📋 Índice

1. [Introdução](#introdução)
2. [Pré-requisitos](#pré-requisitos)
3. [Instalação](#instalação)
4. [Configuração](#configuração)
5. [Uso do Sistema](#uso-do-sistema)
6. [Funcionalidades](#funcionalidades)
7. [Estrutura de Pastas](#estrutura-de-pastas)
8. [Suporte](#suporte)

---

## 🎯 Introdução

Sistema completo de gerenciamento de produção industrial desenvolvido em **PHP Puro** e **MySQL**, com interface responsiva em **Bootstrap 5** e gráficos em **Chart.js**.

O sistema permite:
- ✅ Registro de paradas e produção
- ✅ Monitoramento de velocidade e eficiência
- ✅ Cálculo de OEE (Overall Equipment Effectiveness)
- ✅ Geração de relatórios (CSV, PDF)
- ✅ Controle de acesso com 5 perfis de usuário
- ✅ Auditoria completa de ações
- ✅ Dashboard em tempo real

---

## 📦 Pré-requisitos

- **PHP:** 7.4 ou superior
- **MySQL:** 5.7 ou superior (recomendado 8.0+)
- **Servidor Web:** Apache ou Nginx com suporte a mod_rewrite
- **Extensões PHP:** PDO, PDO_MySQL, JSON

---

## 🔧 Instalação

### 1. Preparar o Banco de Dados

```sql
-- Criar banco de dados
CREATE DATABASE paradas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Usar banco
USE paradas;

-- Importar script de tabelas existentes
SOURCE banco/banco.sql;

-- Importar novas tabelas
SOURCE banco/tabelas_novas.sql;
```

### 2. Gerar Hash de Senha do Admin

Execute no PHP:
```php
<?php
$senha = 'admin123';
$hash = password_hash($senha, PASSWORD_BCRYPT);
echo $hash;
?>
```

Depois atualize a tabela usuarios:
```sql
UPDATE usuarios 
SET senha = '$2y$10$SEU_HASH_AQUI' 
WHERE email = 'admin@jfm.com.br';
```

### 3. Configurar Permissões

```bash
# Linux/Mac
chmod -R 755 /caminho/para/JFM
chmod -R 777 /caminho/para/JFM/assets

# Windows (via GUI)
# Clique direito > Propriedades > Segurança > Editar
```

### 4. Testar Conexão

Acesse a aplicação no navegador:
```
http://localhost/JFM/processos/login.php
```

---

## ⚙️ Configuração

### Arquivo: `banco/conexao_pdo.php`

Ajuste as constantes se necessário:

```php
define('DB_HOST', 'localhost');   // Host do banco
define('DB_USER', 'root');        // Usuário do banco
define('DB_PASS', '');            // Senha do banco
define('DB_NAME', 'paradas');     // Nome do banco
```

### Arquivo: `includes/auth.php`

Tempo de sessão (em segundos):
```php
$inatividade = 30 * 60; // 30 minutos
```

---

## 🚀 Uso do Sistema

### Login

1. Acesse `http://localhost/JFM/processos/login.php`
2. Insira as credenciais:
   - **Email:** `admin@jfm.com.br`
   - **Senha:** `admin123` (alterar após primeiro acesso)

### Dashboard

O dashboard exibe:
- Produção do dia e mês
- Tempo produzindo vs parado
- Eficiência média
- Gráficos de paradas por tipo
- Linhas com mais paradas

### Registrar Parada

1. Clique em "Registrar Parada" no menu
2. Selecione setor, linha e turno
3. Preencha os dados da parada
4. Clique em "Salvar"

### Editar Dados

1. Acesse o módulo (Paradas, Velocidade, etc)
2. Clique em "Editar" ao lado do registro
3. Altere os dados
4. Clique em "Salvar Alterações"

**Todas as alterações são registradas em auditoria!**

### Gerar Relatórios

1. Acesse "Relatórios" > "Produção" ou "Paradas"
2. Selecione período
3. Clique em "Filtrar"
4. Exporte em CSV se desejar

---

## 🎯 Funcionalidades

### 1️⃣ Autenticação e Controle de Acesso

**Perfis de Usuário:**
- **Administrador:** Acesso total
- **Supervisor:** Editar e visualizar tudo
- **Líder:** Registrar e editar próprias paradas
- **Operador:** Apenas registrar produção
- **Visualizador:** Apenas consulta

### 2️⃣ Dashboard

- KPIs em tempo real
- Gráficos de produção e paradas
- Alertas de anomalias
- Tendências

### 3️⃣ Gerenciamento de Paradas

- Registro de paradas com múltiplos tipos
- Edição de dados históricos
- Cálculo automático de tempo
- Observações e justificativas

### 4️⃣ Controle de Velocidade

- Registro de velocidades planejada vs real
- Cálculo automático de eficiência
- Histórico de alterações

### 5️⃣ OEE (Overall Equipment Effectiveness)

Cálculo de OEE baseado em:
- **Disponibilidade:** Tempo produzindo / Total
- **Performance:** Produção real / Planejada
- **Qualidade:** Produção boa / Total

OEE = Disponibilidade × Performance × Qualidade

### 6️⃣ Relatórios

- Produção por período
- Paradas por tipo e máquina
- Eficiência por linha
- Exportação em CSV

### 7️⃣ Auditoria

Log completo de:
- Login/Logout
- Inserção de dados
- Alterações
- Exclusões
- IP do usuário

---

## 📁 Estrutura de Pastas

```
JFM/
├── admin/
│   ├── usuarios.php              # Gerenciar usuários
│   └── auditoria.php             # Log de auditoria
│
├── assets/
│   ├── css/
│   │   ├── style.css             # CSS customizado
│   │   └── enviar.css
│   ├── js/
│   │   └── script.js             # JavaScript customizado
│   └── img/                      # Imagens do sistema
│
├── banco/
│   ├── conexao.php               # Conexão mysqli (legado)
│   ├── conexao_pdo.php           # Conexão PDO (novo)
│   ├── banco.sql                 # Script do banco original
│   └── tabelas_novas.sql         # Novas tabelas do sistema
│
├── dashboard/
│   ├── index.php                 # Dashboard principal
│   ├── paradas.php               # Visualizar paradas
│   ├── velocidade.php            # Controle de velocidade
│   ├── eficiencia.php            # Análise de eficiência
│   └── oee.php                   # Dashboard OEE
│
├── includes/
│   ├── auth.php                  # Autenticação e sessão
│   ├── header.php                # Cabeçalho HTML padrão
│   ├── footer.php                # Rodapé HTML padrão
│   └── functions.php             # Funções auxiliares
│
├── processos/
│   ├── login.php                 # Página de login
│   ├── logout.php                # Encerrar sessão
│   ├── editar_parada.php         # Editar parada
│   ├── editar_velocidade.php     # Editar velocidade
│   └── editar_producao.php       # Editar produção
│
├── relatorios/
│   ├── producao.php              # Relatório de produção
│   └── paradas.php               # Relatório de paradas
│
├── index.php                     # Registro inicial (legado)
├── salvar_registro.php           # Processar registro (legado)
└── README.md                     # Este arquivo
```

---

## 📚 Funções Disponíveis

### Em `includes/functions.php`

```php
// Formatação
formatarData($data)
formatarDataHora($dataHora)
formatarNumero($numero, $decimais)
formatarPercentual($valor, $decimais)

// Cálculos Industriais
calcularOEE($disponibilidade, $performance, $qualidade)
calcularDisponibilidade($tempoProduzindo, $tempoParado)
calcularPerformance($producaoReal, $producaoPlanejada)
calcularQualidade($producaoBoa, $producaoTotal)
calcularEficiencia($velocidadeReal, $velocidadePlanejada)

// Utilitários
limpar($string)
obterIP()
respostaJSON($sucesso, $mensagem, $dados)
```

### Em `banco/conexao_pdo.php`

```php
registrarAuditoria($usuario_id, $acao, $tabela, $registro_id, $detalhes)
registrarEdicao($usuario_id, $tabela, $registro_id, $campo, $valor_antigo, $valor_novo)
temPermissao($permissao)
```

---

## 🔒 Segurança

- ✅ Senhas hash com bcrypt (PASSWORD_BCRYPT)
- ✅ Prepared Statements (PDO)
- ✅ Validação de entrada (htmlspecialchars)
- ✅ Proteção de sessão
- ✅ Log completo de auditoria
- ✅ Controle de acesso por perfil

---

## 🆘 Suporte

### Problemas Comuns

**Erro: "Erro ao conectar ao banco de dados"**
- Verifique configurações em `banco/conexao_pdo.php`
- Confirme se MySQL está rodando
- Verifique permissões do usuário

**Erro: "Sessão expirada"**
- Sessão dura 30 minutos
- Altere em `includes/auth.php` se necessário

**Erro 403 - Acesso Negado**
- Verifique permissões de pasta
- Confirme perfil do usuário
- Verifique permissões na função `temPermissao()`

---

## 📞 Contato

**Suporte:** suporte@jfm.com.br  
**Desenvolvedor:** Equipe TI  

---

**Data:** Junho de 2026  
**Versão:** 2.0.0
