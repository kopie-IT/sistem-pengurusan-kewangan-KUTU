<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\CreditScore;
use App\Models\CreditScoreHistory;
use PDO;

/**
 * Data access for credit scores, score history and scoring rules.
 */
final class CreditScoreRepository
{
    public function findByMember(int $memberId): ?CreditScore
    {
        $sql = 'SELECT * FROM credit_scores WHERE member_id = :member_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function upsert(int $memberId, int $score, string $level): void
    {
        $sql = 'INSERT INTO credit_scores (member_id, score, level)
                VALUES (:member_id, :score, :level)
                ON DUPLICATE KEY UPDATE score = :score_u, level = :level_u, updated_at = NOW()';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':member_id' => $memberId,
            ':score'     => $score,
            ':level'     => $level,
            ':score_u'   => $score,
            ':level_u'   => $level,
        ]);
    }

    /**
     * @return CreditScoreHistory[]
     */
    public function historyForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM credit_score_history WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrateHistory'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function addHistory(array $data): int
    {
        $sql = 'INSERT INTO credit_score_history (
                    member_id, event, reason_code, previous_score, score_change, new_score,
                    related_plan_id, related_payment_id, related_payout_id, actor_id
                ) VALUES (
                    :member_id, :event, :reason_code, :previous_score, :score_change, :new_score,
                    :related_plan_id, :related_payment_id, :related_payout_id, :actor_id
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':member_id'         => $data['member_id'],
            ':event'             => $data['event'],
            ':reason_code'       => $data['reason_code'],
            ':previous_score'    => $data['previous_score'] ?? 0,
            ':score_change'      => $data['score_change'] ?? 0,
            ':new_score'         => $data['new_score'] ?? 0,
            ':related_plan_id'   => $data['related_plan_id'] ?? null,
            ':related_payment_id' => $data['related_payment_id'] ?? null,
            ':related_payout_id'  => $data['related_payout_id'] ?? null,
            ':actor_id'          => $data['actor_id'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array[]
     */
    public function rulesAll(): array
    {
        $sql = 'SELECT * FROM credit_score_rules ORDER BY id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ruleByCode(string $code): ?array
    {
        $sql = 'SELECT * FROM credit_score_rules WHERE reason_code = :code LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function seedDefaultRules(): void
    {
        $sql = 'SELECT COUNT(*) AS total FROM credit_score_rules';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $defaults = [
            ['reason_code' => 'PAYMENT_ON_TIME', 'description' => 'Contribution paid on or before due date', 'score_change' => 2, 'is_recovery' => 0],
            ['reason_code' => 'PAYMENT_LATE', 'description' => 'Contribution paid after due date', 'score_change' => -5, 'is_recovery' => 0],
            ['reason_code' => 'PAYMENT_MISSED', 'description' => 'Contribution missed (overdue)', 'score_change' => -10, 'is_recovery' => 0],
            ['reason_code' => 'PAYMENT_RECOVERY', 'description' => 'Caught up after missed payment', 'score_change' => 5, 'is_recovery' => 1],
            ['reason_code' => 'WITHDRAWAL_APPROVED', 'description' => 'Approved early withdrawal', 'score_change' => -3, 'is_recovery' => 0],
            ['reason_code' => 'PLAN_COMPLETED', 'description' => 'Successfully completed a plan cycle', 'score_change' => 5, 'is_recovery' => 1],
        ];

        $sql = 'INSERT INTO credit_score_rules (reason_code, description, score_change, is_recovery, status)
                VALUES (:reason_code, :description, :score_change, :is_recovery, :status)';
        $stmt = Database::connection()->prepare($sql);
        foreach ($defaults as $rule) {
            $stmt->execute([
                ':reason_code' => $rule['reason_code'],
                ':description' => $rule['description'],
                ':score_change' => $rule['score_change'],
                ':is_recovery' => $rule['is_recovery'],
                ':status'      => 'active',
            ]);
        }
    }

    private function hydrate(array $row): CreditScore
    {
        return CreditScore::fromRow($row);
    }

    private function hydrateHistory(array $row): CreditScoreHistory
    {
        return CreditScoreHistory::fromRow($row);
    }
}
