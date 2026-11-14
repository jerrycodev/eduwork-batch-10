import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export default function initializeEcho() {
    window.Pusher = Pusher;

    window.Echo ??= new Echo({
        broadcaster: 'reverb',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });

    return window.Echo;
}
