<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CreditScore;
use App\Repositories\CreditScoreRepository;
use App\Repositories\MemberRepository;

/**
 * Applies credit-score events and computes a member's risk level.
 *
 * Scoring rules (PRD section 37) are stored in `credit_score_rules` and
 * referenced by reason_code. Every change is clamped to the [0, 100] range and
 * the running score is upserted, plus a history row is appended.
 */
final class CreditScoreService
{
    private const MIN_SCORE = 0;
    private const MAX_SCORE = 100;

    public function __construct(
        private CreditScoreRepository $scores,
        private MemberRepository $members,
    ) {}

    /**
     * Apply a scoring event by reason code to a member.
     *
     * @param array<string, mixed> $context
     * @return array{ok: bool, new_score?: int, error?: string}
     */
    public function applyEvent(int $memberId, string $reasonCode, array $context = []): array
    {
        $rule = $this->scores->ruleByCode($reasonCode);
        if ($rule === null) {
            return ['ok' => false, 'error' => 'Kod acara skor kredit tidak dikenali.'];
        }

        $scoreChange = (int) $rule['score_change'];
        $previous = $this->scores->findByMember($memberId);
        $previousScore = $previous !== null ? $previous->score : 100;

        $newScore = $this->clamp($previousScore + $scoreChange);
        $level = $this->levelFor($newScore);

        $this->scores->upsert($memberId, $newScore, $level);
        $this->scores->addHistory([
            'member_id'         => $memberId,
            'event'             => $rule['description'] ?? $reasonCode,
            'reason_code'       => $reasonCode,
            'previous_score'    => $previousScore,
            'score_change'      => $scoreChange,
            'new_score'         => $newScore,
            'related_plan_id'   => $context['related_plan_id'] ?? null,
            'related_payment_id' => $context['related_payment_id'] ?? null,
            'related_payout_id'  => $context['related_payout_id'] ?? null,
            'actor_id'          => $context['actor_id'] ?? null,
        ]);

        return ['ok' => true, 'new_score' => $newScore];
    }

    /**
     * Map a numeric score to a risk level (PRD section 37).
     */
    public static function levelFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 80 => 'good',
            $score >= 70 => 'fair',
            $score >= 60 => 'risk',
            default      => 'high_risk',
        };
    }

    /**
     * Return the current score row for a member, or a default 100 record.
     *
     * @return array{score: int, level: string}|null
     */
    public function getScore(int $memberId): ?array
    {
        $row = $this->scores->findByMember($memberId);
        if ($row === null) {
            return ['score' => 100, 'level' => $this->levelFor(100)];
        }
        return ['score' => $row->score, 'level' => $row->level];
    }

    /**
     * @return \App\Models\CreditScoreHistory[]
     */
    public function getHistory(int $memberId): array
    {
        return $this->scores->historyForMember($memberId);
    }

    /**
     * List all members' current credit scores (for the admin list page).
     *
     * @return array<int, array{member_id: int, name: string, email: string, score: int, level: string}>
     */
    public function listAll(): array
    {
        return $this->scores->all();
    }

    private function clamp(int $score): int
    {
        if ($score < self::MIN_SCORE) {
            return self::MIN_SCORE;
        }
        if ($score > self::MAX_SCORE) {
            return self::MAX_SCORE;
        }
        return $score;
    }
}
