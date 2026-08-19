{{-- photobookGenerator() is defined in resources/js/modules/photobookGenerator.js --}}
{{-- Opens when any element dispatches the 'open-photobook' window event.       --}}

<div
    x-data="photobookGenerator(window.CelebrationConfig?.photobook ?? {})"
    @open-photobook.window="open = true"
    @keydown.escape.window="open = false"
    @keydown.arrow-left.window="if (open && generated) prevPage()"
    @keydown.arrow-right.window="if (open && generated) nextPage()"
>

    {{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="open = false"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
        style="display:none"
    >

        {{-- ── Modal panel ──────────────────────────────────────────────────── --}}
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="bg-white rounded-3xl w-full max-w-3xl overflow-hidden flex flex-col shadow-2xl"
            style="max-height:90vh"
        >

            {{-- ── Modal header ─────────────────────────────────────────────── --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                        <i class="mdi mdi-book-open-page-variant-outline text-lg text-violet-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-base leading-none">Comment Photobook</h2>
                        <p class="text-xs text-gray-400 mt-0.5"
                           x-text="`${commentsFiltered.length} messages · ${generated ? totalPages + ' pages ready' : 'Configure & generate'}`"></p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                >
                    <i class="mdi mdi-close text-lg"></i>
                </button>
            </div>

            {{-- ── Body ─────────────────────────────────────────────────────── --}}
            <div class="flex flex-col md:flex-row flex-1 overflow-hidden min-h-0">

                {{-- Mobile Tabs Navigation --}}
                <div class="md:hidden flex border-b border-gray-100 shrink-0">
                    <button 
                        type="button"
                        @click="activeTab = 'settings'" 
                        :class="activeTab === 'settings' ? 'border-b-2 border-violet-600 text-violet-600 font-bold' : 'text-gray-500'" 
                        class="flex-1 py-3 text-xs text-center focus:outline-none"
                    >
                        <i class="mdi mdi-cog-outline mr-1"></i> Configure
                    </button>
                    <button 
                        type="button"
                        @click="activeTab = 'preview'" 
                        :class="activeTab === 'preview' ? 'border-b-2 border-violet-600 text-violet-600 font-bold' : 'text-gray-500'" 
                        class="flex-1 py-3 text-xs text-center focus:outline-none relative"
                    >
                        <i class="mdi mdi-book-open-outline mr-1"></i> Preview
                        <span x-show="generated && !generating" class="absolute top-2.5 right-6 w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </button>
                </div>

                {{-- ── LEFT: Settings ───────────────────────────────────────── --}}
                <div 
                    :class="activeTab === 'settings' ? 'block' : 'hidden md:block'"
                    class="md:w-60 shrink-0 border-b md:border-b-0 md:border-r border-gray-100 overflow-y-auto p-5 space-y-5"
                >

                    {{-- Per-page setting --}}
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2.5">Comments per Page</p>
                        <div class="grid grid-cols-4 gap-1.5">
                            @foreach ([1, 2, 3, 4, 5, 6] as $n)
                            <button
                                type="button"
                                @click="perPage = {{ $n }}"
                                :class="perPage === {{ $n }}
                                    ? 'bg-violet-600 text-white border-violet-600 shadow-md shadow-violet-100'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300'"
                                class="border-2 rounded-xl py-2.5 text-sm font-bold transition"
                            >{{ $n }}</button>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5">
                            <span x-text="Math.ceil(commentsFiltered.length / perPage)"></span>
                            comment pages + 1 cover
                        </p>
                    </div>

                    {{-- Dimension presets --}}
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2.5">Dimensions</p>
                        <div class="space-y-1.5">
                            @foreach ([
                                ['square',    'Square',    '1:1',  '1080×1080', 'mdi-crop-square'],
                                ['portrait',  'Portrait',  '4:5',  '1080×1350', 'mdi-crop-portrait'],
                                ['landscape', 'Landscape', '16:9', '1920×1080', 'mdi-crop-landscape'],
                                ['a4',        'A4 Portrait','A4',  '1240×1754', 'mdi-file-document-outline'],
                                ['a3',        'A3 Landscape','A3',  '1754×1240', 'mdi-file-document-box-outline'],
                            ] as [$key, $label, $ratio, $size, $icon])
                            <button
                                type="button"
                                @click="dimension = '{{ $key }}'"
                                :class="dimension === '{{ $key }}'
                                    ? 'border-violet-500 bg-violet-50 text-violet-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-violet-200'"
                                class="w-full flex items-center gap-3 border-2 rounded-xl px-3 py-2 transition text-left"
                            >
                                <i class="mdi {{ $icon }} text-base shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold leading-none">{{ $label }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $ratio }} · {{ $size }}</p>
                                </div>
                                <i
                                    x-show="dimension === '{{ $key }}'"
                                    class="mdi mdi-check-circle text-violet-500 text-base shrink-0"
                                ></i>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Generate button --}}
                    <button
                        type="button"
                        @click="generate()"
                        :disabled="generating || !commentsFiltered.length"
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-violet-600 to-rose-500 text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-violet-100"
                    >
                        <template x-if="!generating">
                            <span class="flex items-center gap-2">
                                <i class="mdi mdi-auto-fix text-base"></i>
                                <span x-text="generated ? 'Regenerate' : 'Generate Photobook'"></span>
                            </span>
                        </template>
                        <template x-if="generating">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Rendering…
                            </span>
                        </template>
                    </button>

                    {{-- Progress --}}
                    <div x-show="generating" x-transition>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] text-gray-400">Rendering pages…</span>
                            <span class="text-[10px] font-semibold text-violet-600" x-text="progress + '%'"></span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-violet-500 to-rose-500 transition-all duration-300"
                                :style="`width:${progress}%`"
                            ></div>
                        </div>
                    </div>

                    {{-- Download controls --}}
                    <template x-if="generated && !generating">
                        <div class="space-y-2.5">

                            {{-- Format toggle --}}
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Format</p>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button
                                        type="button"
                                        @click="downloadFormat = 'image'"
                                        :class="downloadFormat === 'image'
                                            ? 'bg-violet-600 text-white border-violet-600 shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300'"
                                        class="border-2 rounded-xl py-2 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <i class="mdi mdi-image-outline text-sm"></i> Image
                                    </button>
                                    <button
                                        type="button"
                                        @click="downloadFormat = 'pdf'"
                                        :class="downloadFormat === 'pdf'
                                            ? 'bg-violet-600 text-white border-violet-600 shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300'"
                                        class="border-2 rounded-xl py-2 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <i class="mdi mdi-file-pdf-box text-sm"></i> PDF
                                    </button>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1" x-text="downloadFormat === 'pdf' ? 'All pages in one PDF file' : 'One JPG file per page'"></p>
                            </div>

                            {{-- Download this page --}}
                            <button
                                type="button"
                                @click="downloadPage(currentPage)"
                                class="w-full py-2.5 rounded-xl border-2 border-gray-200 text-sm text-gray-700 font-semibold hover:border-violet-400 hover:text-violet-600 transition flex items-center justify-center gap-2"
                            >
                                <i class="mdi text-base" :class="downloadFormat === 'pdf' ? 'mdi-file-pdf-box' : 'mdi-image-download'"></i>
                                <span x-text="downloadFormat === 'pdf' ? 'Download Page as PDF' : 'Download This Page'"></span>
                            </button>

                            {{-- Download all --}}
                            <button
                                type="button"
                                @click="downloadAll()"
                                :disabled="pdfBuilding"
                                class="w-full py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-700 transition disabled:opacity-60 flex items-center justify-center gap-2"
                            >
                                <template x-if="!pdfBuilding">
                                    <span class="flex items-center gap-2">
                                        <i class="mdi text-base" :class="downloadFormat === 'pdf' ? 'mdi-file-pdf-box' : 'mdi-download-multiple'"></i>
                                        <span x-text="downloadFormat === 'pdf' ? `Download All as PDF` : `Download All (${totalPages} pages)`"></span>
                                    </span>
                                </template>
                                <template x-if="pdfBuilding">
                                    <span class="flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        Building PDF…
                                    </span>
                                </template>
                            </button>

                        </div>
                    </template>

                </div>

                {{-- ── RIGHT: Preview ────────────────────────────────────────── --}}
                <div 
                    :class="activeTab === 'preview' ? 'flex' : 'hidden md:flex'"
                    class="flex-1 flex flex-col bg-gray-50 overflow-hidden min-h-0 relative"
                >
                    {{-- Success Toast --}}
                    <div 
                        x-show="showSuccessToast" 
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-4"
                        class="absolute top-4 left-1/2 -translate-x-1/2 bg-emerald-500 text-white px-4 py-2.5 rounded-2xl text-xs font-bold shadow-lg z-30 flex items-center gap-2"
                        style="display: none;"
                    >
                        <i class="mdi mdi-checkbox-marked-circle-outline text-base"></i>
                        <span>Photobook Generated!</span>
                    </div>

                    {{-- Empty state --}}
                    <template x-if="!generated && !generating">
                        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center justify-center mb-4">
                                <i class="mdi mdi-book-open-page-variant-outline text-3xl text-gray-300"></i>
                            </div>
                            <p class="font-semibold text-gray-700 text-sm">Preview appears here</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-[190px] leading-relaxed">
                                Choose your settings and click <strong>Generate Photobook</strong> to render a beautiful photobook from all messages.
                            </p>
                        </div>
                    </template>

                    {{-- Generating --}}
                    <template x-if="generating">
                        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                            <div class="relative w-14 h-14 mb-4">
                                <svg class="w-14 h-14 text-violet-100" fill="none" viewBox="0 0 24 24" style="animation:spin 3s linear infinite">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle>
                                </svg>
                                <svg class="w-14 h-14 text-violet-600 absolute inset-0" fill="none" viewBox="0 0 24 24" style="animation:spin 0.9s linear infinite">
                                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 2 A10 10 0 0 1 22 12"></path>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <i class="mdi mdi-book-open-page-variant-outline text-lg text-violet-500"></i>
                                </div>
                            </div>
                            <p class="font-semibold text-gray-700 text-sm">Crafting your photobook…</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="`${progress}% complete`"></p>
                        </div>
                    </template>

                    {{-- Page preview carousel --}}
                    <template x-if="generated && !generating">
                        <div class="flex-1 flex flex-col overflow-hidden min-h-0">

                            {{-- Image area --}}
                            <div class="flex-1 flex items-center justify-center p-4 overflow-hidden relative">
                                <img
                                    :src="currentPreview"
                                    :alt="`Photobook page ${currentPage + 1}`"
                                    class="max-w-full max-h-full object-contain rounded-2xl shadow-xl ring-1 ring-black/5"
                                >

                                {{-- Prev arrow --}}
                                <button
                                    type="button"
                                    x-show="currentPage > 0"
                                    @click="prevPage()"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-xl bg-white/90 shadow-md border border-gray-100 flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-white transition"
                                >
                                    <i class="mdi mdi-chevron-left text-xl"></i>
                                </button>

                                {{-- Next arrow --}}
                                <button
                                    type="button"
                                    x-show="currentPage < totalPages - 1"
                                    @click="nextPage()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-xl bg-white/90 shadow-md border border-gray-100 flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-white transition"
                                >
                                    <i class="mdi mdi-chevron-right text-xl"></i>
                                </button>
                            </div>

                            {{-- Footer: page counter + dot nav --}}
                            <div class="shrink-0 border-t border-gray-100 bg-white px-5 py-3 flex items-center justify-between gap-3">
                                <span class="text-xs text-gray-500 shrink-0">
                                    <span class="font-semibold" x-text="currentPage + 1"></span>
                                    <span x-text="` / ${totalPages}`"></span>
                                    <span class="text-gray-300 mx-1">·</span>
                                    <span class="text-gray-400" x-text="currentPage === 0 ? 'Cover' : `Page ${currentPage}`"></span>
                                </span>

                                <div class="flex items-center gap-1 flex-1 justify-center">
                                    <template x-for="(_, i) in pages.slice(0, 9)" :key="i">
                                        <button
                                            type="button"
                                            @click="currentPage = i"
                                            :class="currentPage === i
                                                ? 'w-4 h-1.5 bg-violet-600'
                                                : 'w-1.5 h-1.5 bg-gray-300 hover:bg-gray-400'"
                                            class="rounded-full transition-all"
                                        ></button>
                                    </template>
                                    <template x-if="totalPages > 9">
                                        <span class="text-[10px] text-gray-400 ml-1">+<span x-text="totalPages - 9"></span></span>
                                    </template>
                                </div>

                                <span class="text-[10px] text-gray-300 shrink-0 hidden sm:block">← → keys</span>
                            </div>

                        </div>
                    </template>

                </div>

            </div>

        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
