
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>API Absensi</title>
        <meta name="description" content="REST API untuk sistem absensi mahasiswa">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Press Start 2P', monospace;
                background-color: #5c94fc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .container {
                text-align: center;
                padding: 24px;
            }

            h1 {
                font-size: 2.5rem;
                color: #ffffff;
                line-height: 1.8;
                text-shadow: 4px 4px 0px #1a3d8f;
            }

            @media (max-width: 640px) {
                h1 {
                    font-size: 1.4rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Selamat Datang<br>di API Absensi</h1>
        </div>
    </body>
</html>
