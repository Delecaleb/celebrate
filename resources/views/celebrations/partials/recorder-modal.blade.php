{{-- Video wish recorder. Inside the wishForm() scope. --}}
<div x-show="showRecorder" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(20,11,18,.6)">
    <div class="card w-full max-w-lg">

        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--line)">
            <h3 class="t-h3">Record a video wish</h3>
            <button type="button" class="ibtn ibtn-bare" @click="closeRecorder()" aria-label="Close">
                <i class="mdi mdi-close"></i>
            </button>
        </div>

        <div class="p-5">
            <div class="relative w-full aspect-video overflow-hidden flex items-center justify-center" style="background:#000">
                <video id="recorderPreview" autoplay muted x-show="!hasRecordedVideo" class="w-full h-full object-cover"></video>
                <video id="recorderPlayback" controls x-show="hasRecordedVideo" class="w-full h-full object-cover"></video>

                <div x-show="isRecording" class="absolute top-3 left-3 badge" style="background: var(--danger); color:#fff">
                    <span class="dot" style="background:#fff"></span>
                    <span x-text="'0:' + String(timeLeft).padStart(2, '0')"></span>
                </div>

                <div x-show="error" class="absolute inset-0 flex items-center justify-center text-center p-6"
                     style="background: rgba(0,0,0,.8)">
                    <p class="text-white text-sm" x-text="error"></p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2 justify-center">
                {{-- nothing to record without a camera stream, so do not offer it --}}
                <button type="button" x-show="!isRecording && !hasRecordedVideo" @click="startRecording()"
                        :disabled="!recorderStream"
                        :style="!recorderStream ? 'opacity:.45;cursor:not-allowed' : ''"
                        class="btn btn-primary">
                    <i class="mdi mdi-record"></i> Start recording (max 30s)
                </button>

                <button type="button" x-show="isRecording" @click="stopRecording()" class="btn btn-dark">
                    <i class="mdi mdi-stop"></i> Stop recording
                </button>

                <button type="button" x-show="hasRecordedVideo" @click="openVideoRecorder()" class="btn btn-outline">
                    <i class="mdi mdi-refresh"></i> Record again
                </button>

                <button type="button" x-show="hasRecordedVideo" @click="useRecordedVideo()" class="btn btn-primary">
                    <i class="mdi mdi-check"></i> Use video
                </button>
            </div>
        </div>
    </div>
</div>
