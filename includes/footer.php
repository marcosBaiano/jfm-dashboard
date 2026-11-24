            </main>
        </div>
    </div>
    
    <!-- FOOTER -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <strong>Sistema Industrial JFM</strong> - Gerenciamento de Produção e Paradas
                    </p>
                    <small class="text-muted">Desenvolvido por TI</small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Versão 2.0.0 | 
                        <span id="dataAtual"></span>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Atualizar data atual no footer
        document.getElementById('dataAtual').textContent = new Date().toLocaleDateString('pt-BR');
    </script>
    
    <?php if (isset($scriptAdicionais)): ?>
        <?php echo $scriptAdicionais; ?>
    <?php endif; ?>
</body>
</html>
