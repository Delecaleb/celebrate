function wishForm() {
    return {

        message: '',
        loading: false,
        showGuestModal: false,
        commentImage:null,
        commentImagePreview: '',


        tab: 'welcome',

        loginForm: {
            email: '',
            password: ''
        },

        registerForm: {
            name: '',
            email: '',
            password: ''
        },
        async login() {
            alert('Login functionality is currently a placeholder. Please implement the login logic.');

            try {

                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.CelebrationConfig.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: this.loginForm.email,
                        password: this.loginForm.password
                    })
                });

                const data = await response.json();

                if (response.ok) {

                    this.isAuthenticated = true;

                    await this.submitWish();

                } else {

                    alert(data.message || 'Login failed');

                }

            } catch (error) {

                console.error(error);

            }
        },
        handleCommentImageUpload(event) {

    const file = event.target.files[0];

    if (!file) return;

    this.commentImage = file;

    this.commentImagePreview = URL.createObjectURL(file);
},
        async register() {

            try {

                const response = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.CelebrationConfig.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.registerForm.name,
                        email: this.registerForm.email,
                        password: this.registerForm.password,
                        password_confirmation: this.registerForm.password
                    })
                });

                const data = await response.json();

                if (response.ok) {

                    this.isAuthenticated = true;

                    await this.submitWish();

                } else {

                    alert(data.message || 'Registration failed');

                }

            } catch (error) {

                console.error(error);

            }
        },
        submittedMessage: '',

        isAuthenticated: window.CelebrationConfig.isAuthenticated,

        handleSubmit() {

            if (!this.message.trim() && !this.commentImage) {
                window.showAlert(
                    'Please enter a message or select an image.',
                    'warning'
                    );
                return;
            } 

            if (this.isAuthenticated) {
                this.submitWish();
                return;
            }
            this.commentImage;
            this.showGuestModal = true;
            this.tab = 'welcome';
        },

        saveDraft() {
            localStorage.setItem('celebrationWish', this.message);
        },

        async submitAnonymous() {
            this.showGuestModal = false;
            await this.submitWish(true);
        },

        async submitWish(anonymous = false) {

            this.loading = true;

            let formData = new FormData();

            formData.append(
                'celebration_id',
                window.CelebrationConfig.celebrationId
            );

            formData.append(
                'comment',
                this.message
            );

            formData.append(
                'anonymous',
                anonymous ? 1 : 0
            );

            if (this.commentImage) {
                formData.append(
                    'image',
                    this.commentImage
                );
            }

            try {

                const response = await fetch(
                    window.CelebrationConfig.commentStoreUrl,
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': window.CelebrationConfig.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    }
                );

                const data = await response.json();

                if (data.success) {

                    this.submittedMessage = this.message;

                    this.message = '';
                    this.commentImage = null;
                    this.commentImagePreview = '';

                    this.launchConfetti();

                    this.tab = 'success';
                    this.showGuestModal = true;

                    window.dispatchEvent(
                        new CustomEvent('wish-created', {
                            detail: data.comment
                        })
                    );

                } else {
                    window.showAlert(data.message || 'Unable to post comment', 'error');
                }

            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },

        launchConfetti() {
            confetti({
                particleCount: 150,
                spread: 120,
                origin: { y: 0.7 }
            });
        },
    }
}


window.wishForm = wishForm;