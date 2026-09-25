<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Agent GP</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
        <tr>
            <td style="background: linear-gradient(135deg, #053754 0%, #074C72 100%); padding: 35px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 900; letter-spacing: -0.5px;">Rahma GP</h1>
                <span style="display: inline-block; margin-top: 10px; background-color: rgba(255,255,255,0.15); color: #7dd3fc; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; tracking: 1px;">
                    Invitation Agent GP
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 35px 30px; color: #334155; font-size: 15px; line-height: 1.6;">
                <h2 style="font-size: 18px; font-weight: 800; color: #053754; margin-top: 0;">Bonjour,</h2>
                <p>
                    L'entreprise <strong style="color: #053754;">{{ $nomEntreprise }}</strong> vous invite à rejoindre son réseau en tant qu'<strong>Agent GP</strong> sur la plateforme de livraison Rahma GP.
                </p>
                <p>
                    En acceptant cette invitation, vous pourrez gérer l'acheminement des colis, recevoir vos feuilles de route et effectuer des trajets pour le compte de l'entreprise.
                </p>
                
                <div style="text-align: center; margin: 35px 0;">
                    <a href="{{ $lienRegister }}" style="background-color: #053754; color: #ffffff; text-decoration: none; padding: 14px 32px; font-size: 14px; font-weight: 800; border-radius: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(5,55,84,0.3);">
                        🚀 Activer mon compte Agent GP →
                    </a>
                </div>

                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px; margin-top: 25px;">
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 8px 0; font-weight: bold;">Si le bouton ne s'ouvre pas, copiez ce lien dans votre navigateur :</p>
                    <a href="{{ $lienRegister }}" style="font-size: 12px; color: #0284c7; word-break: break-all; font-weight: 600;">{{ $lienRegister }}</a>
                </div>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 12px;">
                &copy; {{ date('Y') }} Rahma Delivery. Tous droits réservés.
            </td>
        </tr>
    </table>
</body>
</html>
