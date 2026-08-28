<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Member;
use App\Models\Plan;
use App\Models\PlanMember;
use App\Repositories\CreditScoreRepository;
use App\Repositories\MemberRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PlanMemberRepository;
use App\Repositories\PlanRepository;
use PDO;

/**
 * Manages membership lifecycle: joining a plan, approval, rejection and the
 * various listing helpers used by dashboards. Eligibility rules follow PRD
 * section 38. Financial state is never touched here; this is purely membership.
 */
final class MembershipService
{
    public function __construct(
        private PlanRepository $plans,
        private PlanMemberRepository $planMembers,
        private MemberRepository $members,
        private CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
    ) {}

    /**
     * Request to join a plan, applying PRD §38 eligibility rules.
     *
     * @return array{ok: bool, status?: string, plan_member_id?: int, error?: string}
     */
    public function requestJoin(int $memberId, int $planId): array
    {
        $member = $this->members->findById($memberId);
        if ($member === null) {
            return ['ok' => false, 'error' => 'Ahli tidak dijumpai.'];
        }

        $plan = $this->plans->findById($planId);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Pelan tidak dijumpai.'];
        }

        if (!in_array($plan->status, ['open', 'active'], true)) {
            return ['ok' => false, 'error' => 'Pelan tidak dibuka untuk penyertaan.'];
        }

        if ((int) $plan->minCreditScore > $member->creditScore) {
            return ['ok' => false, 'error' => 'Skor kredit tidak mencukupi untuk pelan ini.'];
        }

        if (!$plan->allowMultiple && $this->planMembers->allActiveForMember($memberId) !== []) {
            return ['ok' => false, 'error' => 'Anda sudah menyertai pelan ini atau pelan lain.'];
        }

        $status = $plan->approvalRequired ? 'pending' : 'active';
        $data = [
            'plan_id'   => $planId,
            'member_id' => $memberId,
            'status'    => $status,
            'joined_at' => $plan->approvalRequired ? null : date('Y-m-d H:i:s'),
        ];

        $planMemberId = $this->planMembers->create($data);

        AuditService::log('plan.join', $memberId, 'plan_member', $planMemberId, [
            'plan_id' => $planId,
            'status'  => $status,
        ]);

        if ($status === 'active') {
            $this->notifyMember($member, 'plan.joined', 'Penyertaan diluluskan', 'Anda telah berjaya menyertai pelan ' . $plan->name . '.');
        }

        return ['ok' => true, 'status' => $status, 'plan_member_id' => $planMemberId];
    }

    /**
     * Approve a pending membership.
     *
     * @return array{ok: bool, error?: string}
     */
    public function approve(int $planMemberId, int $actorId): array
    {
        $planMember = $this->planMembers->findById($planMemberId);
        if ($planMember === null) {
            return ['ok' => false, 'error' => 'Permintaan penyertaan tidak dijumpai.'];
        }

        $this->planMembers->update($planMemberId, [
            'status'     => 'active',
            'joined_at'  => date('Y-m-d H:i:s'),
            'approved_by' => $actorId,
        ]);

        AuditService::log('plan.join.approve', $actorId, 'plan_member', $planMemberId, [
            'plan_id' => $planMember->planId,
        ]);

        $member = $this->members->findById($planMember->memberId);
        if ($member !== null) {
            $plan = $this->plans->findById($planMember->planId);
            $planName = $plan !== null ? $plan->name : (string) $planMember->planId;
            $this->notifyMember($member, 'plan.joined', 'Penyertaan diluluskan', 'Permintaan menyertai pelan ' . $planName . ' telah diluluskan.');
        }

        return ['ok' => true];
    }

    /**
     * Reject a pending membership.
     *
     * @return array{ok: bool, error?: string}
     */
    public function reject(int $planMemberId, int $actorId): array
    {
        $planMember = $this->planMembers->findById($planMemberId);
        if ($planMember === null) {
            return ['ok' => false, 'error' => 'Permintaan penyertaan tidak dijumpai.'];
        }

        $this->planMembers->update($planMemberId, ['status' => 'rejected']);

        AuditService::log('plan.join.reject', $actorId, 'plan_member', $planMemberId, [
            'plan_id' => $planMember->planId,
        ]);

        $member = $this->members->findById($planMember->memberId);
        if ($member !== null) {
            $plan = $this->plans->findById($planMember->planId);
            $planName = $plan !== null ? $plan->name : (string) $planMember->planId;
            $this->notifyMember($member, 'plan.rejected', 'Penyertaan ditolak', 'Permintaan menyertai pelan ' . $planName . ' telah ditolak.');
        }

        return ['ok' => true];
    }

    /**
     * @return PlanMember[]
     */
    public function listForMember(int $memberId): array
    {
        return $this->planMembers->allForMember($memberId);
    }

    /**
     * @return PlanMember[]
     */
    public function listForPlan(int $planId, ?string $status = null): array
    {
        return $this->planMembers->allForPlan($planId, $status);
    }

    public function find(int $planMemberId): ?PlanMember
    {
        return $this->planMembers->findById($planMemberId);
    }

    private function notifyMember(Member $member, string $type, string $title, string $message): void
    {
        $this->notifications->create([
            'recipient_id'   => $member->userId,
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'reference_type' => 'member',
            'reference_id'   => $member->id,
            'channel'        => 'in_app',
            'is_read'        => false,
        ]);
    }
}
