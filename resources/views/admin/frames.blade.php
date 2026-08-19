@extends('admin.layout')

@section('title', 'Frame Management')
@section('topbar-title', 'Frame Management')

@section('topbar-actions')
    <span style="font-size:0.82rem;color:var(--muted)">
        {{ $frames->total() }} frame{{ $frames->total() === 1 ? '' : 's' }} total
    </span>
@endsection

@section('content')
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
    
    {{-- Create Frame Card --}}
    <div class="table-card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.2rem; font-size: 1.1rem; font-weight: 600;">Add New Frame</h3>
        
        <form action="{{ route('admin.frames.store') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem;">Frame Name</label>
                <input type="text" name="name" required placeholder="e.g. Birthday Confetti" class="filter-input" style="width: 100%;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem;">Frame Type</label>
                <select name="type" id="frame_type" onchange="toggleFrameInputs()" class="filter-select" style="width: 100%;">
                    <option value="css">CSS Styled Border</option>
                    <option value="svg">SVG Code Overlay</option>
                </select>
            </div>
            
            <div id="css_input_group">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem;">CSS Styles (Applied inline)</label>
                <textarea name="css_content" placeholder="border: 8px double gold; border-radius: 24px; box-shadow: 0 0 10px rgba(0,0,0,0.2);" class="filter-input" style="width: 100%; height: 100px; font-family: monospace; font-size: 0.8rem; resize: vertical;"></textarea>
                <span style="font-size: 0.72rem; color: var(--muted); display: block; margin-top: 0.2rem;">Define custom CSS properties applied to the container (e.g. border, box-shadow, background padding).</span>
            </div>
            
            <div id="svg_input_group" style="display: none;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem;">SVG Code (Overlay XML)</label>
                <textarea name="svg_content" placeholder="<svg viewBox='0 0 100 100' ...>...</svg>" class="filter-input" style="width: 100%; height: 120px; font-family: monospace; font-size: 0.8rem; resize: vertical;"></textarea>
                <span style="font-size: 0.72rem; color: var(--muted); display: block; margin-top: 0.2rem;">Full &lt;svg&gt; markup. It will overlay the cover image absolutely. Use pointer-events-none and absolute positioning tags.</span>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem;">Preview Thumbnail Image</label>
                <input type="file" name="preview_image" required accept="image/*" class="filter-input" style="width: 100%;">
            </div>
            
            <button type="submit" class="btn-filter" style="justify-content: center; width: 100%;">
                <i class="mdi mdi-plus"></i> Save Frame
            </button>
        </form>
    </div>

    {{-- Frames List Table --}}
    <div class="table-card">
        <div class="table-card-header">
            <h3 class="table-card-title"><i class="mdi mdi-image-frame"></i> Available Frames</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($frames as $frame)
                    <tr>
                        <td>
                            @if($frame->preview_image)
                                <img src="{{ asset('images/frames/' . $frame->preview_image) }}" alt="Preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                            @else
                                <span style="font-size: 0.8rem; color: var(--muted);">No Image</span>
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ $frame->name }}</td>
                        <td>
                            @if($frame->type === 'css')
                                <span class="badge badge-blue">CSS</span>
                            @else
                                <span class="badge badge-green">SVG</span>
                            @endif
                        </td>
                        <td>
                            <div style="max-width: 250px; max-height: 60px; overflow: hidden; text-overflow: ellipsis; font-family: monospace; font-size: 0.75rem; color: var(--muted); white-space: nowrap;">
                                {{ $frame->type === 'css' ? $frame->css_content : $frame->svg_content }}
                            </div>
                        </td>
                        <td>
                            <form action="{{ route('admin.frames.delete', $frame) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this frame?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tbl-btn" style="border: none; cursor: pointer;" title="Delete Frame">
                                    <i class="mdi mdi-trash-can-outline" style="color: #dc2626;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted); padding: 2rem;">
                            No frames added yet. Add your first frame on the left.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($frames->hasPages())
            <div style="padding: 1rem; border-top: 1px solid var(--border);">
                {{ $frames->links() }}
            </div>
        @endif
    </div>

</div>

<script>
    function toggleFrameInputs() {
        const type = document.getElementById('frame_type').value;
        const cssGroup = document.getElementById('css_input_group');
        const svgGroup = document.getElementById('svg_input_group');
        
        if (type === 'css') {
            cssGroup.style.display = 'block';
            svgGroup.style.display = 'none';
        } else {
            cssGroup.style.display = 'none';
            svgGroup.style.display = 'block';
        }
    }
</script>
@endsection
