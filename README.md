<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## API Authentication

First-party jQuery AJAX requests use Sanctum's stateful session authentication and CSRF protection. Keep the frontend host in `SANCTUM_STATEFUL_DOMAINS` when deploying the app; do not put OAuth client secrets in browser code.

External OAuth2 clients authenticate with Passport bearer tokens. To initialize Passport in each environment:

```sh
php artisan migrate
php artisan passport:keys
php artisan passport:client --personal
```

Send `POST /api/login` with `email` and `password` to receive an `access_token`, then use it as a Bearer token for protected API routes. Keep the generated Passport keys and access tokens private; the keys are stored under `storage/` and are excluded from Git.

## Audio and video calls

Calls use WebRTC for media and Laravel Reverb for private, real-time call signaling. Set `BROADCAST_CONNECTION=reverb` and configure the `REVERB_*` and `VITE_REVERB_*` settings in `.env` (run `php artisan reverb:install` to generate the Reverb application credentials), then run `npm run build` after changing Vite environment settings. Start Reverb in a separate terminal with:

```sh
php artisan reverb:start
```

Both users must be signed in and connected to the same Reverb application. Each participant needs a working microphone, and video calls also need a working camera; allow the browser to access these devices and close other apps that may be using them. Camera and microphone access requires HTTPS in production (localhost is allowed for development). The built-in STUN server works for many networks; configure `CALL_TURN_URL`, `CALL_TURN_USERNAME`, and `CALL_TURN_CREDENTIAL` with short-lived TURN credentials when calls must work across restrictive NATs or firewalls. TURN credentials are sent to browsers and should not be long-lived secrets.

### Testing call signaling with Postman

First log in to get a token:

```http
POST /api/login
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

Copy `access_token` from the response. For the call requests below, select **Authorization → Bearer Token** in Postman and paste that token. Also set `Accept: application/json` and `Content-Type: application/json`.

Start a call:

```http
POST /api/calls/start
```

```json
{
  "recipient_id": 2,
  "call_type": "audio"
}
```

The response includes a `call_id`. Send signaling to the same recipient and include that ID:

```http
POST /api/calls/signal
```

```json
{
  "recipient_id": 2,
  "call_id": "UUID_FROM_START_RESPONSE",
  "signal_type": "offer",
  "payload": {
    "call_type": "audio",
    "description": {
      "type": "offer",
      "sdp": "test-sdp"
    }
  }
}
```

The signaling endpoint also accepts `answer`, `ice-candidate`, `accept`, `reject`, and `end`. Postman can verify API authentication, validation, and signaling responses; it cannot access a browser's camera or microphone or generate a real WebRTC offer. For an actual call, both users must be signed in in browsers, and Reverb must be running.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
