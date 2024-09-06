<?php
// Ative a exibição de erros para ajudar na depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Informações do aplicativo
$client_id = '74975fbc717d483eba04bc64f7a83bee';
$redirect_uri = 'http://localhost/projeto_api/callback.php';
$scopes = 'streaming user-read-email user-read-private user-modify-playback-state user-read-playback-state';

// Construindo a URL de autorização do Spotify
$url = 'https://accounts.spotify.com/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $client_id,
    'scope' => $scopes,
    'redirect_uri' => $redirect_uri,
]);

// Redirecionando para a página de login do Spotify
header('Location: ' . $url);
echo $url;
exit;
?>
