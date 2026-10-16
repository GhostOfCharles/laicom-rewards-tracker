@php
    $captchaDriver = \EduLazaro\Laracaptcha\Facades\Captcha::driver();
@endphp

@if ($captchaDriver->name() === 'turnstile')
    <div class="auth-captcha-widget" data-rts-turnstile data-sitekey="{{ $captchaDriver->siteKey() }}" data-theme="light"></div>
    @once('laracaptcha-script')
        <script>
            window.rtsTurnstileReady = () => {
                document.querySelectorAll('[data-rts-turnstile]').forEach((widget) => {
                    if (widget.dataset.initialized) return;
                    widget.dataset.initialized = 'true';

                    const wrapper = widget.closest('.captcha-wrapper') || widget.parentElement;
                    let widgetId = null;
                    let currentSize = null;

                    const render = () => {
                        const size = wrapper.clientWidth < 304 ? 'compact' : 'normal';
                        if (size === currentSize) return;

                        if (widgetId !== null) window.turnstile.remove(widgetId);
                        delete widget.dataset.errorCode;
                        widget.dataset.size = size;
                        widgetId = window.turnstile.render(widget, {
                            sitekey: widget.dataset.sitekey,
                            theme: 'light',
                            size,
                            'error-callback': (code) => {
                                const numericCode = String(code);
                                if (/^\d{3,6}$/.test(numericCode)) widget.dataset.errorCode = numericCode;
                            },
                        });
                        currentSize = size;
                    };

                    render();
                    if ('ResizeObserver' in window) {
                        new ResizeObserver(render).observe(wrapper);
                    } else {
                        window.addEventListener('resize', render);
                    }
                });
            };
        </script>
        <script src="{{ $captchaDriver->scriptUrl() }}?render=explicit&amp;onload=rtsTurnstileReady" async defer></script>
    @endonce
@else
    <x-laracaptcha::widget theme="light" />
@endif
