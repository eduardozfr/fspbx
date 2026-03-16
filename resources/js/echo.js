/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY

if (!reverbKey) {
    window.Echo = null
} else {
    const fallbackScheme = window.location.protocol === 'https:' ? 'https' : 'http'
    const scheme = String(import.meta.env.VITE_REVERB_SCHEME || fallbackScheme).replace(':', '')
    const secure = scheme === 'https'
    const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const port = Number(import.meta.env.VITE_REVERB_PORT || (secure ? 443 : 80))
    const path = import.meta.env.VITE_REVERB_PATH || '/ws'
    const wsPath = path.startsWith('/') ? path : `/${path}`

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        wsPath,
        forceTLS: secure,
        enabledTransports: [secure ? 'wss' : 'ws'],
    })
}
