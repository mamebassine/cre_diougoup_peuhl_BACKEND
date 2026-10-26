<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre }}</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f3f5f9;
    font-family: Arial, Helvetica, sans-serif;
    color: #333333;
">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0"
       style="background-color: #f3f5f9; padding: 30px 10px;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                   style="
                       width: 100%;
                       max-width: 600px;
                       background-color: #ffffff;
                       border-radius: 12px;
                       overflow: hidden;
                       border: 1px solid #e5e9f0;
                   ">

                <!-- En-tête -->
                <tr>
                    <td align="center"
                        style="
                            background-color: #3B5998;
                            padding: 30px 20px;
                            border-bottom: 5px solid #2E7D32;
                        ">

                        <h1 style="
                            margin: 0;
                            color: #ffffff;
                            font-size: 21px;
                            line-height: 1.5;
                            font-weight: bold;
                        ">
                            CENTRE DE RECHERCHE ET D’ESSAI
                        </h1>

                        <p style="
                            margin: 5px 0 0;
                            color: #ffffff;
                            font-size: 17px;
                            font-weight: bold;
                        ">
                            DE DIOUGOUP PEULH
                        </p>

                        <p style="
                            margin: 12px 0 0;
                            color: #eaf0ff;
                            font-size: 12px;
                            letter-spacing: 1px;
                        ">
                            FORMATION · INNOVATION · RÉUSSITE
                        </p>
                    </td>
                </tr>

                <!-- Contenu -->
                <tr>
                    <td style="padding: 32px 28px;">

                        <p style="
                            margin: 0 0 20px;
                            font-size: 16px;
                            color: #333333;
                        ">
                            Bonjour <strong>{{ $nomComplet }}</strong>,
                        </p>

                        <h2 style="
                            margin: 0 0 18px;
                            font-size: 21px;
                            line-height: 1.4;
                            color: #3B5998;
                        ">
                            {{ $titre }}
                        </h2>

                        <!-- Message principal -->
                        <p style="
                            margin: 0 0 22px;
                            font-size: 15px;
                            line-height: 1.8;
                            color: #4b5563;
                        ">
                            {{ $messagePrincipal }}
                        </p>

                        <!-- Informations sur la formation -->
                        @if($type === 'inscription' && $nomFormation)
                            <table role="presentation" width="100%"
                                   cellspacing="0" cellpadding="0"
                                   style="
                                       margin: 20px 0;
                                       background-color: #f0f4ff;
                                       border-left: 4px solid #3B5998;
                                       border-radius: 6px;
                                   ">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <p style="
                                            margin: 0 0 6px;
                                            font-size: 12px;
                                            color: #667085;
                                            text-transform: uppercase;
                                            letter-spacing: 0.5px;
                                        ">
                                            Formation concernée
                                        </p>

                                        <p style="
                                            margin: 0;
                                            color: #3B5998;
                                            font-size: 16px;
                                            font-weight: bold;
                                            line-height: 1.5;
                                        ">
                                            {{ $nomFormation }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <!-- Matricule -->
                        @if($matricule)
                            <table role="presentation" width="100%"
                                   cellspacing="0" cellpadding="0"
                                   style="
                                       margin: 20px 0;
                                       background-color: #edf7ef;
                                       border: 1px solid #cce8d1;
                                       border-radius: 6px;
                                   ">
                                <tr>
                                    <td align="center" style="padding: 18px;">
                                        <p style="
                                            margin: 0 0 8px;
                                            color: #387348;
                                            font-size: 12px;
                                            text-transform: uppercase;
                                            letter-spacing: 1px;
                                        ">
                                            Votre matricule apprenant
                                        </p>

                                        <p style="
                                            margin: 0;
                                            color: #2E7D32;
                                            font-size: 22px;
                                            font-weight: bold;
                                            letter-spacing: 1px;
                                        ">
                                            {{ $matricule }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <!-- Message d'accueil après acceptation -->
                        @if(!empty($messageAccueil))
                            @foreach($messageAccueil as $ligne)
                                <p style="
                                    margin: 18px 0;
                                    font-size: 15px;
                                    line-height: 1.8;
                                    color: #4b5563;
                                    {{ $loop->last ? 'font-weight: bold; color: #2E7D32;' : '' }}
                                ">
                                    {{ $ligne }}
                                </p>
                            @endforeach
                        @endif

                        <p style="
                            margin: 30px 0 0;
                            color: #4b5563;
                            font-size: 14px;
                            line-height: 1.7;
                        ">
                            Cordialement,<br>
                            <strong style="color: #3B5998;">
                                L’équipe du CRE de Diougoup Peulh
                            </strong>
                        </p>

                    </td>
                </tr>

                <!-- Pied de page -->
                <tr>
                    <td style="
                        background-color: #f8fafc;
                        padding: 25px 24px;
                        border-top: 1px solid #e5e9f0;
                    ">

                        <h3 style="
                            margin: 0 0 14px;
                            color: #3B5998;
                            font-size: 15px;
                            text-align: center;
                        ">
                            Nos coordonnées
                        </h3>

                        <p style="
                            margin: 8px 0;
                            text-align: center;
                            color: #555555;
                            font-size: 13px;
                            line-height: 1.7;
                        ">
                            <strong style="color: #2E7D32;">Adresse :</strong>
                            {{ $adresseCentre }}
                        </p>

                        <p style="
                            margin: 8px 0;
                            text-align: center;
                            color: #555555;
                            font-size: 13px;
                            line-height: 1.7;
                        ">
                            <strong style="color: #2E7D32;">Téléphone :</strong>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $telephoneCentre) }}"
                               style="color: #3B5998; text-decoration: none;">
                                {{ $telephoneCentre }}
                            </a>
                        </p>

                        <p style="
                            margin: 8px 0;
                            text-align: center;
                            color: #555555;
                            font-size: 13px;
                            line-height: 1.7;
                        ">
                            <strong style="color: #2E7D32;">E-mail :</strong>
                            <a href="mailto:{{ $emailCentre }}"
                               style="color: #3B5998; text-decoration: none;">
                                {{ $emailCentre }}
                            </a>
                        </p>

                        <div style="
                            height: 1px;
                            background-color: #e0e5ec;
                            margin: 20px 0 15px;
                        "></div>

                        <p style="
                            margin: 0;
                            text-align: center;
                            font-size: 11px;
                            line-height: 1.6;
                            color: #8a94a3;
                        ">
                            Ce message vous est adressé dans le cadre de vos
                            échanges avec le CRE de Diougoup Peulh.
                        </p>

                        <p style="
                            margin: 8px 0 0;
                            text-align: center;
                            font-size: 11px;
                            color: #8a94a3;
                        ">
                            © {{ date('Y') }} CRE de Diougoup Peulh.
                            Tous droits réservés.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>