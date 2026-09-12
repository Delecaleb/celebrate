@php
    $defaultAccount = $bankAccounts->firstWhere('is_default');
@endphp

                <div class="page-head">
                    <div>
                        <h1>Bank account</h1>
                        <p class="sub">Where your withdrawals get paid. Only you can see these details.</p>
                    </div>
                </div>

                <div class="stats-bar cols-2">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="mdi mdi-bank-outline"></i></div>
                        <p class="stat-label">Saved accounts</p>
                        <p class="stat-value">{{ $bankAccounts->count() }}</p>
                        <p class="stat-sub">
                            {{ $bankAccounts->isEmpty() ? 'add one to withdraw' : 'available for payouts' }}
                        </p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="mdi mdi-star-check-outline"></i></div>
                        <p class="stat-label">Paid into</p>
                        <p class="stat-value" style="font-size:1.2rem">
                            {{ $defaultAccount->bank_name ?? 'Not set' }}
                        </p>
                        <p class="stat-sub">
                            {{ $defaultAccount
                                ? '••' . substr($defaultAccount->account_number, -4)
                                : 'pick a default account' }}
                        </p>
                    </div>
                </div>

                <div class="tab-panel-header">
                    <div>
                        <h2 class="panel-title">Your accounts</h2>
                        <p class="panel-subtitle">Saved details used for withdrawal payments</p>
                    </div>
                    <button class="btn-create-lg" @click="$dispatch('open-modal', 'add-bank')">
                        <i class="mdi mdi-plus"></i> Add bank account
                    </button>
                </div>

                @if (session('success'))
                    <div class="flash-ok"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
                @endif

                <div style="max-width:680px">
                    @if ($bankAccounts->isEmpty())
                        <div class="empty-state">
                            <i class="mdi mdi-bank-off-outline empty-icon"></i>
                            <h3>No bank account saved</h3>
                            <p>Add a bank account so we can process your withdrawal requests quickly.</p>
                        </div>
                    @else
                        <div class="bank-list">
                            @foreach ($bankAccounts as $ba)
                                <div class="bank-item">
                                    <div class="bank-icon"><i class="mdi mdi-bank"></i></div>
                                    <div class="bank-info">
                                        <div class="bank-name">
                                            {{ $ba->bank_name }}
                                            @if ($ba->is_default)
                                                <span class="default-badge">Default</span>
                                            @endif
                                        </div>
                                        <p class="bank-meta">
                                            {{ $ba->account_number }} · {{ $ba->account_name }}
                                            @if ($ba->is_verified)
                                                <i class="mdi mdi-check-decagram" title="Confirmed with the bank"
                                                   style="color:var(--ok)"></i>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="bank-actions">
                                        @unless ($ba->is_default)
                                            <form method="POST" action="{{ route('bank-accounts.default', $ba) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="icon-btn accent" title="Set as default">
                                                    <i class="mdi mdi-star-outline"></i>
                                                </button>
                                            </form>
                                        @endunless
                                        <button class="icon-btn"
                                            title="Edit"
                                            @click="
                                                editingBank = {
                                                    id: {{ $ba->id }},
                                                    bank_name: '{{ addslashes($ba->bank_name) }}',
                                                    bank_code: '{{ $ba->bank_code }}',
                                                    account_number: '{{ $ba->account_number }}',
                                                    account_name: '{{ addslashes($ba->account_name) }}'
                                                };
                                                $dispatch('open-modal', 'edit-bank')
                                            ">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </button>
                                        <form method="POST" action="{{ route('bank-accounts.destroy', $ba) }}"
                                              x-data
                                              @submit.prevent="confirm('Remove {{ addslashes($ba->bank_name) }} ({{ $ba->account_number }})? This cannot be undone.') && $el.submit()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Remove">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
