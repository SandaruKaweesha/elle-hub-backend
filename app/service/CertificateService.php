<?php
require_once __DIR__ . '/../repository/CertificateRepository.php';
require_once __DIR__ . '/../model/Certificate.php';

class CertificateService
{
    private CertificateRepository $repository;

    public function __construct()
    {
        $this->repository = new CertificateRepository();
    }

    public function generateTournamentCertificates(int $tournamentId): array
    {
        try {
            $conn = Database::getConnection();

            // Fetch tournament
            $tStmt = $conn->prepare("SELECT tournament_id, title, location, start_date, tournament_held_date, status, draw_data FROM tournaments WHERE tournament_id = ?");
            $tStmt->execute([$tournamentId]);
            $t = $tStmt->fetch(PDO::FETCH_ASSOC);

            if (!$t) {
                return ["success" => false, "message" => "Tournament not found"];
            }

            $issueDate = !empty($t['tournament_held_date']) ? $t['tournament_held_date'] : (!empty($t['start_date']) ? $t['start_date'] : date('Y-m-d'));
            $drawData = !empty($t['draw_data']) ? json_decode($t['draw_data'], true) : [];
            $winnerName = $drawData['winner'] ?? $drawData['bracketWinners']['champion'] ?? null;

            // Fetch participating teams
            $teamStmt = $conn->prepare("
                SELECT u.user_id, u.email, COALESCE(tm.team_name, u.email) as team_name
                FROM tournament_team_requests ttr
                JOIN users u ON ttr.team_user_id = u.user_id
                LEFT JOIN teams tm ON u.user_id = tm.user_id
                WHERE ttr.tournament_id = ? AND ttr.status IN ('APPROVED', 'ACCEPTED')
            ");
            $teamStmt->execute([$tournamentId]);
            $participatingTeams = $teamStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($participatingTeams)) {
                return ["success" => false, "message" => "No approved participating teams found for certificate generation."];
            }

            $generatedCount = 0;
            foreach ($participatingTeams as $index => $team) {
                $teamName = $team['team_name'];
                $teamUserId = (int) $team['user_id'];

                $certType = 'PARTICIPATION';
                if ($winnerName && (strcasecmp(trim($winnerName), trim($teamName)) === 0)) {
                    $certType = 'CHAMPION';
                } else if ($index === 1 && $winnerName) {
                    $certType = 'RUNNER_UP';
                }

                $token = "CERT-ELE-" . date('Y') . "-" . strtoupper(substr(md5(uniqid($tournamentId . $teamUserId, true)), 0, 8));

                $cert = new Certificate(
                    null,
                    $token,
                    $tournamentId,
                    $teamName,
                    $teamUserId,
                    $certType,
                    $issueDate
                );

                if ($this->repository->save($cert)) {
                    $generatedCount++;
                }
            }

            return [
                "success" => true,
                "message" => "Successfully generated {$generatedCount} E-Certificates with QR verification tokens!",
                "generatedCount" => $generatedCount
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error generating certificates: " . $e->getMessage()];
        }
    }

    public function getCertificatesByTournament(int $tournamentId): array
    {
        try {
            $certs = $this->repository->findByTournamentId($tournamentId);
            return [
                "success" => true,
                "data" => $certs
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function verifyCertificateByToken(string $token): array
    {
        try {
            $certData = $this->repository->findByToken($token);

            if ($certData) {
                return [
                    "success" => true,
                    "valid" => true,
                    "verified_by" => "Elle Hub Official E-Certificate Verification System",
                    "badge" => "Verified by Elle Hub",
                    "data" => [
                        "certificate_id" => $certData['certificate_id'],
                        "verification_token" => $certData['verification_token'],
                        "recipient_name" => $certData['recipient_name'],
                        "tournament_title" => $certData['tournament_title'],
                        "tournament_location" => $certData['tournament_location'] ?? 'Sri Lanka',
                        "certificate_type" => $certData['certificate_type'],
                        "issue_date" => $certData['issue_date'],
                        "created_at" => $certData['created_at'],
                        "organizer_name" => $certData['organizer_name'] ?? 'Elle Hub Official'
                    ]
                ];
            }

            return [
                "success" => false,
                "valid" => false,
                "message" => "Invalid, tampered, or non-existent certificate token."
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "valid" => false,
                "message" => "Error verifying certificate: " . $e->getMessage()
            ];
        }
    }
}
