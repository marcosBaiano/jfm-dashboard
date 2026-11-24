<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Centralizado de Paradas</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<main>

    <h1 class="titulo-pagina">
        <i class="fa-solid fa-industry"></i> SISTEMA CENTRALIZADO DE PARADAS
    </h1>

    <div class="keyboard-info-bar">
        <span><i class="fa-solid fa-bolt"></i> <strong>Atalhos Industriais:</strong></span>
        <span><span class="keyboard-key">ENTER</span> Pula Campo / Salva Linha</span>
        <span><span class="keyboard-key">SETA BAIXO ↓</span> Adiciona Ocorrência (no final)</span>
        <span><span class="keyboard-key">↓ / ↑</span> Seleciona Motivos no Autocomplete</span>
        <span><span class="keyboard-key">ESC</span> Fecha Sugestões</span>
    </div>

    <div class="card formulario-container">

   <form action="salvar_registro.php" method="POST">
            
            <div class="dados-gerais">
                
                <div class="campo">
                    <label>SETOR</label>
                    <div class="grupo-botoes">
                        <button type="button" class="botao-opcao ativo" id="btn-setor-po" onclick="carregarLinhas('PO')">PO</button>
                        <button type="button" class="botao-opcao" id="btn-setor-liq" onclick="carregarLinhas('LIQ')">LIQ</button>
                        <button type="button" class="botao-opcao" id="btn-setor-bar" onclick="carregarLinhas('BAR')">BARRA</button>
                    </div>
                </div>

                <div class="campo" style="grid-column: span 2;">
                    <label>LINHA</label>
                    <div id="container-linhas" class="grupo-botoes">
                        </div>
                    <input type="hidden" name="linha" id="linha_hidden">
                </div>

                <div class="campo">
                    <label>TURNO</label>
                    <div class="grupo-botoes">
                        <button type="button" class="botao-opcao btn-turno selecionado" data-grupo="turno" data-valor="1" onclick="selecionarBotao(this, 'turno', 'turno_hidden')">1</button>
                        <button type="button" class="botao-opcao btn-turno" data-grupo="turno" data-valor="2" onclick="selecionarBotao(this, 'turno', 'turno_hidden')">2</button>
                        <button type="button" class="botao-opcao btn-turno" data-grupo="turno" data-valor="3" onclick="selecionarBotao(this, 'turno', 'turno_hidden')">3</button>
                    </div>
                    <input type="hidden" name="turno" id="turno_hidden" value="1">
                </div>

                <div class="campo">
                    <label>OPERADOR</label>
                    <input type="text" name="operador" class="input" required autocomplete="off" placeholder="Aristóteles">
                </div>

                <div class="campo">
                    <label>DATA</label>
                    <input type="text" id="data_registro" name="data_registro" class="input" required placeholder="Selecione a data">
                </div>

            </div>

            <div class="section-title">
                <span>Relatório de Paradas e Ocorrências</span>
                <button type="button" class="btn-add" id="btn-adicionar-parada">
                    <i class="fa-solid fa-plus-circle"></i> + Ocorrência
                </button>
            </div>

            <div class="table-wrapper">
                <table id="tabelaParadas">
                    <thead>
                        <tr>
                            <th style="width: 14%;">Início</th>
                            <th style="width: 14%;">Fim</th>
                            <th style="width: 12%;">Código</th>
                            <th style="width: 25%;">Descrição</th>
                            <th style="width: 20%;">Observações</th>
                            <th style="width: 10%;">Tempo (Min)</th>
                            <th style="width: 5%;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-paradas">
                        </tbody>
                </table>
            </div>

            <div class="section-title">
                <span>Produtos & Perdas Reguladas</span>
                <button type="button" class="btn-add" id="btn-adicionar-produto" style="background-color: var(--cor-azul);">
                    <i class="fa-solid fa-circle-plus"></i> + Produto
                </button>
            </div>

            <div class="table-wrapper">
                <table id="tabela-producao">
                    <thead>
                        <tr id="cabecalho-producao">
                            <th style="width: 25%;">MARCA / DESCRIÇÃO</th>
                            <th style="width: 20%;">FORMATO (SACHÊ)</th>
                            <th style="width: 15%;">PRODUÇÃO</th>
                            <th style="width: 15%;">PERDA COLORIDA</th>
                            <th style="width: 15%;">PERDA SEM COR</th>
                            <th style="width: 10%;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-producao">
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                <span>Velocidade da Máquina de Envase (A cada 1h)</span>
            </div>

            <div class="table-wrapper">
                <table class="grid-velocidades-tabela">
                    <thead>
                        <tr>
                            <th>07:00</th>
                            <th>08:00</th>
                            <th>09:00</th>
                            <th>10:00</th>
                            <th>11:00</th>
                            <th>12:00</th>
                            <th>13:00</th>
                            <th>14:00</th>
                            <th>15:00</th>
                            <th>16:00</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                            <td><input type="text" name="velocidade[]" class="input-tabela input-velocidade-envase" autocomplete="off" placeholder="0"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="botao-submit">
                <i class="fa-solid fa-floppy-disk"></i> SALVAR PARADAS E DADOS DO TURNO
            </button>

        </form>

    </div>

</main>

<div id="toast" class="toast-container">
    <i id="toast-icon" class="fa-solid fa-circle-check"></i>
    <span id="toast-message">Registro atualizado com sucesso!</span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>