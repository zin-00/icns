import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const token = document.querySelector('meta[name="csrf-token"]')
if (token) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
  console.log('CSRF token attached')
} else {
  console.error('CSRF token not found in <meta> tag.')
}

    window.Pusher = Pusher;
    window.Echo = new Echo({
    broadcaster: 'pusher',

    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});


