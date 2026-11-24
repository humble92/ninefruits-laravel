{{--
    Replaces Livewire's English "This page has expired" confirm (HTTP 419: the CSRF token or
    session changed while the page was open) with a translated one. Must load before Livewire
    starts, so it goes in <head>: the portal layout and the admin panel's HEAD_END hook.
--}}
<script>
    document.addEventListener('livewire:init', () => {
        let asked = false;

        window.Livewire.interceptRequest(({ onError }) => {
            onError(({ response, preventDefault }) => {
                if (response.status !== 419) {
                    return;
                }

                preventDefault();

                if (! asked) {
                    asked = true;

                    if (window.confirm(@js(__('errors.page_expired.title')."\n".__('errors.page_expired.reload_prompt')))) {
                        window.location.reload();
                    }
                }
            });
        });
    });
</script>
