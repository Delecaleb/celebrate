function wishForm() {
    return {
        message: '',
        loading: false,
        showGuestModal: false,
        commentImage: null,
        commentImagePreview: '',

        // Video recording state
        commentVideo: null,
        commentVideoPreview: '',
        showRecorder: false,
        isRecording: false,
        hasRecordedVideo: false,
        timeLeft: 30,
        error: '',

        recorderStream: null,
        mediaRecorder: null,
        recordedChunks: [],
        timerInterval: null,

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
            // Clear any video
            this.clearVideo();
            this.commentImage = file;
            this.commentImagePreview = URL.createObjectURL(file);
        },

        // --- Video Recording Methods ---
        async openVideoRecorder() {
            this.error = '';
            this.showRecorder = true;
            this.hasRecordedVideo = false;
            this.commentVideo = null;
            this.commentVideoPreview = '';
            this.recordedChunks = [];
            this.timeLeft = 30;

            /*
             * navigator.mediaDevices only exists in a secure context. Browsers
             * exempt localhost, so recording works on a dev machine and then
             * silently does nothing on a phone hitting the same site over plain
             * http:// — which is exactly what it looks like: the API is simply
             * not there. Say so, rather than reporting a denied permission.
             */
            if (! navigator.mediaDevices?.getUserMedia) {
                this.error = window.isSecureContext
                    ? 'This browser cannot record video. Try attaching a video file instead.'
                    : 'Recording needs a secure connection. Open this page over https:// to record a video.';
                return;   // leave the recorder open so the message is visible
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    // ideal, not fixed — phone cameras rarely offer exactly 640x480
                    video: {
                        width:      { ideal: 640 },
                        height:     { ideal: 480 },
                        facingMode: 'user',
                    },
                    audio: true
                });
                this.recorderStream = stream;

                this.$nextTick(() => {
                    const preview = document.getElementById('recorderPreview');
                    if (preview) {
                        preview.srcObject = stream;
                        preview.muted = true;
                        preview.setAttribute('playsinline', '');   // iOS refuses to preview inline without it
                        preview.play().catch(e => console.log('Preview play error:', e));
                    }
                });
            } catch (err) {
                console.error(err);

                // Name the actual problem — "denied" and "no camera" need
                // different things from the person reading it.
                this.error = {
                    NotAllowedError:    'Camera and microphone access was blocked. Allow it in your browser settings and try again.',
                    NotFoundError:      'No camera or microphone was found on this device.',
                    NotReadableError:   'Your camera is already in use by another app.',
                    OverconstrainedError: 'This camera does not support the requested video size.',
                }[err.name] ?? 'Could not start the camera. Try attaching a video file instead.';

                // stay open: the message renders inside this modal
            }
        },

        startRecording() {
            if (!this.recorderStream) return;
            this.isRecording = true;
            this.timeLeft = 30;
            this.recordedChunks = [];

            const options = { mimeType: 'video/webm;codecs=vp8,opus' };
            try {
                this.mediaRecorder = new MediaRecorder(this.recorderStream, options);
            } catch (e) {
                // Fallback for Safari/iOS
                this.mediaRecorder = new MediaRecorder(this.recorderStream);
            }

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    this.recordedChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                const blob = new Blob(this.recordedChunks, { type: this.mediaRecorder.mimeType || 'video/webm' });
                this.commentVideo = blob;
                this.commentVideoPreview = URL.createObjectURL(blob);
                this.hasRecordedVideo = true;

                // Stop the camera tracks to turn off the hardware light
                if (this.recorderStream) {
                    this.recorderStream.getTracks().forEach(track => track.stop());
                    this.recorderStream = null;
                }

                this.$nextTick(() => {
                    const preview = document.getElementById('recorderPlayback');
                    if (preview) {
                        preview.src = this.commentVideoPreview;
                        preview.play().catch(e => console.log('Playback error:', e));
                    }
                });
            };

            this.mediaRecorder.start();

            this.timerInterval = setInterval(() => {
                this.timeLeft--;
                if (this.timeLeft <= 0) {
                    this.stopRecording();
                }
            }, 1000);
        },

        stopRecording() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            this.isRecording = false;
        },

        closeRecorder() {
            this.stopRecording();
            if (this.recorderStream) {
                this.recorderStream.getTracks().forEach(track => track.stop());
                this.recorderStream = null;
            }
            this.showRecorder = false;
        },

        useRecordedVideo() {
            // Clear any image
            this.commentImage = null;
            this.commentImagePreview = '';
            this.showRecorder = false;
        },

        clearVideo() {
            this.commentVideo = null;
            this.commentVideoPreview = '';
            this.hasRecordedVideo = false;
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
            if (!this.message.trim() && !this.commentImage && !this.commentVideo) {
                window.showAlert('Please enter a message, record a video, or select an image.', 'warning');
                return;
            }

            if (this.isAuthenticated) {
                this.submitWish();
                return;
            }
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
            formData.append('celebration_id', window.CelebrationConfig.celebrationId);
            formData.append('comment', this.message);
            formData.append('anonymous', anonymous ? 1 : 0);

            if (this.commentImage) {
                formData.append('image', this.commentImage);
            }

            if (this.commentVideo) {
                formData.append('video', this.commentVideo, 'wish-video.webm');
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
                    this.submittedMessage = this.message || 'Video Wish';
                    this.message = '';
                    this.commentImage = null;
                    this.commentImagePreview = '';
                    this.commentVideo = null;
                    this.commentVideoPreview = '';
                    this.hasRecordedVideo = false;

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