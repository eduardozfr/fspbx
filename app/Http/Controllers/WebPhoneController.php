<?php

namespace App\Http\Controllers;

use App\Models\Extensions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebPhoneController extends Controller
{
    public function index(Request $request): Response
    {
        $extension = $this->resolveExtension($request);

        return Inertia::render('WebPhone', [
            'profile' => [
                'has_assigned_extension' => (bool) $extension,
                'display_name' => $extension?->effective_caller_id_name ?: $request->user()?->name_formatted,
                'extension' => $extension?->extension,
                'extension_uuid' => $extension?->extension_uuid,
                'domain' => $extension?->user_context ?: $request->getHost(),
                'requires_https' => ! $request->isSecure(),
            ],
            'routes' => [
                'config' => route('web-phone.config'),
            ],
        ]);
    }

    public function config(Request $request): JsonResponse
    {
        $extension = $this->resolveExtension($request);

        if (! $extension) {
            return response()->json([
                'messages' => [
                    'error' => ['Assign an extension to this user before using the Web Phone.'],
                ],
            ], 422);
        }

        $forceSecure = (bool) config('services.webphone.force_secure', true);
        $secure = $forceSecure || $request->isSecure();
        $host = config('services.webphone.host') ?: $request->getHost();
        $port = $secure
            ? (int) config('services.webphone.wss_port', 7443)
            : (int) config('services.webphone.ws_port', 5066);
        $sipDomain = $extension->user_context ?: $request->getHost();

        return response()->json([
            'data' => [
                'aor' => sprintf('sip:%s@%s', $extension->extension, $sipDomain),
                'transport' => [
                    'server' => sprintf('%s://%s:%d', $secure ? 'wss' : 'ws', $host, $port),
                    'host' => $host,
                    'port' => $port,
                    'secure' => $secure,
                ],
                'auth' => [
                    'username' => $extension->extension,
                    'password' => $extension->password,
                ],
                'identity' => [
                    'display_name' => $extension->effective_caller_id_name ?: $request->user()?->name_formatted ?: $extension->extension,
                    'extension' => $extension->extension,
                    'domain' => $sipDomain,
                ],
                'media' => [
                    'ice_servers' => $this->parseIceServers(),
                ],
                'dialing' => [
                    'prefix' => (string) config('services.webphone.outbound_prefix', ''),
                ],
            ],
        ]);
    }

    protected function resolveExtension(Request $request): ?Extensions
    {
        $extensionUuid = $request->user()?->extension_uuid;

        if (! $extensionUuid) {
            return null;
        }

        return Extensions::query()
            ->select([
                'extension_uuid',
                'extension',
                'password',
                'effective_caller_id_name',
                'user_context',
                'domain_uuid',
            ])
            ->where('domain_uuid', session('domain_uuid'))
            ->whereKey($extensionUuid)
            ->first();
    }

    protected function parseIceServers(): array
    {
        $servers = array_filter(array_map(
            static fn (string $server) => trim($server),
            explode(',', (string) config('services.webphone.ice_servers', 'stun:stun.l.google.com:19302'))
        ));

        return array_values(array_map(
            static fn (string $server) => ['urls' => $server],
            $servers
        ));
    }
}
