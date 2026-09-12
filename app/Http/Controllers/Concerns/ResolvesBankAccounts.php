<?php

namespace App\Http\Controllers\Concerns;

use App\Services\PaymentSystem\PaystackService;
use Illuminate\Validation\ValidationException;

/**
 * Shared by the web and API payout-account controllers.
 *
 * A payout account is only ever stored with the name the bank itself returns
 * for that bank + account number. The client never gets to decide it: if the
 * name it sends disagrees with the bank, the save is rejected rather than
 * quietly corrected, so nobody saves details that will bounce a transfer.
 */
trait ResolvesBankAccounts
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function bankAccountRules(): array
    {
        return [
            'bank_code'      => ['required', 'string', 'max:10'],
            'account_number' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'account_name'   => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * Turn a validated bank code + account number into the columns we are
     * willing to write.
     *
     * @param  array{bank_code: string, account_number: string, account_name?: string|null}  $data
     * @return array{bank_name: string, bank_code: string, account_number: string, account_name: string, is_verified: bool}
     *
     * @throws ValidationException when the bank is unknown, the account cannot
     *                             be resolved, or a supplied name disagrees
     *                             with the one on the account
     */
    protected function verifiedBankPayload(array $data): array
    {
        $paystack = app(PaystackService::class);

        $bank = collect($paystack->banks())->firstWhere('code', $data['bank_code']);

        if (! $bank) {
            throw ValidationException::withMessages([
                'bank_code' => 'Pick your bank from the list.',
            ]);
        }

        $claimed = trim((string) ($data['account_name'] ?? ''));

        // No key, no lookup — dev and CI still need to be able to add an
        // account. It goes in unverified, which is visible in the database and
        // on the API resource, rather than pretending it was checked.
        if (! $paystack->isConfigured()) {
            if ($claimed === '') {
                throw ValidationException::withMessages([
                    'account_name' => 'Account verification is unavailable — enter the account holder name.',
                ]);
            }

            return [
                'bank_name'      => $bank['name'],
                'bank_code'      => $bank['code'],
                'account_number' => $data['account_number'],
                'account_name'   => $claimed,
                'is_verified'    => false,
            ];
        }

        try {
            $resolved = $paystack->resolveAccountName($data['account_number'], $bank['code']);
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'account_number' => 'We could not reach your bank to confirm this account. Try again in a moment.',
            ]);
        }

        if ($resolved === null) {
            throw ValidationException::withMessages([
                'account_number' => "That account number isn't valid for {$bank['name']}. Check the number and the bank.",
            ]);
        }

        if ($claimed !== '' && ! $this->namesMatch($claimed, $resolved)) {
            throw ValidationException::withMessages([
                'account_name' => "This account belongs to {$resolved}. Save it under that name or use a different account.",
            ]);
        }

        return [
            'bank_name'      => $bank['name'],
            'bank_code'      => $bank['code'],
            'account_number' => $data['account_number'],
            'account_name'   => $resolved,
            'is_verified'    => true,
        ];
    }

    /**
     * Banks return names in their own order and punctuation — "OKAFOR JOHN C."
     * against "John C Okafor" is the same person. Compare the words, not the
     * string.
     */
    private function namesMatch(string $a, string $b): bool
    {
        return $this->nameKey($a) === $this->nameKey($b);
    }

    private function nameKey(string $name): string
    {
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        sort($words);

        return implode(' ', $words);
    }
}
