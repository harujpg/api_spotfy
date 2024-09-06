<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: login.php');
    exit;
}

$access_token = $_SESSION['access_token'];

// Verificar se há uma pesquisa
$query = isset($_GET['query']) ? $_GET['query'] : '';
$search_results = [];
$selected_track_id = isset($_GET['play']) ? $_GET['play'] : '';
$artist_info = null;

if ($query) {
    // Requisição para a API de Pesquisa do Spotify
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/search?' . http_build_query([
        'q' => $query,
        'type' => 'track',
        'limit' => 10
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $search_results = json_decode($response, true);
}

// Requisição para obter informações do artista da música selecionada
if ($selected_track_id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/tracks/' . $selected_track_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $track_info = json_decode($response, true);
    $artist_id = $track_info['artists'][0]['id'];

    // Obter informações adicionais do artista
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/artists/' . $artist_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $artist_info = json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Spotify</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            color: white;
        }

        body {
            background-color: black;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
        }

        .loader {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        }

        .loader-icon {
            font-size: 80px;
            color: #1db954;
        }

        .container {
            margin-left: 100px;
            background-color: rgba(99, 98, 95, 0.5);
            padding: 10px;
            width: 450px;
            border-radius: 9px;
            margin-top: 10px; /* Adiciona espaço acima da barra de pesquisa */
        }

        .container h1 {
            text-align: center;
        }

        #spotify-player {
            width: 500px;
            height: 375px;
            border: none;
            position: fixed;
            top: 0;
            right: 0;
            margin: 10px;
            z-index: 1000;
            margin-right: 100px;
        }

        .search-result {
            margin-bottom: 20px;
        }

        .track {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .track img {
            margin-right: 10px;
        }

        .track a {
            text-decoration: none;
            color: #1DB954;
        }

        .search-form {
            display: flex;
            align-items: center;
            background-color: #282828;
            padding: 8px 16px;
            border-radius: 24px;
            width: 400px;
            margin-top: 0px; /* Adiciona espaço acima do formulário de pesquisa */
        }

        .search-input {
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            font-size: 16px;
            width: 100%;
            padding: 8px;
        }

        .search-input::placeholder {
            color: #b3b3b3;
        }

        .search-button {
            background-color: #1db954;
            border: none;
            border-radius: 24px;
            color: white;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }

        .search-button:hover {
            background-color: #1ed760;
        }

        .search-form:hover {
            background-color: #3e3e3e;
        }

        .artist-info {
            width: 480px;
            height: 235px;
            border: none;
            position: fixed;
            top: 385px; /* Ajuste este valor para subir a faixa */
            right: 0;
            margin: 10px;
            z-index: 1000;
            margin-right: 100px;
            background-color: #3e3e3e;
            border-radius: 9px;
            padding: 10px;
            display: flex;
            align-items: center;
        }

        .artist-info img {
            width: 100px;
            height: 100px;
            margin-right: 20px;
            border-radius: 50%;
        }

        .artist-details {
            flex: 1;
        }

        .artist-details h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1DB954;
        }

        .artist-details p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader">
        <i class="fas fa-spinner fa-spin loader-icon"></i>
    </div>

    <div class="container">
        <h1>Reprodutor Spotify</h1>
        <!-- Formulário de pesquisa -->
        <form class="search-form" method="get" action="player.php">
            <input class="search-input" type="text" name="query" placeholder="Pesquise por músicas..." value="">
            <button class="search-button" type="submit">Buscar</button>
        </form>

        <?php if ($query && !empty($search_results['tracks']['items'])): ?>
            <h2>Resultados da Pesquisa:</h2>
            <?php foreach ($search_results['tracks']['items'] as $track): ?>
                <div class="search-result">
                    <div class="track">
                        <img src="<?php echo htmlspecialchars($track['album']['images'][0]['url']); ?>" alt="<?php echo htmlspecialchars($track['name']); ?>" width="50" height="50">
                        <div>
                            <strong>
                                <a href="?query=<?php echo urlencode($query); ?>&play=<?php echo urlencode($track['id']); ?>"><?php echo htmlspecialchars($track['name']); ?></a>
                            </strong>
                            <p><?php echo htmlspecialchars($track['artists'][0]['name']); ?> - <?php echo htmlspecialchars($track['album']['name']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($query): ?>
            <p>Nenhuma música encontrada.</p>
        <?php endif; ?>
    </div>

    <!-- Reprodutor de música -->
    <?php if ($selected_track_id): ?>
        <iframe
            src="https://open.spotify.com/embed/track/<?php echo htmlspecialchars($selected_track_id); ?>"
            id="spotify-player"
            frameborder="0"
            allowtransparency="true"
            allow="encrypted-media">
        </iframe>
    <?php endif; ?>

    <!-- Informações sobre o artista -->
    <?php if ($artist_info): ?>
        <div class="artist-info">
            <img src="<?php echo htmlspecialchars($artist_info['images'][0]['url']); ?>" alt="Imagem do artista">
            <div class="artist-details">
                <h2><?php echo htmlspecialchars($artist_info['name']); ?></h2>
                <p>Gênero: <?php echo htmlspecialchars(implode(', ', $artist_info['genres'])); ?></p>
                <p>Popularidade: <?php echo htmlspecialchars($artist_info['popularity']); ?></p>
                <p>Seguidores: <?php echo number_format($artist_info['followers']['total']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        window.addEventListener('load', function() {
            var loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = 'none';
            }

            // Cria um novo objeto URL para manipular a URL atual
            var url = new URL(window.location.href);
            
            // Remove os parâmetros da URL
            url.searchParams.delete('query');
            url.searchParams.delete('play');
            
            // Atualiza a URL sem parâmetros
            if (window.location.search) {
                window.history.replaceState({}, document.title, url.pathname);
            }
        });
    </script>

</body>
</html>
