<?php

require_once __DIR__ . '/../app/controller/CertificateController.php';

$router->post(
    '/api/certificates',
    [CertificateController::class, 'generate']
);

$router->get(
    '/api/certificates/history',
    [CertificateController::class, 'history']
);

$router->get(
    '/api/certificates/verify/{id}',
    [CertificateController::class, 'verify']
);
