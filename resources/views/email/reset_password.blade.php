<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333333;
        }

        p {
            font-size: 16px;
            color: #555555;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .notice {
            font-size: 14px;
            color: #ff0000;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Reset Password</h1>
        <p>Anda telah meminta untuk mereset kata sandi Anda. Silakan klik tombol di bawah ini untuk melanjutkan proses
            reset kata sandi.</p>
        <a href="{{ url('api/password/reset/confirm?reset_token=' . $reset_token) }}" class="btn">Reset Kata Sandi</a>
        <p class="notice">Harap diperhatikan, tautan ini hanya berlaku selama 5 menit.</p>
        <p>Jika Anda tidak meminta reset kata sandi, harap abaikan email ini. </p>
    </div>
</body>

</html>
