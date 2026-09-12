                {{--
                    Wallet.

                    Deliberately shows balances and money *out to your bank* only.
                    The full ledger — every gift in, every thing spent — is a
                    quiet disclosure at the bottom rather than the first thing
                    you see: nobody wants their spending totalled up at them.
                --}}
                <div class="page-head">
                    <div>
                        <h1>Wallet</h1>
                        <p class="sub">Your balances, and the money you've moved to your bank.</p>
                    </div>
                    <div class="head-actions">
                        <button class="btn-solid" @click="$dispatch('open-modal', 'fund-wallet')">
                            <i class="mdi mdi-plus"></i> Fund wallet
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="flash-err"><i class="mdi mdi-alert-circle"></i> {{ session('error') }}</div>
                @endif

                {{-- ── Balances ──────────────────────────────────────────
                     The local wallet only exists where USD checkout doesn't.
                     Somewhere USD works, one wallet is the whole story — a
                     second, also-USD balance beside it would just be two piles
                     of the same money.
                --}}
                <div class="wallet-grid">
                    @if ($hasLocalWallet)
                        <div class="wallet-hero">
                            <div class="wallet-chip">
                                <i class="mdi mdi-wallet" style="font-size:0.9rem"></i>
                                Local wallet ({{ $userCurrency }})
                            </div>
                            <p class="wallet-label">Available balance</p>
                            <p class="wallet-balance">{{ $currencySymbol }}{{ number_format($localDisplay, 2) }}</p>
                        </div>
                    @endif

                    <div class="wallet-hero is-global">
                        <div class="wallet-chip">
                            <i class="mdi {{ $hasLocalWallet ? 'mdi-earth' : 'mdi-wallet' }}" style="font-size:0.9rem"></i>
                            {{ $hasLocalWallet ? 'Global wallet (USD)' : 'Wallet (USD)' }}
                        </div>
                        <p class="wallet-label">Available balance</p>
                        <p class="wallet-balance">${{ number_format($globalDisplay, 2) }}</p>
                    </div>
                </div>

                {{-- ── Withdrawals ───────────────────────────────────── --}}
                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">Withdrawals</h2>
                        <p class="panel-subtitle">Money you've moved out to your bank account</p>
                    </div>
                    @if ($bankAccounts->isNotEmpty())
                        <button class="btn-create-lg" @click="$dispatch('open-modal', 'request-withdrawal')">
                            <i class="mdi mdi-bank-transfer-out"></i> Request withdrawal
                        </button>
                    @endif
                </div>

                @if ($withdrawals->isEmpty())
                    <div class="empty-state">
                        <i class="mdi mdi-bank-transfer-out empty-icon"></i>
                        <h3>No withdrawals yet</h3>
                        <p>When you move money from your wallet to your bank account, each payout will be listed here.</p>
                        @if ($bankAccounts->isEmpty())
                            <a href="{{ route('dashboard.bank') }}" data-nav class="btn-empty">
                                <i class="mdi mdi-plus"></i> Add a bank account
                            </a>
                        @else
                            <button class="btn-empty" @click="$dispatch('open-modal', 'request-withdrawal')">
                                <i class="mdi mdi-bank-transfer-out"></i> Request withdrawal
                            </button>
                        @endif
                    </div>
                @else
                    @include('dashboard.partials.shared.withdrawal-history')
                @endif

                {{-- ── Full ledger, folded away ──────────────────────── --}}
                @if ($walletTransactions->isNotEmpty())
                    <div class="ledger" x-data="{ open: false }">
                        <button class="link-quiet" @click="open = !open" :aria-expanded="open">
                            <i class="mdi" :class="open ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                            <span x-text="open ? 'Hide transaction history' : 'View transaction history'">View transaction history</span>
                        </button>

                        <div x-show="open" x-cloak x-transition.opacity style="margin-top:1.1rem">
                            <div class="tx-list">
                                <div class="tx-list-header">Transaction history</div>
                                @foreach ($walletTransactions as $tx)
                                    <div class="tx-item">
                                        <div class="tx-icon {{ $tx->type }}">
                                            <i class="mdi {{ $tx->type === 'credit' ? 'mdi-arrow-down' : 'mdi-arrow-up' }}"></i>
                                        </div>
                                        <div class="tx-info">
                                            <p class="tx-desc">{{ $tx->description ?: ($tx->type === 'credit' ? 'Credit received' : 'Debit') }}</p>
                                            @if ($giver = $tx->giverName())
                                                <p class="tx-from"><i class="mdi mdi-account-heart-outline"></i> from {{ $giver }}</p>
                                            @endif
                                            <p class="tx-meta">
                                                {{ $tx->created_at->format('M j, Y · g:ia') }}
                                                @if ($tx->reference) · Ref: {{ $tx->reference }} @endif
                                            </p>
                                        </div>
                                        <div class="tx-right">
                                            <p class="tx-amount {{ $tx->type }}">
                                                {{ $tx->type === 'credit' ? '+' : '−' }}{{ config("currency.currencies.{$tx->currency}.symbol", $currencySymbol) }}{{ number_format($tx->amount, 2) }}
                                            </p>
                                            <p class="tx-status">{{ ucfirst($tx->status) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
