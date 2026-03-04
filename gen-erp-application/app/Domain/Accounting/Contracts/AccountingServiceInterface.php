<?php

namespace App\Domain\Accounting\Contracts;

use App\Domain\Accounting\DTOs\CreateAccountData;
use App\Domain\Accounting\DTOs\ProposedJournalEntry;
use App\Domain\Accounting\DTOs\UpdateAccountData;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\Accounting\Models\AccountGroup;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Carbon\Carbon;

/**
 * Interface for accounting service operations.
 */
interface AccountingServiceInterface
{
    /**
     * Create a new account.
     */
    public function createAccount(CreateAccountData $data): Account;

    /**
     * Update an account.
     */
    public function updateAccount(Account $account, UpdateAccountData $data): Account;

    /**
     * Delete an account.
     */
    public function deleteAccount(Account $account): void;

    /**
     * Get accounts for a company with optional filters.
     */
    public function getAccounts(Company $company, ?string $search = null, ?string $accountType = null, ?int $accountGroupId = null): \Illuminate\Database\Eloquent\Builder;

    /**
     * Create a journal entry.
     */
    public function createEntry(Company $company, array $data, array $lines): JournalEntry;

    /**
     * Post a journal entry.
     */
    public function postEntry(JournalEntry $entry, ?User $user = null): void;

    /**
     * Get account balance.
     */
    public function getBalance(Account $account, ?Carbon $asOf = null): int;

    /**
     * Get trial balance.
     */
    public function getTrialBalance(Company $company, Carbon $asOf): array;

    /**
     * Get profit and loss statement.
     */
    public function getProfitAndLoss(Company $company, Carbon $from, Carbon $to): array;

    /**
     * Get balance sheet.
     */
    public function getBalanceSheet(Company $company, Carbon $asOf): array;

    /**
     * Get paginated payment methods for a company.
     */
    public function getPaymentMethods(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get a specific payment method.
     */
    public function getPaymentMethod(int $companyId, int $id): PaymentMethod;

    /**
     * Create a payment method.
     */
    public function createPaymentMethod(int $companyId, array $data): PaymentMethod;

    /**
     * Update a payment method.
     */
    public function updatePaymentMethod(PaymentMethod $paymentMethod, array $data): PaymentMethod;

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): void;

    /**
     * Get paginated account groups for a company.
     */
    public function getAccountGroups(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get a specific account group.
     */
    public function getAccountGroup(int $companyId, int $id): AccountGroup;

    /**
     * Create an account group.
     */
    public function createAccountGroup(int $companyId, array $data): AccountGroup;

    /**
     * Update an account group.
     */
    public function updateAccountGroup(AccountGroup $accountGroup, array $data): AccountGroup;

    /**
     * Delete an account group.
     */
    public function deleteAccountGroup(AccountGroup $accountGroup): void;

    /**
     * Post a proposed journal entry using the PostingService.
     */
    public function postProposedEntry(ProposedJournalEntry $proposed, ?int $postedBy = null): JournalEntry;

    /**
     * Reverse a posted journal entry.
     */
    public function reverseEntry(JournalEntry $original, string $idempotencyKey, string $description, ?int $reversedBy = null): JournalEntry;
}