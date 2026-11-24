<?php

return [
    'forbidden' => [
        'title' => 'Accès restreint',
        'message' => 'Votre compte ne permet pas d’ouvrir cette page ou de consulter ces informations.',
    ],
    'not_found' => [
        'title' => 'Page ou informations indisponibles',
        'message' => 'La page a peut-être été déplacée, ou les informations demandées ne sont pas accessibles avec votre compte.',
    ],
    'certificate_pdf' => [
        'title' => 'PDF du certificat indisponible',
        'message' => 'Le certificat figure dans le registre, mais son fichier PDF ne peut pas être ouvert.',
    ],
    'page_expired' => [
        'title' => 'Page expirée',
        'message' => 'La page est restée ouverte longtemps ou votre connexion a changé : la demande n’a pas pu aboutir. Revenez en arrière, actualisez la page et réessayez.',
        'reload_prompt' => 'Actualiser la page ? Les saisies non enregistrées seront perdues.',
    ],
    'too_many_requests' => [
        'title' => 'Trop de requêtes',
        'message' => 'Trop de requêtes ont été envoyées en peu de temps. Patientez un instant puis réessayez.',
    ],
    'server_error' => [
        'title' => 'Une erreur s’est produite',
        'message' => 'Un problème est survenu lors du traitement de votre demande. S’il persiste, contactez le service concerné en indiquant le numéro de référence ci-dessous.',
    ],
    'unavailable' => [
        'title' => 'Temporairement indisponible',
        'message' => 'Le service est en maintenance ou temporairement indisponible. Réessayez dans quelques instants.',
    ],
    'code' => 'Erreur :code',
    'reference' => 'Référence',
];
