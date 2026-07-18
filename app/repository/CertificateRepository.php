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
        $query = "INSERT INTO certificates (id, tournament, cert_type, recipient, organizer_id, created_at)
                  VALUES (:id, :tournament, :cert_type, :recipient, :organizer_id, :created_at)";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            ':id' => $certificate->id,
            ':tournament' => $certificate->tournament,
            ':cert_type' => $certificate->certType,
            ':recipient' => $certificate->recipient,
            ':organizer_id' => $certificate->organizerId,
            ':created_at' => $certificate->createdAt
        ]);
    }

    public function findById(string $id): ?Certificate
    {
        $query = "SELECT c.*, u.name as organizer_name 
                  FROM certificates c
                  JOIN users u ON c.organizer_id = u.id 
                  WHERE c.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        if ($result) {
            $cert = Certificate::fromArray($result);
            // Optionally, we can dynamically add the organizer_name to the object if needed, 
            // but for simplicity, we'll return an associative array along with the certificate model in the service.
            $cert->organizerName = $result['organizer_name'];
            return $cert;
        }

        return null;
    }

    public function findByOrganizerId(int $organizerId): array
    {
        $query = "SELECT * FROM certificates WHERE organizer_id = :organizer_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':organizer_id' => $organizerId]);

        $certificates = [];
        while ($row = $stmt->fetch()) {
            $certificates[] = Certificate::fromArray($row);
        }

        return $certificates;
    }
}
