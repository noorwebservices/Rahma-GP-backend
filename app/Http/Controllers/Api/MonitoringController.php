<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    /** Fenêtre (minutes) considérée comme "en ligne / connecté maintenant". */
    protected const ONLINE_WINDOW_MINUTES = 5;

    /**
     * Vue d'ensemble : KPIs, répartition site/app, séries temporelles.
     */
    public function overview(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $onlineSince = now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        $base = Visit::whereBetween('created_at', [$start, $end]);

        $totalVisits = (clone $base)->count();
        $uniqueVisitors = (clone $base)->distinct('visitor_id')->count('visitor_id');

        $platformSplit = (clone $base)
            ->select('platform', DB::raw('COUNT(*) as visits'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('platform')
            ->get()
            ->keyBy('platform');

        $deviceSplit = (clone $base)
            ->select('device_type', DB::raw('COUNT(*) as visits'))
            ->groupBy('device_type')
            ->get();

        $onlineVisitors = Visit::where('created_at', '>=', $onlineSince)
            ->distinct('visitor_id')->count('visitor_id');

        $connectedUsers = Visit::where('created_at', '>=', $onlineSince)
            ->whereNotNull('user_id')
            ->distinct('user_id')->count('user_id');

        $newUsers = User::whereBetween('created_at', [$start, $end])
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->count();

        $totalUsers = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'periode' => ['debut' => $start->toIso8601String(), 'fin' => $end->toIso8601String()],
                'kpis' => [
                    'visites' => $totalVisits,
                    'visiteurs_uniques' => $uniqueVisitors,
                    'pages_vues' => $totalVisits,
                    'visiteurs_en_ligne' => $onlineVisitors,
                    'utilisateurs_connectes' => $connectedUsers,
                    'nouveaux_utilisateurs' => $newUsers,
                    'total_utilisateurs' => $totalUsers,
                ],
                'plateformes' => [
                    'web' => [
                        'visites' => (int) ($platformSplit['web']->visits ?? 0),
                        'visiteurs' => (int) ($platformSplit['web']->visitors ?? 0),
                    ],
                    'pwa' => [
                        'visites' => (int) ($platformSplit['pwa']->visits ?? 0),
                        'visiteurs' => (int) ($platformSplit['pwa']->visitors ?? 0),
                    ],
                ],
                'appareils' => $deviceSplit->map(fn ($d) => [
                    'type' => $d->device_type ?? 'inconnu',
                    'visites' => (int) $d->visits,
                ]),
                'series' => $this->timeseries($start, $end),
            ],
        ]);
    }

    /**
     * Top pays consultant le site.
     */
    public function countries(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $limit = (int) $request->input('limit', 20);

        $countries = Visit::whereBetween('created_at', [$start, $end])
            ->whereNotNull('country_code')
            ->select(
                'country',
                'country_code',
                DB::raw('COUNT(*) as visites'),
                DB::raw('COUNT(DISTINCT visitor_id) as visiteurs')
            )
            ->groupBy('country', 'country_code')
            ->orderByDesc('visites')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $countries,
        ]);
    }

    /**
     * Détail d'un pays : série temporelle, villes, visiteurs.
     */
    public function countryDetail(Request $request, string $code): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $code = strtoupper($code);

        $base = Visit::whereBetween('created_at', [$start, $end])->where('country_code', $code);

        $countryName = (clone $base)->value('country');

        $cities = (clone $base)
            ->whereNotNull('city')
            ->select('city', DB::raw('COUNT(*) as visites'), DB::raw('COUNT(DISTINCT visitor_id) as visiteurs'))
            ->groupBy('city')
            ->orderByDesc('visites')
            ->limit(30)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pays' => $countryName,
                'code' => $code,
                'kpis' => [
                    'visites' => (clone $base)->count(),
                    'visiteurs_uniques' => (clone $base)->distinct('visitor_id')->count('visitor_id'),
                    'utilisateurs' => (clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                ],
                'villes' => $cities,
                'series' => $this->timeseries($start, $end, fn ($q) => $q->where('country_code', $code)),
            ],
        ]);
    }

    /**
     * Utilisateurs les plus actifs sur la période.
     */
    public function activeUsers(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $limit = (int) $request->input('limit', 20);

        $rows = Visit::whereBetween('created_at', [$start, $end])
            ->whereNotNull('user_id')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as visites'),
                DB::raw('COUNT(DISTINCT DATE(created_at)) as jours_actifs'),
                DB::raw('MAX(created_at) as derniere_activite')
            )
            ->groupBy('user_id')
            ->orderByDesc('visites')
            ->limit($limit)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))
            ->with(['roles'])
            ->get()
            ->keyBy('id');

        $data = $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);

            return [
                'user_id' => $row->user_id,
                'nom' => $user?->nom,
                'prenom' => $user?->prenom,
                'email' => $user?->email,
                'avatar' => $user?->avatar,
                'roles' => $user ? $user->getRoleNames() : [],
                'visites' => (int) $row->visites,
                'jours_actifs' => (int) $row->jours_actifs,
                'derniere_activite' => Carbon::parse($row->derniere_activite)->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Détail d'activité d'un utilisateur.
     */
    public function userActivity(Request $request, User $user): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);

        $base = Visit::whereBetween('created_at', [$start, $end])->where('user_id', $user->id);

        $topPages = (clone $base)
            ->whereNotNull('path')
            ->select('path', DB::raw('COUNT(*) as vues'))
            ->groupBy('path')
            ->orderByDesc('vues')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'utilisateur' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'roles' => $user->getRoleNames(),
                ],
                'kpis' => [
                    'visites' => (clone $base)->count(),
                    'jours_actifs' => (clone $base)->select(DB::raw('COUNT(DISTINCT DATE(created_at)) as c'))->value('c'),
                    'derniere_activite' => optional((clone $base)->max('created_at'))
                        ? Carbon::parse((clone $base)->max('created_at'))->toIso8601String()
                        : null,
                ],
                'pages' => $topPages,
                'series' => $this->timeseries($start, $end, fn ($q) => $q->where('user_id', $user->id)),
            ],
        ]);
    }

    /**
     * Pages les plus consultées.
     */
    public function pages(Request $request): JsonResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $limit = (int) $request->input('limit', 30);

        $pages = Visit::whereBetween('created_at', [$start, $end])
            ->whereNotNull('path')
            ->select('path', DB::raw('COUNT(*) as vues'), DB::raw('COUNT(DISTINCT visitor_id) as visiteurs'))
            ->groupBy('path')
            ->orderByDesc('vues')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pages,
        ]);
    }

    /**
     * Activité temps réel : visiteurs des dernières minutes.
     */
    public function realtime(Request $request): JsonResponse
    {
        $window = (int) $request->input('minutes', 15);
        $since = now()->subMinutes($window);

        $recent = Visit::where('created_at', '>=', $since)
            ->with(['user:id,nom,prenom,avatar'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'path' => $v->path,
                'pays' => $v->country,
                'code_pays' => $v->country_code,
                'ville' => $v->city,
                'plateforme' => $v->platform,
                'appareil' => $v->device_type,
                'utilisateur' => $v->user ? [
                    'id' => $v->user->id,
                    'nom' => $v->user->nom,
                    'prenom' => $v->user->prenom,
                    'avatar' => $v->user->avatar,
                ] : null,
                'date' => $v->created_at->toIso8601String(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'fenetre_minutes' => $window,
                'visiteurs_en_ligne' => Visit::where('created_at', '>=', $since)->distinct('visitor_id')->count('visitor_id'),
                'utilisateurs_connectes' => Visit::where('created_at', '>=', $since)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'evenements' => $recent,
            ],
        ]);
    }

    /**
     * Série temporelle des visites / visiteurs uniques, granularité adaptée à la période.
     *
     * @param  callable|null  $constrain  contrainte supplémentaire sur la requête
     * @return array<int, array<string, mixed>>
     */
    protected function timeseries(Carbon $start, Carbon $end, ?callable $constrain = null): array
    {
        $days = $start->diffInDays($end);
        $groupByHour = $days <= 1;

        $format = $groupByHour ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';

        $query = Visit::whereBetween('created_at', [$start, $end]);
        if ($constrain) {
            $constrain($query);
        }

        $rows = $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as bucket"),
                DB::raw('COUNT(*) as visites'),
                DB::raw('COUNT(DISTINCT visitor_id) as visiteurs')
            )
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(fn ($r) => [
            'date' => $r->bucket,
            'visites' => (int) $r->visites,
            'visiteurs' => (int) $r->visiteurs,
        ])->all();
    }

    /**
     * Résout la période demandée : today, 7d, 30d, 90d, all, ou custom (from/to).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePeriod(Request $request): array
    {
        $period = $request->input('period', '30d');
        $end = now();

        if ($period === 'custom' && $request->filled('from')) {
            $start = Carbon::parse($request->input('from'))->startOfDay();
            $end = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now();

            return [$start, $end];
        }

        $start = match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'all' => Carbon::createFromTimestamp(0),
            default => now()->subDays(30),
        };

        return [$start, $end];
    }
}
