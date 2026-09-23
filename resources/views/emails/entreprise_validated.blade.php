<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation du compte Entreprise GP - Rahma Delivery</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FAF7F2;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #053754;
            color: #ffffff;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 6px 0 0 0;
            color: #93c5fd;
            font-size: 14px;
        }
        .body-content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #053754;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 9999px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .text {
            color: #475569;
            font-size: 15px;
            margin-bottom: 24px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background-color: #053754;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
        }
        .link-alt {
            font-size: 12px;
            color: #94a3b8;
            word-break: break-all;
            margin-top: 16px;
            text-align: center;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rahma Delivery</h1>
            <p>Plateforme pour Entreprises GP & Transporteurs</p>
        </div>
        <div class="body-content">
            <div class="badge">Vérification de l'adresse E-mail</div>
            <div class="greeting">Bonjour {{ $gerant->prenom }} {{ $gerant->nom }},</div>
            <p class="text">
                Votre entreprise <strong>{{ $entreprise->nom }}</strong> a bien été enregistrée sur la plateforme Rahma Delivery.
            </p>
            <p class="text">
                Veuillez cliquer sur le bouton ci-dessous afin de confirmer l'adresse e-mail de votre entreprise et finaliser la vérification de votre compte :
            </p>
            
            <div class="cta-container">
                <a href="{{ $verificationUrl }}" class="btn" target="_blank">Confirmer l'E-mail de l'Entreprise</a>
            </div>

            <p class="text" style="font-size: 13px; color: #64748b;">
                Une fois cette étape validée, vous pourrez gérer vos agents GP, publier des voyages d'entreprise et suivre l'ensemble de vos opérations.
            </p>

            <div class="link-alt">
                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
                <a href="{{ $verificationUrl }}" style="color: #0284c7;">{{ $verificationUrl }}</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Rahma Delivery. Tous droits réservés.
        </div>
    </div>
</body>
</html>
