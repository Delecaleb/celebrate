{{-- Guest sign-in / anonymous flow. Inside the wishForm() scope. --}}
<div x-show="showGuestModal" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background: rgba(20,11,18,.5)">
    <div @click.away="showGuestModal = false" class="card w-full max-w-sm p-6">

        {{-- WELCOME --}}
        <template x-if="tab === 'welcome'">
            <div>
                <div class="tile tile-lg mx-auto mb-4"><i class="mdi mdi-send-outline"></i></div>
                <h3 class="t-h2 text-center">Send your wish</h3>
                <p class="text-sm t-muted text-center mt-2 mb-5">Choose how you'd like to continue.</p>

                <div class="space-y-2">
                    <button @click="submitAnonymous" class="btn btn-primary btn-block">
                        <i class="mdi mdi-incognito"></i> Continue as guest
                    </button>
                    <button @click="saveDraft(); tab='login'" class="btn btn-outline btn-block">Sign in</button>
                    <button @click="saveDraft(); tab='register'" class="btn btn-outline btn-block">Create account</button>
                </div>
            </div>
        </template>

        {{-- LOGIN --}}
        <template x-if="tab === 'login'">
            <div>
                <button @click="tab='welcome'" class="btn btn-ghost btn-sm mb-4" style="padding-left:0">
                    <i class="mdi mdi-arrow-left"></i> Back
                </button>
                <h3 class="t-h2 mb-4">Welcome back</h3>
                <form @submit.prevent="login" class="space-y-3">
                    <input x-model="loginForm.email" type="email" placeholder="Email" class="input">
                    <input x-model="loginForm.password" type="password" placeholder="Password" class="input">
                    <button type="submit" class="btn btn-primary btn-block">Sign in &amp; send wish</button>
                </form>
            </div>
        </template>

        {{-- REGISTER --}}
        <template x-if="tab === 'register'">
            <div>
                <button @click="tab='welcome'" class="btn btn-ghost btn-sm mb-4" style="padding-left:0">
                    <i class="mdi mdi-arrow-left"></i> Back
                </button>
                <h3 class="t-h2 mb-4">Create account</h3>
                <form @submit.prevent="register" class="space-y-3">
                    <input x-model="registerForm.name" type="text" placeholder="Full name" class="input">
                    <input x-model="registerForm.email" type="email" placeholder="Email" class="input">
                    <input x-model="registerForm.password" type="password" placeholder="Password" class="input">
                    <button type="submit" class="btn btn-primary btn-block">Create account &amp; send wish</button>
                </form>
            </div>
        </template>

        {{-- SUCCESS --}}
        <template x-if="tab === 'success'">
            <div>
                <div class="tile tile-lg mx-auto mb-4" style="background: var(--ok-l); color: var(--ok)">
                    <i class="mdi mdi-check"></i>
                </div>
                <h3 class="t-h2 text-center">Wish delivered</h3>
                <p class="text-sm t-muted text-center mt-2">Your message has been added to the celebration.</p>

                <div class="card mt-4 p-4" style="background: var(--surface-2)">
                    <p class="t-label mb-1.5">Your wish</p>
                    <p class="text-sm" x-text="submittedMessage"></p>
                </div>

                @guest
                    <div class="mt-5">
                        <p class="text-sm font-bold mb-3">Create a free account to:</p>
                        <ul class="space-y-2 text-sm t-muted">
                            <li class="flex items-center gap-2"><i class="mdi mdi-check t-accent"></i> Track celebrations</li>
                            <li class="flex items-center gap-2"><i class="mdi mdi-check t-accent"></i> Save memories</li>
                            <li class="flex items-center gap-2"><i class="mdi mdi-check t-accent"></i> Send gifts faster</li>
                            <li class="flex items-center gap-2"><i class="mdi mdi-check t-accent"></i> Get celebration reminders</li>
                            <li class="flex items-center gap-2"><i class="mdi mdi-check t-accent"></i> Create your own page</li>
                        </ul>
                        <div class="mt-5 space-y-2">
                            <button @click="tab='register'" class="btn btn-primary btn-block">Create free account</button>
                            <button @click="showGuestModal=false; window.location.reload()" class="btn btn-outline btn-block">Maybe later</button>
                        </div>
                    </div>
                @endguest

                @auth
                    <div class="mt-5">
                        <button @click="showGuestModal=false; window.location.reload()" class="btn btn-dark btn-block">
                            Great, thanks
                        </button>
                    </div>
                @endauth
            </div>
        </template>

    </div>
</div>
