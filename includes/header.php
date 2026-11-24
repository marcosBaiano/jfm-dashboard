<?php
/**
 * Header padrão do sistema
 * Uso: <?php include 'includes/header.php'; ?>
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($titulo) ? htmlspecialchars($titulo) : 'Sistema Industrial JFM'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- jQuery (opcional) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <style>
        :root {
            --cor-primaria: #1e40af;
            --cor-secundaria: #7c3aed;
            --cor-sucesso: #16a34a;
            --cor-alerta: #ea580c;
            --cor-perigo: #dc2626;
            --cor-fundo: #f8fafc;
        }
        
        body {
            background-color: var(--cor-fundo);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
        }
        
        .sidebar {
            background-color: #fff;
            min-height: 100vh;
            box-shadow: 2px 0 4px rgba(0,0,0,0.05);
            padding-top: 1rem;
        }
        
        .sidebar a {
            color: #666;
            padding: 0.75rem 1.5rem;
            display: block;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .sidebar a:hover,
        .sidebar a.active {
            background-color: var(--cor-fundo);
            color: var(--cor-primaria);
            border-left-color: var(--cor-primaria);
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        
        .card-header {
            background-color: var(--cor-fundo);
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
        }
        
        .badge-pill {
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
        
        .table {
            font-size: 0.95rem;
        }
        
        .table thead {
            background-color: var(--cor-fundo);
            font-weight: 600;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
    
    <?php if (isset($estilosAdicionais)): ?>
        <?php echo $estilosAdicionais; ?>
    <?php endif; ?>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard/">
                <i class="fas fa-industry"></i> SISTEMA JFM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <?php echo isset($usuario_nome) ? $usuario_nome : 'Usuário'; ?>
                            <small class="ms-2 badge bg-light text-dark"><?php echo isset($perfil) ? $perfil : 'N/A'; ?></small>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/processos/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <nav class="col-md-2 sidebar">
                <div class="nav flex-column">
                    <h6 class="px-3 mt-3 mb-3 text-muted" style="font-weight: 700;">MENU PRINCIPAL</h6>
                    
                    <a href="/dashboard/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <a href="/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> Registrar Parada
                    </a>
                    
                    <a href="/dashboard/paradas.php" class="nav-link">
                        <i class="fas fa-pause-circle"></i> Paradas
                    </a>
                    
                    <a href="/dashboard/velocidade.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Velocidade
                    </a>
                    
                    <a href="/dashboard/eficiencia.php" class="nav-link">
                        <i class="fas fa-chart-line"></i> Eficiência
                    </a>
                    
                    <a href="/dashboard/oee.php" class="nav-link">
                        <i class="fas fa-star"></i> OEE
                    </a>
                    
                    <h6 class="px-3 mt-4 mb-3 text-muted" style="font-weight: 700;">RELATÓRIOS</h6>
                    
                    <a href="/relatorios/producao.php" class="nav-link">
                        <i class="fas fa-file-pdf"></i> Relatório Produção
                    </a>
                    
                    <a href="/relatorios/paradas.php" class="nav-link">
                        <i class="fas fa-file-pdf"></i> Relatório Paradas
                    </a>
                    
                    <?php if (isset($perfil) && ($perfil == 'Administrador' || $perfil == 'Supervisor')): ?>
                        <h6 class="px-3 mt-4 mb-3 text-muted" style="font-weight: 700;">ADMINISTRAÇÃO</h6>
                        
                        <a href="/admin/usuarios.php" class="nav-link">
                            <i class="fas fa-users"></i> Usuários
                        </a>
                        
                        <a href="/admin/auditoria.php" class="nav-link">
                            <i class="fas fa-history"></i> Auditoria
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
            
            <!-- CONTEÚDO PRINCIPAL -->
            <main class="col-md-10 main-content">
