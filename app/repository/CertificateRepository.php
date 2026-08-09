<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../model/Certificate.php';

class CertificateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(Certificate $certificate): bool
    {
        $query = "INSERT INTO certificates (verification_token, tournament_id, recipient_name, player_id, certificate_type, issue_date, created_at)
                  VALUES (:verification_token, :tournament_id, :recipient_name, :player_id, :certificate_type, :issue_date, :created_at)";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            ':verification_token' => $certificate->verificationToken,
            ':tournament_id' => $certificate->tournamentId,
            ':recipient_name' => $certificate->recipientName,
            ':player_id' => $certificate->playerId ?? 0,
            ':certificate_type' => $certificate->certificateType,
            ':issue_date' => $certificate->issueDate,
            ':created_at' => $certificate->createdAt
        ]);
    }

    public function findByToken(string $token): ?array
    {
        $query = "SELECT c.certificate_id, c.verification_token, c.tournament_id, c.recipient_name, c.player_id, 
                         c.certificate_type, c.issue_date, c.created_at,
                         t.title AS tournament_title, t.location AS tournament_location, 
                         t.start_date, t.end_date, t.tournament_held_date,
                         COALESCE(org_user.email, 'Elle Hub Official') AS organizer_name
                  FROM certificates c
                  JOIN tournaments t ON c.tournament_id = t.tournament_id
                  LEFT JOIN users org_user ON t.organizer_id = org_user.user_id
                  WHERE c.verification_token = :token OR c.certificate_id = :token_id";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':token' => $token,
            ':token_id' => is_numeric($token) ? (int)$token : -1
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    public function findByTournamentId(int $tournamentId): array
    {
        $query = "SELECT c.*, t.title AS tournament_title 
                  FROM certificates c 
                  JOIN tournaments t ON c.tournament_id = t.tournament_id
                  WHERE c.tournament_id = :tournament_id 
                  ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':tournament_id' => $tournamentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
