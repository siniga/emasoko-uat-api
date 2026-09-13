<?php

namespace Tests\Unit;

use App\Models\PartnerAccount;
use PHPUnit\Framework\TestCase;

class PartnerAccountOnboardingStatusTest extends TestCase
{
    public function test_only_unapproved_non_operational_account_can_be_deleted(): void
    {
        $pending = new PartnerAccount([
            'is_verified' => 0,
            'onboarding_status' => PartnerAccount::ONBOARDING_PENDING_REVIEW,
        ]);
        $activeNmb = new PartnerAccount([
            'is_verified' => 1,
            'onboarding_status' => PartnerAccount::ONBOARDING_ACTIVE,
        ]);
        $approvedCrdb = new PartnerAccount([
            'is_verified' => 1,
            'onboarding_status' => PartnerAccount::ONBOARDING_NOT_REQUIRED,
        ]);

        $this->assertTrue($pending->canBeDeleted());
        $this->assertFalse($activeNmb->canBeDeleted());
        $this->assertFalse($approvedCrdb->canBeDeleted());
    }

    public function test_only_active_nmb_account_is_bank_approved_and_locked(): void
    {
        $activeNmb = new PartnerAccount([
            'channel' => 'NMB',
            'onboarding_status' => PartnerAccount::ONBOARDING_ACTIVE,
        ]);
        $pendingNmb = new PartnerAccount([
            'channel' => 'NMB',
            'onboarding_status' => PartnerAccount::ONBOARDING_PENDING_ACTIVATION,
        ]);
        $crdb = new PartnerAccount([
            'channel' => 'CRDB',
            'onboarding_status' => PartnerAccount::ONBOARDING_NOT_REQUIRED,
        ]);

        $this->assertTrue($activeNmb->isBankApproved());
        $this->assertFalse($pendingNmb->isBankApproved());
        $this->assertFalse($crdb->isBankApproved());
    }

    public function test_verified_crdb_account_does_not_require_bank_approval(): void
    {
        $status = PartnerAccount::inferOnboardingStatus('CRDB', true);

        $this->assertSame(PartnerAccount::ONBOARDING_NOT_REQUIRED, $status);
    }

    public function test_unverified_crdb_account_stays_pending_emakato_review(): void
    {
        $status = PartnerAccount::inferOnboardingStatus('CRDB', false);

        $this->assertSame(PartnerAccount::ONBOARDING_PENDING_REVIEW, $status);
    }

    public function test_explicitly_active_nmb_account_is_active(): void
    {
        $status = PartnerAccount::inferOnboardingStatus(
            'NMB',
            true,
            'Account processing completed successfully and is active.'
        );

        $this->assertSame(PartnerAccount::ONBOARDING_ACTIVE, $status);
    }

    /**
     * @dataProvider ambiguousNmbRemarks
     */
    public function test_ambiguous_verified_nmb_accounts_require_review(string $remarks): void
    {
        $status = PartnerAccount::inferOnboardingStatus('NMB', true, $remarks);

        $this->assertSame(PartnerAccount::ONBOARDING_REVIEW_REQUIRED, $status);
    }

    public static function ambiguousNmbRemarks(): array
    {
        return [
            ['Corporate registration is being processed.'],
            ['Corporate account is not active.'],
            ['Duplicate corporate account.'],
            ['Successfully created corporate account.'],
        ];
    }

    public function test_rejected_nmb_account_remains_rejected(): void
    {
        $status = PartnerAccount::inferOnboardingStatus('NMB', false, 'Rejected', true);

        $this->assertSame(PartnerAccount::ONBOARDING_REJECTED, $status);
    }

    public function test_submitted_nmb_account_remains_pending_bank_activation(): void
    {
        $status = PartnerAccount::inferOnboardingStatus('NMB', false, 'Submitted', false, 2);

        $this->assertSame(PartnerAccount::ONBOARDING_SUBMITTED, $status);
    }
}
