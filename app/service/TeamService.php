<?php

require_once __DIR__ . "/../repository/TeamRepository.php";

class TeamService
{
    private TeamRepository $teamRepository;

    public function __construct()
    {
        $this->teamRepository = new TeamRepository();
    }

    public function getTeamRankings(): array
    {
        try {
            $rawTeams = $this->teamRepository->getTeamRankings();
            
            $formattedTeams = [];
            $trends = ['up', 'same', 'down'];

            foreach ($rawTeams as $index => $row) {
                $played = (int) ($row['played'] ?? 0);
                $won = (int) ($row['won'] ?? 0);
                $points = (int) ($row['points'] ?? 0);
                $rating = (float) ($row['rating'] ?? 0.0);
                $winRate = (float) ($row['win_rate'] ?? 0.0);
                $rank = $played > 0 ? (int)($row['rank_position'] ?? ($index + 1)) : ($index + 1);

                $trend = ($played > 0 || $won > 0) ? $trends[($row['user_id'] ?? $index) % 3] : 'same';

                $formattedTeams[] = [
                    'id' => (int) $row['user_id'],
                    'rank' => $rank,
                    'name' => $row['team_name'] ?: 'Team #' . $row['user_id'],
                    'district' => $row['district'] ?: 'General',
                    'contactNumber' => $row['contact_number'] ?? '',
                    'played' => $played,
                    'won' => $won,
                    'points' => $points,
                    'rating' => $rating,
                    'win_rate' => $winRate,
                    'trend' => $trend,
                    'profilePicture' => $row['profile_picture'] ?? null
                ];
            }

            return [
                "success" => true,
                "data" => $formattedTeams
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Failed to retrieve rankings: " . $e->getMessage()
            ];
        }
    }

    public function getTeamStats(int $userId): array
    {
        try {
            require_once __DIR__ . "/TournamentService.php";
            $tService = new TournamentService();
            $tService->recalculateAllTeamRatings();

            $stats = $this->teamRepository->getTeamStats($userId);
            return [
                "success" => true,
                "data" => $stats
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Failed to retrieve team stats: " . $e->getMessage()
            ];
        }
    }
}

