<?php

require_once __DIR__ . '/../app/controller/CertificateController.php';

$router->post(
    '/tournament/{id}/certificates/generate',
    [CertificateController::class, 'generate']
);

$router->get(
    '/tournament/{id}/certificates',
    [CertificateController::class, 'getTournamentCertificates']
);

$router->get(
    '/api/certificates/verify/{token}',
    [CertificateController::class, 'verify']
);

$router->get(
    '/certificate/verify/{token}',
    [CertificateController::class, 'verify']
);
