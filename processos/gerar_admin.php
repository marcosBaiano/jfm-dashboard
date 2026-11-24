<?php
require_once '../banco/conexao_pdo.php';

$email = 'admin@jfm.com.br';
$nome = 'Administrador JFM';
// Senha criptografada para 'admin123'
$senha_hash = password_hash('admin123', PASSWORD_DEFAULT); 
$perfil = 'administrador'; // Alterado para minúsculo para evitar o erro de truncamento
$ativo = 1;

try {
    // Verifica se o usuário já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Atualiza a senha e garante acesso
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha, ativo = :ativo, nome = :nome, perfil = :perfil WHERE id = :id");
        $stmt->execute([
            ':senha' => $senha_hash,
            ':ativo' => $ativo,
            ':nome' => $nome,
            ':perfil' => $perfil,
            ':id' => $usuario['id']
        ]);
        echo "<h3>Usuário admin atualizado com sucesso!</h3>";
    } else {
        // Cria um novo
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (:nome, :email, :senha, :perfil, :ativo)");
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senha_hash,
            ':perfil' => $perfil,
            ':ativo' => $ativo
        ]);
        echo "<h3>Usuário admin criado com sucesso!</h3>";
    }
    echo "<p>Agora acesse o login e tente conectar com a senha <b>admin123</b>.</p>";
} catch (Exception $e) {
    echo "<h3>Erro ao ajustar o banco de dados:</h3> " . $e->getMessage();
}
?>
