@extends('admin.layout')

@section('title', 'Email')
@section('topbar-title', 'Email')

@section('topbar-actions')
    <a href="{{ route('admin.outbox') }}" class="btn-filter"><i class="mdi mdi-arrow-left"></i> Outbox</a>
@endsection

@section('content')

    @if (session('success'))
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="mdi mdi-alert"></i> {{ session('error') }}</div>
    @endif

    @if ($email->last_error)
        {{-- What the mail server actually said, verbatim. A paraphrase is no
             use when the answer is a policy code you have to look up. --}}
        <div class="alert alert-error" style="align-items:flex-start">
            <i class="mdi mdi-alert-circle-outline"></i>
            <div>
                <strong>{{ $email->status === 'failed' ? 'Gave up after ' . $email->attempts . ' ' . Str::plural('attempt', $email->attempts) : 'Last attempt was refused' }}.</strong>
                <p style="margin-top:0.4rem;font-family:ui-monospace,monospace;font-size:0.78rem;line-height:1.6;word-break:break-word">
                    {{ $email->last_error }}
                </p>
            </div>
        </div>
    @endif

    <div class="table-card" style="padding:1.75rem;margin-bottom:1.5rem">
        <table style="width:100%">
            <tbody>
                @foreach ([
                    'To'        => $email->recipient(),
                    'From'      => trim(($email->from_name ?? '') . ' <' . $email->from_address . '>'),
                    'Subject'   => $email->subject,
                    'Type'      => $email->type,
                    'Mailable'  => $email->mailable,
                    'Status'    => ucfirst($email->status) . ($email->attempts ? " · {$email->attempts} " . Str::plural('attempt', $email->attempts) : ''),
                    'Queued'    => $email->created_at?->format('D j M Y, g:ia'),
                    'Sent'      => $email->sent_at?->format('D j M Y, g:ia') ?? '—',
                    'Response'  => $email->response ?? '—',
                    'Reference' => $email->uuid,
                ] as $label => $value)
                    <tr>
                        <td style="width:130px;padding:0.5rem 0;font-size:0.74rem;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);vertical-align:top">
                            {{ $label }}
                        </td>
                        <td style="padding:0.5rem 0;font-size:0.88rem;word-break:break-word">{{ $value ?: '—' }}</td>
                    </tr>
                @endforeach

                @if ($email->metadata)
                    <tr>
                        <td style="padding:0.5rem 0;font-size:0.74rem;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);vertical-align:top">
                            Metadata
                        </td>
                        <td style="padding:0.5rem 0">
                            <pre style="font-size:0.78rem;background:var(--off);padding:0.7rem 0.9rem;margin:0;overflow-x:auto">{{ json_encode($email->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-top:1.5rem">
            @if ($email->status !== 'sent')
                <form method="POST" action="{{ route('admin.outbox.retry', $email) }}">
                    @csrf
                    <button type="submit" class="btn-create-lg">
                        <i class="mdi mdi-refresh"></i> Try again
                    </button>
                </form>

                @if ($email->status !== 'held')
                    <form method="POST" action="{{ route('admin.outbox.hold', $email) }}"
                          onsubmit="return confirm('Hold this email? It will not be sent until you retry it.')">
                        @csrf
                        <button type="submit" class="btn-filter">
                            <i class="mdi mdi-pause"></i> Hold
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Sandboxed: this is somebody else's mail rendered back into a browser,
         so it gets no scripts and its own document. --}}
    <div class="table-card" style="padding:0;overflow:hidden">
        <div style="padding:0.9rem 1.25rem;border-bottom:1px solid var(--line);font-size:0.78rem;font-weight:700;color:var(--muted)">
            What {{ $email->to_name ?: $email->to_address }} {{ $email->status === 'sent' ? 'received' : 'will receive' }}
        </div>
        <iframe src="{{ route('admin.outbox.preview', $email) }}"
                sandbox=""
                title="Email preview"
                style="width:100%;height:720px;border:0;display:block;background:#fff"></iframe>
    </div>

@endsection
