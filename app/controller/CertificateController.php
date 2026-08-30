<?php
require_once __DIR__ . '/../service/CertificateService.php';
require_once __DIR__ . '/../core/AuthMiddleware.php';

class CertificateController
{
    private const JSON_HEADER = "Content-Type: application/json";
    private CertificateService $service;

    public function __construct()
    {
        $this->service = new CertificateService();
    }

    public function generate($tournamentId = null)
    {
        header(self::JSON_HEADER);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!empty($input['recipient']) || !empty($input['recipient_name'])) {
            if ($tournamentId && empty($input['tournamentId']) && empty($input['selectedTournamentId'])) {
                $input['tournamentId'] = (int)$tournamentId;
            }
            $result = $this->service->generateSingleCertificate($input);
        } else if ($tournamentId) {
            $result = $this->service->generateTournamentCertificates((int)$tournamentId);
        } else {
            $result = ["success" => false, "message" => "Invalid tournament ID or certificate payload."];
        }

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        echo json_encode($result);
    }

    public function getTournamentCertificates($tournamentId)
    {
        header(self::JSON_HEADER);
        $result = $this->service->getCertificatesByTournament((int)$tournamentId);
        echo json_encode($result);
    }

    public function verify($token)
    {
        header(self::JSON_HEADER);
        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'valid' => false, 'message' => 'Verification token is required']);
            return;
        }

        $result = $this->service->verifyCertificateByToken($token);

        if ($result['valid']) {
            http_response_code(200);
        } else {
            http_response_code(404);
        }

        echo json_encode($result);
    }
}
