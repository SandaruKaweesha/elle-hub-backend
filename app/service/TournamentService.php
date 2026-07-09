<?php
require_once __DIR__ . "/../model/Tournament.php";
require_once __DIR__ . "/../repository/TournamentRepository.php";
require_once __DIR__ . "/../../config/Database.php";

class TournamentService{
    private $tournamentRepository;
    public function __construct(){
        $this->tournamentRepository = new TournamentRepository();
    }

    /**
     * Create a tournament. Sets default status to PENDING and saves.
     */
    public function createTournament(Tournament $tournament): array{
        // Default status: PENDING (requires admin approval)
        $tournament->setStatus("PENDING");

        try{
            Database::beginTransaction();

            $tournamentId = $this->tournamentRepository->save($tournament);
            $tournament->setTournamentId($tournamentId);

            Database::commit();

            return [
                "success" => true,
                "message" => "Tournament created successfully.",
                "data" => ["tournamentId" => $tournamentId]
            ];
        } catch (Exception $e) {
            Database::rollback();
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve tournaments that match status = PENDING
     */
    public function getPendingTournaments(): array
    {
        $rows = $this->tournamentRepository->findByStatus("PENDING");

        if (empty($rows)) {
            return [
                "success" => true,
                "message" => "No pending tournaments found.",
                "data" => []
            ];
        }

        return [
            "success" => true,
            "message" => "Pending tournaments retrieved successfully.",
            "data" => $rows
        ];
    }

//    Cancel the Tournament
    public function cancelTournament(int $tournamentId): array
    {
        $cancelled = $this->tournamentRepository
            ->updateStatus($tournamentId, "CANCELLED");

        if (!$cancelled) {
            return [
                "success" => false,
                "message" => "Tournament not found or could not be cancelled."
            ];
        }

        return [
            "success" => true,
            "message" => "Tournament cancelled successfully."
        ];
    }
}

