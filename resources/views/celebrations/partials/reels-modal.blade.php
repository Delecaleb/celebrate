{{--
    Immersive video-wish player (stories style).
    Lives inside the videoReelsPlayer() scope declared on .cel-body.
--}}
<div
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-0 md:p-4"
    style="background: rgba(0,0,0,0.95)"
    {{--
        These are window-level, so they fire even while the player is closed.
        Guard every one on isOpen — an unguarded `.space.prevent.window` here
        swallowed the spacebar across the whole page, so you could not type a
        space into any input on the celebration page.
    --}}
    @keydown.escape.window="isOpen && close()"
    @keydown.space.window="onSpaceKey($event)"
    @keydown.arrow-right.window="isOpen && next()"
    @keydown.arrow-left.window="isOpen && prev()"
>
    <div class="relative w-full h-full md:max-w-md md:h-[85vh] flex flex-col justify-between overflow-hidden"
         style="background:#0a0a0a"
         @click.away="close()">

        {{-- Segmented progress + author --}}
        <div class="absolute top-0 inset-x-0 z-20 p-4"
             style="background: linear-gradient(to bottom, rgba(0,0,0,.85), transparent)">
            <div class="flex gap-1 mb-4">
                <template x-for="(video, index) in videos" :key="video.id">
                    <div class="flex-1 h-[3px] overflow-hidden" style="background: rgba(255,255,255,.3)">
                        <div class="h-full bg-white"
                             :style="index === activeIndex ? { width: progress + '%' }
                                   : (index < activeIndex ? { width: '100%' } : { width: '0%' })"></div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="avatar avatar-sm" style="background: var(--primary); color:#fff"
                         x-text="activeVideo?.author?.[0]"></div>
                    <div>
                        <p class="text-white text-xs font-bold leading-none" x-text="activeVideo?.author"></p>
                        <p class="text-[10px] mt-1" style="color: rgba(255,255,255,.6)" x-text="activeVideo?.time"></p>
                    </div>
                </div>
                <button type="button" @click="close()" aria-label="Close"
                        class="ibtn" style="width:32px;height:32px;color:#fff;border-color:rgba(255,255,255,.2)">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
        </div>

        {{-- Video --}}
        <div class="flex-1 flex items-center justify-center relative">
            <video id="reelVideoPlayer"
                   :src="activeVideo?.media_url"
                   class="w-full h-full object-cover"
                   playsinline
                   @timeupdate="onTimeUpdate()"
                   @ended="onEnded()"></video>

            <div class="absolute inset-0 flex items-center justify-center cursor-pointer" @click="togglePlay()">
                <div x-show="!isPlaying" x-transition
                     class="w-16 h-16 flex items-center justify-center text-white text-3xl"
                     style="background: rgba(0,0,0,.5); border:1px solid rgba(255,255,255,.2)">
                    <i class="mdi mdi-play"></i>
                </div>
            </div>

            {{--
                Bottom-LEFT, laid out across. The next arrow owns the right edge
                at the vertical centre; keeping these in a right-hand column put
                the two controls on top of each other.
            --}}
            <div class="absolute left-4 bottom-4 z-20 flex flex-row gap-3">
                <button type="button" @click="togglePlay()" aria-label="Play or pause"
                        class="ibtn" style="width:44px;height:44px;color:#fff;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.15)">
                    <i class="mdi text-xl" :class="isPlaying ? 'mdi-pause' : 'mdi-play'"></i>
                </button>
                <button type="button" @click="toggleMute()" aria-label="Mute or unmute"
                        class="ibtn" style="width:44px;height:44px;color:#fff;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.15)">
                    <i class="mdi text-xl" :class="isMuted ? 'mdi-volume-off' : 'mdi-volume-high'"></i>
                </button>
            </div>
        </div>

        {{-- Desktop arrows --}}
        <button type="button" x-show="activeIndex > 0" @click="prev()" aria-label="Previous"
                class="ibtn hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 z-20"
                style="width:40px;height:40px;color:#fff;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.15)">
            <i class="mdi mdi-chevron-left text-2xl"></i>
        </button>
        <button type="button" x-show="activeIndex < videos.length - 1" @click="next()" aria-label="Next"
                class="ibtn hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 z-20"
                style="width:40px;height:40px;color:#fff;background:rgba(0,0,0,.45);border-color:rgba(255,255,255,.15)">
            <i class="mdi mdi-chevron-right text-2xl"></i>
        </button>

        {{-- Caption --}}
        <div class="z-20 p-6 pt-12 text-white"
             style="background: linear-gradient(to top, #000, rgba(0,0,0,.75), transparent)">
            <p class="text-sm leading-relaxed max-h-24 overflow-y-auto"
               style="color: rgba(255,255,255,.85)" x-text="activeVideo?.message"></p>
            <p class="mt-3 text-xs" style="color: rgba(255,255,255,.45)"
               x-text="String(activeIndex + 1) + ' of ' + String(videos.length)"></p>
        </div>
    </div>
</div>
