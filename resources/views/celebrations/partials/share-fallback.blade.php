{{--
    Share sheet for browsers that cannot open the native one.

    commentShare.js prefers the real share sheet with the image attached. It
    only fires `wish-share-fallback` when that is unavailable — most often
    because the page is on plain http://, where navigator.share does not exist
    at all. Rather than silently dropping a file in the downloads folder, offer
    somewhere to actually send it.
--}}
<div
    x-data="wishShareSheet()"
    @wish-share-fallback.window="open($event.detail)"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[60] flex items-end md:items-center justify-center"
    style="background: rgba(0,0,0,0.6)"
    @click.self="close()"
    @keydown.escape.window="isOpen && close()"
>
    {{-- rounding via classes: an Alpine :style string would replace this
         element's inline style outright, background and all --}}
    <div class="w-full md:max-w-sm p-5 md:p-6 rounded-t-[20px] md:rounded-2xl"
         style="background: var(--tpl-card, var(--surface))">

        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="m-title" style="font-size:1.05rem">Share this wish</h3>
                <p class="m-sub" style="margin-top:.25rem;font-size:.8rem">Send the card, or share the page.</p>
            </div>
            <button type="button" @click="close()" aria-label="Close"
                    class="ibtn ibtn-bare" style="width:32px;height:32px">
                <i class="mdi mdi-close"></i>
            </button>
        </div>

        {{-- the generated card, so it is obvious what gets shared --}}
        <img :src="payload.imageUrl" alt=""
             class="w-full mb-4"
             style="border:1px solid var(--line); max-height:190px; object-fit:cover; object-position:bottom">

        <div class="grid grid-cols-4 gap-2 mb-4">
            <template x-for="target in targets" :key="target.name">
                <a :href="target.href" target="_blank" rel="noopener"
                   class="flex flex-col items-center gap-1.5 py-2.5"
                   style="border:1px solid var(--line); text-decoration:none; color:var(--ink)">
                    <i class="mdi" :class="target.icon" style="font-size:1.35rem"></i>
                    <span style="font-size:.62rem;font-weight:700" x-text="target.name"></span>
                </a>
            </template>
        </div>

        <div class="flex gap-2">
            <button type="button" @click="copyLink()" class="btn btn-quiet" style="flex:1">
                <i class="mdi" :class="copied ? 'mdi-check' : 'mdi-link-variant'"></i>
                <span x-text="copied ? 'Copied' : 'Copy link'"></span>
            </button>
            <a :href="payload.imageUrl" download="celebration-wish.jpg"
               class="btn btn-primary" style="flex:1" @click="close()">
                <i class="mdi mdi-download"></i> Save image
            </a>
        </div>

        {{--
            Only the page owner can act on this, so keep it quiet and factual
            rather than alarming a guest who just wanted to share a wish.
        --}}
        <p x-show="payload.blockedByHttp" x-cloak
           class="mt-3" style="font-size:.68rem;color:var(--muted-2);line-height:1.5">
            Tip: on an https:// address this opens your phone's own share sheet
            with the image already attached.
        </p>
    </div>
</div>

{{-- this page has no scripts stack, so the component lives right here --}}
<script>
    function wishShareSheet() {
        return {
            isOpen:  false,
            copied:  false,
            payload: { imageUrl: '', title: '', text: '', pageUrl: '', blockedByHttp: false },

            open(detail) {
                this.payload = detail;
                this.copied  = false;
                this.isOpen  = true;
            },

            close() { this.isOpen = false; },

            /*
             * These hand over a link, not the file — no social network accepts
             * an uploaded image through a plain intent URL. The image itself is
             * the "Save image" button, or the native sheet on https.
             */
            get targets() {
                const text = encodeURIComponent(this.payload.text ?? '');
                const url  = encodeURIComponent(this.payload.pageUrl ?? '');

                return [
                    { name: 'WhatsApp', icon: 'mdi-whatsapp', href: `https://wa.me/?text=${text}%20${url}` },
                    { name: 'X',        icon: 'mdi-twitter',  href: `https://twitter.com/intent/tweet?text=${text}&url=${url}` },
                    { name: 'Facebook', icon: 'mdi-facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${url}` },
                    { name: 'Email',    icon: 'mdi-email-outline',
                      href: `mailto:?subject=${encodeURIComponent(this.payload.title ?? '')}&body=${text}%20${url}` },
                ];
            },

            async copyLink() {
                try {
                    // clipboard API is secure-context only too, hence the fallback
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(this.payload.pageUrl);
                    } else {
                        const t = document.createElement('textarea');
                        t.value = this.payload.pageUrl;
                        t.style.position = 'fixed';
                        t.style.opacity  = '0';
                        document.body.appendChild(t);
                        t.select();
                        document.execCommand('copy');
                        document.body.removeChild(t);
                    }
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                } catch (e) {
                    window.showAlert?.('Could not copy the link.', 'error');
                }
            },
        };
    }
    window.wishShareSheet = wishShareSheet;
</script>
