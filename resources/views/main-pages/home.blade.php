<x-guest-layout>

  <!-- Hero Section -->
  <section class="relative overflow-hidden bg-gradient-to-br from-pink-500 via-rose-500 to-orange-400 text-white">
    <div class="absolute inset-0 opacity-10 bg-cover bg-center"
      style="background-image: url('https://images.unsplash.com/photo-1513151233558-d860c5398176?q=80&w=2070&auto=format&fit=crop');">
    </div>

    <div class="relative max-w-7xl mx-auto px-6 py-24 lg:py-32 grid lg:grid-cols-2 gap-16 items-center">
      <div>
        <div class="inline-flex items-center rounded-full bg-white/20 backdrop-blur px-4 py-2 text-sm mb-6">
          🎉 Redefining Online Celebrations
        </div>

        <h1 class="text-5xl lg:text-7xl font-black leading-tight">
          Celebrate Moments.
          <br />
          <span class="text-yellow-200">Collect Love & Keep them forever.</span>
        </h1>

        <p class="mt-6 text-lg lg:text-xl text-pink-50 leading-relaxed max-w-xl">
          CelebrateMi helps people receive birthday wishes, gifts, money,
          videos, and unforgettable memories — all in one beautiful
          celebration page.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row gap-4">
          <button x-data x-on:click="$dispatch('open-modal', 'create-event')" class="bg-white text-rose-600 px-8 py-4 rounded-2xl font-semibold shadow-2xl hover:scale-105 transition-transform">
            Create Your Celebration
          </button>

          <button class="border border-white/40 bg-white/10 backdrop-blur px-8 py-4 rounded-2xl font-semibold hover:bg-white/20 transition">
            Watch Demo
          </button>
        </div>

        <div class="mt-10 flex items-center gap-8 text-sm text-pink-100">
          <div>
            <p class="text-3xl font-bold text-white">30s</p>
            <p>Create an event</p>
          </div>

          <div>
            <p class="text-3xl font-bold text-white">∞</p>
            <p>Memories shared</p>
          </div>

          <div>
            <p class="text-3xl font-bold text-white">24/7</p>
            <p>Online celebration</p>
          </div>
        </div>
      </div>

      <div class="relative">
        <div class="bg-white rounded-[32px] p-5 shadow-2xl rotate-2">
          <div class="bg-gradient-to-br from-rose-100 to-orange-100 rounded-[24px] p-6 text-gray-900">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Birthday Celebration</p>
                <h3 class="text-2xl font-bold mt-1">John’s 25th Birthday 🎂</h3>
              </div>

              <div class="bg-rose-500 text-white px-4 py-2 rounded-full text-sm font-semibold">
                Live
              </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4">
              <div class="bg-white rounded-2xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Messages</p>
                <h4 class="text-3xl font-black mt-2">245</h4>
              </div>

              <div class="bg-white rounded-2xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Gifted</p>
                <h4 class="text-3xl font-black mt-2">₦850k</h4>
              </div>
            </div>

            <div class="mt-6 bg-white rounded-2xl p-5 shadow-sm">
              <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-full bg-rose-200"></div>
                <div>
                  <p class="font-semibold">James A.</p>
                  <p class="text-gray-600 mt-1 text-sm">
                    “Happy birthday bro! Wishing you greatness always 🎉”
                  </p>
                </div>
              </div>
            </div>

            <button class="mt-6 w-full bg-rose-500 hover:bg-rose-600 text-white py-4 rounded-2xl font-semibold transition">
              Send Gift 🎁
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
  <x-modal name="create-event" maxWidth="2xl" focusable>
    <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900">Create Your Celebration</h2>
        <p class="mt-2 text-sm text-gray-600">Fill in the details to create your unique celebration page.</p>
        <div 
            x-data="celebrationForm()"
            x-init="loggedIn = @js(auth()->check())"
            class="max-w-2xl mx-auto"
        >

    <form @submit.prevent="nextStep">

        <!-- STEP INDICATOR -->
        <div class="flex items-center gap-2 mb-6">
            <div :class="step >= 1 ? 'bg-rose-500 text-white' : 'bg-gray-200'"
                class="w-8 h-8 rounded-full flex items-center justify-center">
                1
            </div>

            <div :class="step >= 2 ? 'bg-rose-500 text-white' : 'bg-gray-200'"
                class="w-8 h-8 rounded-full flex items-center justify-center">
                2
            </div>
        </div>

        <!-- STEP 1 -->
        <div x-show="step === 1">

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">
                    Name Of Celebrant
                </label>

                <input 
                    type="text"
                    x-model="form.celebrantName"
                    class="mt-1 block w-full border-gray-300 rounded-md"
                >
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">
                    What are you celebrating?
                </label>

                <select 
                    x-model="form.eventType"
                    class="mt-1 block w-full border-gray-300 rounded-md"
                >
                    <option value="">Select an option</option>
                    <option value="birthday">Birthday</option>
                    <option value="wedding">Wedding</option>
                    <option value="graduation">Graduation</option>
                </select>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">
                    When?
                </label>

                <div class="flex gap-2">
                    <input 
                        placeholder="Select starting date"
                        type="text"
                        x-model="form.startDate"
                        class="datepicker w-full border-gray-300 rounded-md"
                    >

                    <input 
                        placeholder="Select ending date"
                        type="text"
                        x-model="form.endDate"
                        class="datepicker w-full border-gray-300 rounded-md"
                    >
                </div>
            </div>
            <div class="mt-4">
              <label for="event-description" class="block text-sm font-medium text-gray-700">Title</label>
              <input type="text" id="event-description" x-model="form.eventTitle" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm">
            </div>
            <button 
                type="submit"
                class="mt-6 bg-rose-500 text-white px-4 py-2 rounded-lg"
            >
                Continue
            </button>

        </div>

        <!-- STEP 2 -->
        <div x-show="step === 2">

            <h2 class="text-lg font-semibold mb-4">
                Login or Create Account
            </h2>

            <div class="mt-4">
                <input 
                    type="email"
                    x-model="auth.email"
                    placeholder="Email"
                    class="w-full border-gray-300 rounded-md"
                >
            </div>

            <div class="mt-4">
                <input 
                    type="password"
                    x-model="auth.password"
                    placeholder="Password"
                    class="w-full border-gray-300 rounded-md"
                >
            </div>

            <button 
                type="button"
                @click="submitForm"
                class="mt-6 bg-rose-500 text-white px-4 py-2 rounded-lg"
            >
                Finish
            </button>

        </div>

    </form>

</div>

<script>
function celebrationForm() {
    return {

        step: 1,

        loggedIn: false,

        form: {
            celebrantName: '',
            eventType: '',
            startDate: '',
            endDate: '',
            eventTitle:''
        },

        init() {
    this.$watch('form.celebrantName', () => this.generateTitle());
    this.$watch('form.eventType', () => this.generateTitle());
},

generateTitle() {
    const name = this.form.celebrantName?.trim();
    const type = this.form.eventType;

    if (!name && !type) {
        this.form.eventTitle = '';
        return;
    }

    const typeLabel = this.getEventLabel(type);

    this.form.eventTitle = `${name || 'Someone'}'s ${typeLabel}`;
},

getEventLabel(type) {
    const map = {
        birthday: 'Birthday',
        wedding: 'Wedding',
        graduation: 'Graduation'
    };

    return map[type] || 'Celebration';
},

        auth: {
            email: '',
            password: ''
        },

        nextStep() {

            // validation
            if (!this.form.celebrantName) {
                alert('Celebrant name is required');
                return;
            }

            // if already logged in skip auth step
            if (this.loggedIn) {
                this.submitForm();
                return;
            }

            // go to auth step
            this.step = 2;
        },

        async submitForm() {

            const response = await fetch('/create-celebration', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },

                body: JSON.stringify({
                    ...this.form,
                    ...this.auth
                })

            });

            const data = await response.json();

            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    }
}
</script>
        <div class="absolute top-4 right-4">
            <button x-on:click="$dispatch('close')" class="rounded-full h-10 w-10 flex items-center justify-center bg-gray-200">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
    </div>
</x-modal>

  <!-- Features -->
  <section class="max-w-7xl mx-auto px-6 py-24">
    <div class="text-center max-w-3xl mx-auto">
      <p class="text-rose-500 font-semibold uppercase tracking-widest">
        Why CelebrateMi
      </p>

      <h2 class="text-4xl lg:text-5xl font-black mt-4 leading-tight">
        More Than Wishes.
        <br />
        It’s An Experience.
      </h2>

      <p class="mt-6 text-lg text-gray-600">
        Create a beautiful celebration page where friends, family, and fans
        can send messages, gifts, money, videos, and love from anywhere in
        the world.
      </p>
    </div>

    <div class="mt-16 grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="rounded-3xl border border-gray-100 p-8 hover:shadow-2xl transition bg-white">
        <div class="text-5xl">🎂</div>
        <h3 class="mt-6 text-xl font-bold">Beautiful Event Pages</h3>
        <p class="mt-3 text-gray-600 leading-relaxed">
          Create stylish celebration pages in under 30 seconds.
        </p>
      </div>

      <div class="rounded-3xl border border-gray-100 p-8 hover:shadow-2xl transition bg-white">
        <div class="text-5xl">💸</div>
        <h3 class="mt-6 text-xl font-bold">Instant Gifting</h3>
        <p class="mt-3 text-gray-600 leading-relaxed">
          Receive money, gifts, and tokens seamlessly.
        </p>
      </div>

      <div class="rounded-3xl border border-gray-100 p-8 hover:shadow-2xl transition bg-white">
        <div class="text-5xl">🎥</div>
        <h3 class="mt-6 text-xl font-bold">Video & Photo Memories</h3>
        <p class="mt-3 text-gray-600 leading-relaxed">
          Friends can upload memorable videos and pictures.
        </p>
      </div>

      <div class="rounded-3xl border border-gray-100 p-8 hover:shadow-2xl transition bg-white">
        <div class="text-5xl">🌍</div>
        <h3 class="mt-6 text-xl font-bold">Share Anywhere</h3>
        <p class="mt-3 text-gray-600 leading-relaxed">
          Perfect for birthdays, weddings, graduations, and more.
        </p>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="py-24">
    <div class="max-w-5xl mx-auto px-6 text-center">
      <div class="bg-gradient-to-r from-rose-500 to-orange-400 rounded-[40px] p-12 text-white shadow-2xl">
        <h2 class="text-4xl lg:text-5xl font-black leading-tight">
          We ensure your celebration does not disappear after the event.
        </h2>

        <p class="mt-6 text-lg text-rose-50 max-w-2xl mx-auto">
          Birthdays, weddings, graduations, baby showers, anniversaries,
          memorials, and more.
        </p>

        <button class="mt-10 bg-white text-rose-600 px-10 py-4 rounded-2xl font-bold hover:scale-105 transition-transform shadow-lg">
          Start For Free
        </button>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t border-gray-100 py-10">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
      <div>
        <h3 class="text-2xl font-black text-rose-500">CelebrateMi</h3>
        <p class="text-gray-500 mt-2">
          Making celebrations unforgettable.
        </p>
      </div>

      <div class="flex items-center gap-6 text-gray-600">
        <a href="#" class="hover:text-rose-500 transition">Features</a>
        <a href="#" class="hover:text-rose-500 transition">Pricing</a>
        <a href="#" class="hover:text-rose-500 transition">Contact</a>
      </div>
    </div>
  </footer>

</x-guest-layout>