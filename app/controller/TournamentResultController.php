<?php
require_once __DIR__ . '/../repository/TournamentResultRepository.php';

class TournamentResultController {
    private $resultRepository;

    public function __construct() {
        $this->resultRepository = new TournamentResultRepository();
    }

    public function saveResults($tournamentId) {
        $requestBody = file_get_contents("php://input");
        $data = json_decode($requestBody, true);

        if (!isset($data['results']) || !is_array($data['results'])) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Invalid results data."]);
            return;
        }

        $success = $this->resultRepository->saveMultiple((int)$tournamentId, $data['results']);

        if ($success) {
            http_response_code(200);
            header("Content-Type: application/json");
            echo json_encode(["success" => true, "message" => "Results saved successfully."]);
        } else {
            http_response_code(500);
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Failed to save results."]);
        }
    }

    public function getResults($tournamentId) {
        $results = $this->resultRepository->findByTournamentId((int)$tournamentId);
        
        $response = [];
        foreach ($results as $result) {
            $response[] = [
                'resultId' => $result->getResultId(),
                'tournamentId' => $result->getTournamentId(),
                'awardType' => $result->getAwardType(),
                'recipientName' => $result->getRecipientName(),
                'recipientTeam' => $result->getRecipientTeam(),
                'createdAt' => $result->getCreatedAt()
            ];
        }

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode(["success" => true, "data" => $response]);
    }
}
