<?php
    include('valida_sessao.php');

    include('conexao.php');

    // função para redimensionar e salvar a image
    function redimensionarESalvarImagem($arquivo, $largura = 80, $altura = 80) {
        $diretorio_destino = "img/";
        $nome_arquivo = uniqid() . '_' . basename($arquivo["name"]);
        $caminho_completo = $diretorio_destino . $nome_arquivo;
        $tipo_arquivo = strtolower(pathinfo($caminho_completo, PATHINFO_EXTENSION));

        // verifica se é uma imagem válida
        $check = getimagesize($arquivo["tmp_name"]);
        if ($check === false) {
            return "O arquivo não é uma imagem válida.";
        }

        // verifica o tamanhop do arquivo (limite de 5mb)
        if ($arquivo["size"] > 5000000){
            return "O arquivo é muito grande. O tamanho máximo é 5MB.";
        }

        // Permite apenas alguns formatos de arquivo
        if($tipo_arquivo != "jpg" && $tipo_arquivo != "jpeg" && $tipo_arquivo != "png" && $tipo_arquivo != "gif"){
            return "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
        }

        // Cria uma nova imagem a partir do arquivo enviado
        if($tipo_arquivo == 'jpg' || $tipo_arquivo == 'jpeg'){
            $imagem_original = imagecreatefromjpeg($arquivo["tmp_name"]);
        } elseif($tipo_arquivo == 'png'){
            $imagem_original = imagecreatefrompng($arquivo["tmp_name"]);
        } elseif($tipo_arquivo == 'gif'){
            $imagem_original = imagecreatefromgif($arquivo["tmp_name"]);
        }

        // Obtém as dimensões originais da imagem
        $largura_original = imagesx($imagem_original);
        $altura_original = imagesy($imagem_original);

        // Calcula as novas dimensões mantendo a proporção
        $ratio = min($largura / $largura_original, $altura / $altura_original);
        $nova_largura = $largura_original * $ratio;
        $nova_altura = $altura_original * $ratio;

        // cria uma nova imagem com as dimensões calculadas
        $nova_imagem = imagecreatetruecolor($nova_largura,$nova_altura);

        // Redimensiona a imagem original para a nova imagem
        imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0,$nova_largura, $nova_altura, $largura_original, $altura_original);

        // SALVA A NOVA IMAGEM
        if($tipo_arquivo == 'jpg' || $tipo_arquivo == 'jpeg'){
            imagejpeg($nova_imagem, $caminho_completo, 90);
        } elseif($tipo_arquivo == 'png'){
            imagepng($nova_imagem, $caminho_completo);
        } elseif($tipo_arquivo == 'gif'){
            imagegif($nova_imagem, $caminho_completo);
        }

        // Libera a memória
        imagedestroy($imagem_original);
        imagedestroy($nova_imagem);

        return $caminho_completo;
    }

        // Verifica se o formulário foi enviado
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $id = $_POST['id'];
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $telefone = $_POST['telefone'];

            $imagem = "";
            if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0){
                $resultado_upload = redimensionarESalvarImagem($_FILES['imagem']);
                if(strpos($resultado_upload, 'img/') === 0){
                    $imagem = $resultado_upload;
                } else {
                    $mensagem_erro = $resultado_upload;
                }
            }

            // Prepara a query sql para a inserção ou atualização
            if($id){
                // Se o ID existe, é uma atualização
                $sql = "UPDATE fornecedores SET nome = '$nome', email = '$email', telefone = '$telefone'";
                if($imagem){
                    $sql .= ", imagem='$imagem'";
                }
                $sql .= " WHERE id = '$id'";
                $mensagem = "Fornecedor atualizado com sucesso!";
            } else {
                // Se não há ID, é uma nova inserção
                $sql = "INSERT INTO fornecedores (nome, email, telefone, imagem) VALUES ('$nome', '$email', '$telefone', '$imagem')";
                $mensagem = "Fornecedor cadastrado com sucesso!";
            }

            // Executa a query e verifica se house erro
            if($conn->query($sql) !== TRUE){
                $mensagem = "Erro: " . $conn->error;
            }
    }

    // verifica se foi solicitada a exclusão de um fornecedor
    if(isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];

        // Verifica se o fornecedor tem produtos cadastrados
        $check_produtos = $conn->query("SELECT COUNT(*) as count FROM produtos WHERE fornecedor_id = '$delete_id'")->fetch_assoc();

        if($check_produtos['count'] > 0){
            $mensagem = "Não é possivel excluir este fornecedor pois existem produtos cadastrados para ele.";
        } else {
            $sql = "DELETE FROM fornecedores WHERE id='$delete_id";
            if($conn->query($sql) === TRUE) {
                $mensagem = "Fornecedor excluido com sucesso!";
            } else {
                $mensagem = "Erro ao exlcuir fornecedor: " . $conn->error;
            }
        }
    }

    // Busca todos os fornecedores para listar na tabela
    $fornecedores = $conn->query("SELECT * from fornecedores");

    // se foi solicitada a edição de um fornecedor, busca os dados dele
    $fornecedor = null;
    if(isset($_GET['edit_id'])){
        $edit_id = $_GET['edit_id'];
        $fornecedor = $conn->query("SELECT * FROM fornecedores WHERE id='edit_id'")->fetch_assoc();
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Cadastro de Fornecedor</title>
</head>
<body>
    <div class="container" style="width: 900px;">
        <h1>Cadastro de Fornecedor</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $fornecedor['id'] ??''; ?>">

            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo $fornecedor['nome'] ?? '';?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo $fornecedor['email'] ?? '';?>" required>

            <label for="telefone">Telefone:</label>
            <input type="text" name="telefone" value="<?php echo $fornecedor['telefone'] ?? '';?>" required>

            <label for="imagem">Imagem:</label>
            <input type="file" name="imagem" accept="image/*">
            <?php if(isset($fornecedor['imagem']) && $fornecedor['imagem']): ?>
                <img src="<?php echo $fornecedor ? 'Atualizar' : 'Cadastrar'; ?>" alt="Imagem atual do fornecedor" class="update-image">
            <?php endif; ?><br>
            <button type="submit"><?php echo $fornecedor ? 'Atualizar' : 'Cadastrar'; ?></button>
        </form>

<!-- Exibe mensagens de sucesso ou erro -->
        <?php
        if(isset($mensagem)) echo "<p class='message " . (strpos($mensagem, 'Erro') !== false ? "error" : "success") . "'>$mensagem</p>";
        if(isset($mensagem_erro)) echo "<p class='message error'>$mensagem_erro</p>";
        ?>
<!-- Tabela para listar os fornecedores cadastrados -->
        <h2>Listagem de Fornecedores</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Ações</th>
            </tr>
            <?php while($row = $fornecedores->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <?php if($row['imagem']): ?>
                            <img src="<?php echo $row['imagem']; ?>" alt="Imagem do fornecedor" class="thumbnail">
                        <?php else: ?>
                            Sem imagem
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['nome']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['telefone']; ?></td>
                    <td>
                        <a href="?edit_id=<?php echo $row['id']; ?>">Editar</a>
                        <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Deletar</a>
                    </td>
                </tr>
                
                <?php endwhile; ?>
        </table>
        <div class="actions">
            <a href="index.php" class="back-button">Voltar</a>
        </div>
    </div>
</body>
</html>