window._ = require('lodash');
window.axios = require('axios');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */


window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// ★★★ 이 부분을 추가하거나, 이 내용으로 교체해주세요. ★★★
axios.get('/sanctum/csrf-cookie').then(response => {
    console.log('CSRF cookie set successfully.');
}).catch(error => {
    console.error('Could not get CSRF cookie.', error);
});
