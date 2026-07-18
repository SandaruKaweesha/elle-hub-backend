<?php
require_once __DIR__ . '/../service/CertificateService.php';
require_once __DIR__ . '/../core/AuthMiddleware.php';

class CertificateController
{
    private CertificateService $service;

    public function __construct()
    {
        $this->service = new CertificateService();
    }

    public function generate()
    {
        $payload = AuthMiddleware::requireRole(['organizer']);
        $organizerId = $payload['user_id'] ?? $payload['id']; // handle potential differences in JWT payload

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['tournament']) || empty($data['cert_type']) || empty($data['recipient'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $certificate = $this->service->generateCertificate($data, $organizerId);

        if ($certificate) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Certificate generated securely',
                'data' => [
                    'id' => $certificate->id,
                    'verify_link' => '/verify/' . $certificate->id
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to generate certificate']);
        }
    }

    public function history()
    {
        $payload = AuthMiddleware::requireRole(['organizer']);
        $organizerId = $payload['user_id'] ?? $payload['id'];

        $history = $this->service->getCertificateHistory($organizerId);

        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    }

    public function verify($id)
    {
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Certificate ID is required']);
            return;
        }

        $certData = $this->service->verifyCertificate($id);

        if ($certData) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'data' => $certData
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid or fake certificate'
            ]);
        }
    }
}
