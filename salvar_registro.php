<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ======================================================
// CONEXÃO
// ======================================================

require_once 'banco/conexao.php';

$conn->set_charset("utf8mb4");

// O nome do seu arquivo de formulário (mude se for .php ou outro nome)
$URL_FORMULARIO = "index.html"; 

// ======================================================
// FUNÇÕES
// ======================================================

function decimal($valor)
{
    if ($valor === '' || $valor === null) {
        return null;
    }

    return str_replace(',', '.', $valor);
}

function dataHora($valor)
{
    if (!$valor) {
        return null;
    }

    return date('Y-m-d H:i:s', strtotime($valor));
}

try {
    // REMOVIDO: print_r($_POST) para não bloquear o redirecionamento posterior

    $conn->begin_transaction();

    // ======================================================
    // REGISTRO PRINCIPAL
    // ======================================================

    $linhaCodigo = trim($_POST['linha'] ?? '');
    $turno       = trim($_POST['turno'] ?? '');
    $operador    = trim($_POST['operador'] ?? '');
    $data        = trim($_POST['data_registro'] ?? '');

    if (!$linhaCodigo) {
        throw new Exception("Linha vazia");
    }

    // ======================================================
    // BUSCAR LINHA
    // ======================================================

    $stmtLinha = $conn->prepare("
        SELECT id
        FROM linhas
        WHERE codigo = ?
        LIMIT 1
    ");

    $stmtLinha->bind_param("s", $linhaCodigo);
    $stmtLinha->execute();

    $resLinha = $stmtLinha->get_result();

    if ($resLinha->num_rows <= 0) {
        throw new Exception("Linha não encontrada: {$linhaCodigo}");
    }

    $linha = $resLinha->fetch_assoc();
    $linha_id = (int)$linha['id'];

    // ======================================================
    // INSERT REGISTRO
    // ======================================================

    $stmtRegistro = $conn->prepare("
        INSERT INTO registros (
            linha_id,
            turno,
            operador,
            data_registro
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmtRegistro->bind_param(
        "isss",
        $linha_id,
        $turno,
        $operador,
        $data
    );

    $stmtRegistro->execute();
    $registro_id = $conn->insert_id;

    // ======================================================
    // PARADAS
    // ======================================================

    $stmtParada = $conn->prepare("
        INSERT INTO paradas (
            registro_id,
            numero,
            inicio,
            fim,
            codigo,
            descricao,
            observacoes,
            tempo
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $inicios     = $_POST['inicio'] ?? [];
    $fins        = $_POST['fim'] ?? [];
    $codigos     = $_POST['codigo'] ?? [];
    $descricoes  = $_POST['descricao'] ?? [];
    $observacoes = $_POST['observacoes'] ?? [];
    $tempos      = $_POST['tempo'] ?? [];

    foreach ($inicios as $i => $inicioValor) {

        $inicio = dataHora($inicioValor);
        $fim    = dataHora($fins[$i] ?? null);

        $codigo    = trim($codigos[$i] ?? '');
        $descricao = trim($descricoes[$i] ?? '');
        $obs       = trim($observacoes[$i] ?? '');
        $tempo     = (int)($tempos[$i] ?? 0);

        if (!$inicio && !$fim && !$descricao) {
            continue;
        }

        $numero = $i + 1;

        $stmtParada->bind_param(
            "iisssssi",
            $registro_id,
            $numero,
            $inicio,
            $fim,
            $codigo,
            $descricao,
            $obs,
            $tempo
        );

        $stmtParada->execute();
    }

    // ======================================================
    // VELOCIDADES
    // ======================================================

    $stmtVel = $conn->prepare("
        INSERT INTO velocidades (
            registro_id,
            hora,
            velocidade
        )
        VALUES (?, ?, ?)
    ");

    $velocidades = $_POST['velocidade'] ?? [];

    foreach ($velocidades as $i => $valor) {

        if ($valor === '') {
            continue;
        }

        $horaNumero = $i + 7;
        $hora = str_pad($horaNumero, 2, '0', STR_PAD_LEFT) . ":00:00";
        $velocidade = decimal($valor);

        $stmtVel->bind_param(
            "iss",
            $registro_id,
            $hora,
            $velocidade
        );

        $stmtVel->execute();
    }

    // ======================================================
    // PRODUÇÃO (MAPEADO DE PERDA_1 À PERDA_4)
    // ======================================================

    $stmtProd = $conn->prepare("
        INSERT INTO producao (
            registro_id,
            marca,
            formato,
            producao,
            perda_1,
            perda_2,
            perda_3,
            perda_4
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $marcas    = $_POST['marca'] ?? [];
    $formatos  = $_POST['formato'] ?? [];
    $producoes = $_POST['producao'] ?? [];
    $perda1s   = $_POST['perda_1'] ?? []; // Caixa no LIQ / Colorida no PO
    $perda2s   = $_POST['perda_2'] ?? []; // Rótulo no LIQ / Sem Cor no PO
    $perda3s   = $_POST['perda_3'] ?? []; // Tampa no LIQ / Vazio nos outros
    $perda4s   = $_POST['perda_4'] ?? []; // Garrafa no LIQ / Vazio nos outros

    foreach ($marcas as $i => $marcaValor) {

        $marca = trim($marcaValor);

        if (!$marca) {
            continue;
        }

        $formato  = decimal($formatos[$i] ?? null);
        $producao = decimal($producoes[$i] ?? null);
        $perda1   = decimal($perda1s[$i] ?? null);
        $perda2   = decimal($perda2s[$i] ?? null);
        $perda3   = decimal($perda3s[$i] ?? null);
        $perda4   = decimal($perda4s[$i] ?? null);

        // Bind configurado para 8 parâmetros: "isssssss"
        $stmtProd->bind_param(
            "isssssss",
            $registro_id,
            $marca,
            $formato,
            $producao,
            $perda1,
            $perda2,
            $perda3,
            $perda4
        );

        $stmtProd->execute();
    }

    // ======================================================
    // COMMIT E REDIRECIONAMENTO DE SUCESSO
    // ======================================================

    $conn->commit();
    $stmtLinha->close();
    $stmtRegistro->close();
    $stmtParada->close();
    $stmtVel->close();
    $stmtProd->close();
    $conn->close();

    // Redireciona informando sucesso=1
    header("Location: " . $URL_FORMULARIO . "?sucesso=1");
    exit();

} catch (Exception $e) {

    $conn->rollback();
    $conn->close();

    // Redireciona enviando a mensagem do erro tratada na URL
    $msgErro = urlencode($e->getMessage());
    header("Location: " . $URL_FORMULARIO . "?erro=" . $msgErro);
    exit();
}