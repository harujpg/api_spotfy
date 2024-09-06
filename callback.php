<?php
session_start();

// Detalhes do aplicativo
$client_id = '74975fbc717d483eba04bc64f7a83bee';
$client_secret = 'ad923cafe50a4a588baeadf17564dee8'; // Substitua pelo seu Client Secret do Spotify
$redirect_uri = 'http://localhost/projeto_api/callback.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Solicita o token de acesso
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (isset($responseData['access_token'])) {
        // Armazenar tokens na sessão
        $_SESSION['access_token'] = $responseData['access_token'];
        $_SESSION['refresh_token'] = $responseData['refresh_token'];

        // Redirecionar para a página do player
        header('Location: player.php');
        exit;
    } else {
        // Exibe erro caso não consiga obter o token
        echo 'Erro ao obter o token de acesso.';
    }
} else {
    echo 'Código de autorização não recebido.';
}
?>