  // Mapeamento real das Linhas de Produção da Fábrica
    const LinhasProducao = {
        'PO': [
            'ESP-ENV-PO-01', 'ESP-ENV-PO-02', 'ESP-ENV-PO-03', 'ESP-ENV-PO-04', 
            'ESP-ENV-PO-05', 'ESP-ENV-PO-06', 'ESP-ENV-PO-07', 'ESP-ENV-PO-08', 
            'ESP-ENV-PO-09', 'ESP-ENV-PO-10', 'ESP-ENV-PO-11', 'ESP-ENV-PO-12', 
            'ESP-ENV-PO-13', 'ESP-ENV-PO-14', 'ESP-ENV-PO-15'
        ],
        'LIQ': [
            'ESP-ENV-LIQ-01', 'ESP-ENV-LIQ-02', 'ESP-ENV-LIQ-03'
        ],
        'BAR': [
            'ESP-ENV-BAR-01-AGRUP', 'ESP-ENV-BAR-02-UNIT', 'ESP-ENV-BAR-03-EXT'
        ]
    };

    // =========================================================
    // GLOBAL APP CONFIG
    // =========================================================
    const App = {
        flatpickrDataConfig: {
            locale: "pt",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true,
            disableMobile: "true"
        },
        flatpickrHoraConfig: {
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "d/m/Y H:i",
            locale: "pt",
            allowInput: true,
            minuteIncrement: 1,
            disableMobile: "true"
        },

        getDataRegistroFormatada: function() {
            const drElement = document.getElementById('data_registro');
            if (drElement && drElement._flatpickr && drElement._flatpickr.selectedDates.length > 0) {
                return flatpickr.formatDate(drElement._flatpickr.selectedDates[0], "Y-m-d");
            }
            return drElement && drElement.value ? drElement.value : new Date().toISOString().split('T')[0];
        },

        init: function() {
            this.initDataPrincipal();
            this.bindEvents();
            
            // Verifica se os elementos de registro existem (não existem em páginas do dashboard)
            if (document.getElementById('btn-setor-po')) {
                carregarLinhas('PO');
                // Inicia com uma linha padrão em cada tabela
                if (document.getElementById('tbody-paradas')) Paradas.adicionarLinha();
                if (document.getElementById('tbody-producao')) Producao.adicionarLinha();
            }
        },

        initDataPrincipal: function() {
            const dataInput = document.getElementById('data_registro');
            // Elemento data_registro só existe em páginas de registro
            if (!dataInput) return;
            
            dataInput.value = new Date().toISOString().split('T')[0];

            flatpickr(dataInput, {
                ...this.flatpickrDataConfig,
                onChange: function(selectedDates, dateStr) {
                    App.sincronizarDatasGlobais(dateStr);
                }
            });
        },

        sincronizarDatasGlobais: function(novaData) {
            if (!novaData) return;
            $(".campo-horario").each(function() {
                const fp = this._flatpickr;
                if (!fp) return;
                const valorAtual = fp.input.value;
                let hora = "00:00";
                if (valorAtual && valorAtual.includes(" ")) {
                    hora = valorAtual.split(" ")[1];
                }
                fp.setDate(`${novaData} ${hora}`, true);
            });
            App.notificar("Datas das paradas atualizadas!", "sucesso");
        },

        notificar: function(msg, tipo = "sucesso") {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const text = document.getElementById('toast-message');

            text.textContent = msg;
            if (tipo === "erro") {
                toast.classList.add('erro');
                icon.className = "fa-solid fa-circle-exclamation";
            } else {
                toast.classList.remove('erro');
                icon.className = "fa-solid fa-circle-check";
            }
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        },

        bindEvents: function() {
            // Elementos de registro - só existem em páginas de registro
            const btnAdicionarParada = document.getElementById('btn-adicionar-parada');
            const btnAdicionarProduto = document.getElementById('btn-adicionar-produto');
            
            if (btnAdicionarParada) {
                btnAdicionarParada.addEventListener('click', () => Paradas.adicionarLinha());
            }
            if (btnAdicionarProduto) {
                btnAdicionarProduto.addEventListener('click', () => Producao.adicionarLinha());
            }
            
            // Monitoramento global de teclado para facilidade industrial (Atalho Seta para baixo)
            // Mas só se estivermos em página com registros
            if (btnAdicionarParada || btnAdicionarProduto) {
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowDown' && document.activeElement.tagName !== 'INPUT') {
                        if (btnAdicionarParada) Paradas.adicionarLinha();
                    }
                });
            }
        }
    };

    // =========================================================
    // SELETOR DE LINHAS E BOTÕES
    // =========================================================
    function carregarLinhas(setor) {
        const botoesSetor = document.querySelectorAll('#btn-setor-po, #btn-setor-liq, #btn-setor-bar');
        if (botoesSetor.length === 0) return; // Não existem em páginas do dashboard
        
        botoesSetor.forEach(btn => btn.classList.remove('ativo', 'selecionado'));
        
        if (setor === 'PO') document.getElementById('btn-setor-po')?.classList.add('selecionado');
        if (setor === 'LIQ') document.getElementById('btn-setor-liq')?.classList.add('selecionado');
        if (setor === 'BAR') document.getElementById('btn-setor-bar')?.classList.add('selecionado');

        const container = document.getElementById('container-linhas');
        if (!container) return; // Não existe em páginas do dashboard
        
        container.innerHTML = '';

        const lista = LinhasProducao[setor] || [];
        lista.forEach((numLinha, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `botao-opcao btn-linha ${idx === 0 ? 'selecionado' : ''}`;
            btn.textContent = numLinha;
            btn.onclick = function() {
                selecionarBotao(this, 'linha', 'linha_hidden');
            };
            container.appendChild(btn);
            if (idx === 0) {
                const linhaHidden = document.getElementById('linha_hidden');
                if (linhaHidden) linhaHidden.value = numLinha;
            }
        });
        
        // Atualiza tabelas de produção se existirem
        if (Producao && Producao.container) {
            Producao.container.innerHTML = ''; // Limpa as linhas antigas do setor anterior
            Producao.atualizarCabecalho();     // Muda as colunas do topo
            Producao.adicionarLinha();          // Insere uma linha inicial limpa no novo formato
        }
    }

    function selecionarBotao(botao, grupo, idCampoHidden) {
        const parent = botao.parentElement;
        parent.querySelectorAll("button").forEach(btn => btn.classList.remove("selecionado", "ativo"));
        botao.classList.add("selecionado");
        const valor = botao.textContent.trim();
        document.getElementById(idCampoHidden).value = valor;
    }

   // =========================================================
// MÓDULO DE PARADAS (COMPORTAMENTO E AJAX REAL ATUALIZADO)
// =========================================================
const Paradas = {
    container: document.getElementById('tbody-paradas'),

    // Função auxiliar para pegar o setor que está ativo no momento
    ObterSetorAtivo: function() {
        const po = document.getElementById('btn-setor-po');
        const liq = document.getElementById('btn-setor-liq');
        const bar = document.getElementById('btn-setor-bar');
        
        if (po && po.classList.contains('selecionado')) return 'PO';
        if (liq && liq.classList.contains('selecionado')) return 'LIQ';
        if (bar && bar.classList.contains('selecionado')) return 'BAR';
        return 'PO'; // Padrão de segurança
    },

    adicionarLinha: function() {
        if (!this.container) return; // Não existe em páginas do dashboard
        
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td><input type="text" name="inicio[]" class="input-tabela campo-horario" required></td>
            <td><input type="text" name="fim[]" class="input-tabela campo-horario" required></td>
            <td><input type="text" name="codigo[]" class="input-tabela campo-codigo-parada" autocomplete="off"></td>
            <td>
                <div class="autocomplete-wrapper">
                    <input type="text" name="descricao[]" class="input-tabela campo-desc-parada" autocomplete="off">
                    <div class="sugestoes" style="display:none;"></div>
                </div>
            </td>
            <td><input type="text" name="observacoes[]" class="input-tabela" autocomplete="off"></td>
            <td><input type="text" name="tempo[]" class="input-tabela campo-tempo-calculado" readonly></td>
            <td style="text-align:center;"><button type="button" class="btn-delete"><i class="fa-solid fa-trash"></i></button></td>
        `;

        this.container.appendChild(tr);
        this.vincularComponentes(tr);
    },

    vincularComponentes: function(tr) {
        const dataBase = App.getDataRegistroFormatada();
        const inputInicio = tr.querySelector('input[name="inicio[]"]');
        const inputFim = tr.querySelector('input[name="fim[]"]');
        const inputCodigo = tr.querySelector('input[name="codigo[]"]');
        const inputDesc = tr.querySelector('input[name="descricao[]"]');
        const wrapperSugestao = tr.querySelector('.sugestoes');
        const inputTempo = tr.querySelector('.campo-tempo-calculado');

        // Proteger contra elementos null
        if (!inputInicio || !inputFim || !inputCodigo || !inputDesc || !wrapperSugestao || !inputTempo) return;

        // Inicializa Flatpickr
        if (flatpickr && typeof flatpickr === 'function') {
            flatpickr(inputInicio, { ...App.flatpickrHoraConfig, defaultDate: `${dataBase} 00:00`, onChange: () => this.calcularTempo(inputInicio, inputFim, inputTempo) });
            flatpickr(inputFim, { ...App.flatpickrHoraConfig, defaultDate: `${dataBase} 00:00`, onChange: () => this.calcularTempo(inputInicio, inputFim, inputTempo) });
        }

        // Botão excluir linha
        const btnDelete = tr.querySelector('.btn-delete');
        if (btnDelete) {
            btnDelete.addEventListener('click', () => {
                if(this.container && this.container.rows.length > 1) tr.remove();
            });
        }

        // AJAX REAL 1: Busca descrição filtrando por Código E Setor
        inputCodigo.addEventListener('change', () => {
            const codVal = inputCodigo.value.trim();
            if(codVal === '') return;

            const setorAtual = this.ObterSetorAtivo();

            if (typeof $ !== 'undefined' && $.getJSON) {
                $.getJSON('assets/ajax/buscar_por_codigo.php', { codigo: codVal, setor: setorAtual }, function(res) {
                    if(res && res.descricao) {
                        inputDesc.value = res.descricao;
                    } else {
                        App.notificar("Código não cadastrado para este setor", "erro");
                    }
                });
            }
        });

        // AJAX REAL 2: Autocomplete preditivo filtrando por Descrição E Setor
        inputDesc.addEventListener('input', () => {
            const termo = inputDesc.value.trim();
            if(termo.length < 2) {
                wrapperSugestao.style.display = 'none';
                return;
            }

            const setorAtual = this.ObterSetorAtivo();

            if (typeof $ !== 'undefined' && $.getJSON) {
                $.getJSON('assets/ajax/buscar_sugestoes.php', { query: termo, setor: setorAtual }, function(dados) {
                    wrapperSugestao.innerHTML = '';
                    if(dados.length === 0) {
                        wrapperSugestao.style.display = 'none';
                        return;
                    }

                    dados.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'sugestao-item';
                        div.innerHTML = `<span>${item.descricao}</span><span class="sugestao-badge">${item.codigo}</span>`;
                        
                        div.addEventListener('click', () => {
                            inputCodigo.value = item.codigo;
                            inputDesc.value = item.descricao;
                            wrapperSugestao.style.display = 'none';
                        });
                        wrapperSugestao.appendChild(div);
                    });
                    wrapperSugestao.style.display = 'block';
                });
            }
        });

        // Fechar caixas de sugestão ao clicar fora
        document.addEventListener('click', (e) => {
            if (!tr.contains(e.target)) wrapperSugestao.style.display = 'none';
        });
    },

    calcularTempo: function(ini, fim, alvo) {
        if(!ini.value || !fim.value) return;
        const d1 = new Date(ini.value);
        const d2 = new Date(fim.value);
        if(d2 >= d1) {
            const diferencaMinutos = Math.floor((d2 - d1) / 60000);
            alvo.value = diferencaMinutos;
        } else {
            alvo.value = 0;
        }
    }
};
// =========================================================
// MÓDULO DE PRODUÇÃO (TABELA DE METRICAS DINÂMICA)
// =========================================================
const Producao = {
    container: document.getElementById('tbody-producao'),

    ObterSetorAtivo: function() {
        const po = document.getElementById('btn-setor-po');
        const liq = document.getElementById('btn-setor-liq');
        const bar = document.getElementById('btn-setor-bar');
        
        if (po && po.classList.contains('selecionado')) return 'PO';
        if (liq && liq.classList.contains('selecionado')) return 'LIQ';
        if (bar && bar.classList.contains('selecionado')) return 'BAR';
        return 'PO';
    },

    atualizarCabecalho: function() {
        const cabecalho = document.getElementById('cabecalho-producao');
        if (!cabecalho) return; // Não existe em páginas do dashboard
        
        const setor = this.ObterSetorAtivo();

        if (setor === 'LIQ') {
            cabecalho.innerHTML = `
                <th style="width: 25%;">MARCA / DESCRIÇÃO</th>
                <th style="width: 15%;">FORMATO (ML)</th>
                <th style="width: 11%;">PRODUÇÃO</th>
                <th style="width: 11%;">CAIXA</th>
                <th style="width: 11%;">RÓTULO</th>
                <th style="width: 11%;">TAMPA</th>
                <th style="width: 11%;">GARRAFA</th>
                <th style="width: 5%;">Ação</th>
            `;
        } else {
            cabecalho.innerHTML = `
                <th style="width: 25%;">MARCA / DESCRIÇÃO</th>
                <th style="width: 15%;">FORMATO (SACHÊ)</th>
                <th style="width: 15%;">PRODUÇÃO</th>
                <th style="width: 15%;">PERDA COLORIDA</th>
                <th style="width: 15%;">PERDA SEM COR</th>
                <th style="width: 15%;">Ação</th>
            `;
        }
    },

    adicionarLinha: function() {
        if (!this.container) return; // Não existe em páginas do dashboard
        
        const tr = document.createElement('tr');
        const setor = this.ObterSetorAtivo();
        
        if (setor === 'LIQ') {
            tr.innerHTML = `
                <td>
                    <div class="autocomplete-wrapper" style="position: relative; width: 100%;">
                        <input type="text" name="marca[]" class="input-tabela" placeholder="Ex: Detergente" autocomplete="off" required>
                        <div class="sugestoes" style="display:none; position: absolute; top: 100%; left: 0; z-index: 9999; background: #fff; border: 1px solid #ccc; width: 100%; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </td>
                <td><input type="text" name="formato[]" class="input-tabela" placeholder="500"></td>
                <td><input type="text" name="producao[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_1[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_2[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_3[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_4[]" class="input-tabela" placeholder="0"></td>
                <td style="text-align:center;"><button type="button" class="btn-delete"><i class="fa-solid fa-trash"></i></button></td>
            `;
        } else {
            tr.innerHTML = `
                <td>
                    <div class="autocomplete-wrapper" style="position: relative; width: 100%;">
                        <input type="text" name="marca[]" class="input-tabela" placeholder="Ex: Maionese" autocomplete="off" required>
                        <div class="sugestoes" style="display:none; position: absolute; top: 100%; left: 0; z-index: 9999; background: #fff; border: 1px solid #ccc; width: 100%; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </td>
                <td><input type="text" name="formato[]" class="input-tabela" placeholder="100"></td>
                <td><input type="text" name="producao[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_1[]" class="input-tabela" placeholder="0"></td>
                <td><input type="text" name="perda_2[]" class="input-tabela" placeholder="0"></td>
                <td style="text-align:center;"><button type="button" class="btn-delete"><i class="fa-solid fa-trash"></i></button></td>
            `;
        }

        this.container.appendChild(tr);
        
        tr.querySelector('.btn-delete').addEventListener('click', () => {
            if(this.container.rows.length > 1) tr.remove();
        });

        // Configuração do Autocomplete (Idêntico ao seu original)
        const inputProduto = tr.querySelector('input[name="marca[]"]');
        const inputFormato = tr.querySelector('input[name="formato[]"]');
        const wrapperSugestao = tr.querySelector('.sugestoes');

        if (!inputProduto || !inputFormato || !wrapperSugestao) return;

        inputProduto.addEventListener('input', () => {
            const termo = inputProduto.value.trim();
            if(termo.length < 2) {
                wrapperSugestao.style.display = 'none';
                return;
            }

            if (typeof $ !== 'undefined' && $.getJSON) {
                $.getJSON('assets/ajax/buscar_produtos.php', { query: termo }, function(dados) {
                    wrapperSugestao.innerHTML = '';
                    if(dados.length === 0) {
                        wrapperSugestao.style.display = 'none';
                        return;
                    }

                    dados.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'sugestao-item';
                    div.style.padding = '8px 12px';
                    div.style.cursor = 'pointer';
                    div.style.display = 'flex';
                    div.style.justify = 'space-between';
                    div.style.borderBottom = '1px solid #eee';

                    div.innerHTML = `<span>${item.descricao}</span><span class="sugestao-badge" style="background: #e0e0e0; padding: 2px 6px; font-size: 11px; border-radius: 4px;">${item.codigo}</span>`;
                    
                    div.addEventListener('click', () => {
                        inputProduto.value = item.descricao;
                        wrapperSugestao.style.display = 'none';
                        
                        const regexFormato = /(\d+)\s*(g|kg|ml|l)?$/i;
                        const correspondencia = item.descricao.match(regexFormato);
                        if (correspondencia) {
                            inputFormato.value = correspondencia[1];
                        }
                    });
                    wrapperSugestao.appendChild(div);
                });
                wrapperSugestao.style.display = 'block';
                });
            }
        });

        document.addEventListener('click', (e) => {
            if (!tr.contains(e.target)) wrapperSugestao.style.display = 'none';
        });
    }
};

        // Inicialização da aplicação ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        App.init();

        // Captura os parâmetros da URL para disparar o Toast do seu sistema
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('sucesso')) {
            App.notificar("Dados salvos com sucesso!", "sucesso");
            // Limpa a URL para sumir o ?sucesso=1 visualmente
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.has('erro')) {
            const mensagem = urlParams.get('erro');
            App.notificar("Erro ao salvar: " + mensagem, "erro");
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });