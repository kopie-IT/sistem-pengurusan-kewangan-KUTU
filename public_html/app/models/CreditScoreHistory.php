<?php

declare(strict_types=1);

namespace App\Models;

final class CreditScoreHistory
{
    public function __construct(
        public int $id,
        public int $memberId,
        public string $event,
        public string $reasonCode,
        public int $previousScore,
        public int $scoreChange,
        public int $newScore,
        public ?int $relatedPlanId,
        public ?int $relatedPaymentId,
        public ?int $relatedPayoutId,
        public ?int $actorId,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            event: $row['event'],
            reasonCode: $row['reason_code'],
            previousScore: (int) $row['previous_score'],
            scoreChange: (int) $row['score_change'],
            newScore: (int) $row['new_score'],
            relatedPlanId: isset($row['related_plan_id']) ? (int) $row['related_plan_id'] : null,
            relatedPaymentId: isset($row['related_payment_id']) ? (int) $row['related_payment_id'] : null,
            relatedPayoutId: isset($row['related_payout_id']) ? (int) $row['related_payout_id'] : null,
            actorId: isset($row['actor_id']) ? (int) $row['actor_id'] : null,
            createdAt: $row['created_at'],
        );
    }
}
