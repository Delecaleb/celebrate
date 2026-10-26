{{-- One decision on a reported thing. See AdminReportController::act. --}}
<form method="POST" action="{{ route('admin.reports.act', [$item->type, $item->id]) }}"
      @isset($confirm) onsubmit="return confirm(@js($confirm))" @endisset>
    @csrf
    <input type="hidden" name="action" value="{{ $action }}">
    <button type="submit" class="btn-filter" style="padding:0.4rem 0.7rem;font-size:0.78rem">
        <i class="mdi mdi-{{ $icon }}"></i> {{ $label }}
    </button>
</form>
