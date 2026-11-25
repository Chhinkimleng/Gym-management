<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production') || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL'])) {
            URL::forceScheme('https');
        }

        Paginator::useTailwind();

        View::composer('layouts.app', function ($view) {
            $gymName = Setting::getValue('gym_name', 'PulseCore Fitness');
            $currencySymbol = Setting::currencySymbol();
            $alerts = collect();

            if (auth()->check()) {
                $alerts = Cache::remember('header_alerts_v1', 60, function () use ($currencySymbol) {
                    $items = collect();
                    try {
                        $noticeDays = max(1, (int) Setting::getValue('expiring_notice_days', 7));

                        $expiring = Subscription::with(['member:id,name'])
                            ->select(['id', 'member_id', 'membership_plan_id', 'plan_name', 'end_date'])
                            ->where('status', Subscription::STATUS_ACTIVE)
                            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($noticeDays)->toDateString()])
                            ->orderBy('end_date')
                            ->take(5)
                            ->get();

                        foreach ($expiring as $sub) {
                            $items->push([
                                'type' => 'warning',
                                'title' => ($sub->member?->name ?? 'Member') . ' expiring soon',
                                'subtitle' => $sub->effectivePlanName() . ' • Ends ' . $sub->end_date->format('M d'),
                                'url' => route('subscriptions.show', $sub),
                            ]);
                        }

                        $openPayments = Payment::with('member:id,name')
                            ->select(['id', 'member_id', 'payment_reference', 'remaining_balance'])
                            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
                            ->where('remaining_balance', '>', 0)
                            ->latest('payment_date')
                            ->take(5)
                            ->get();

                        foreach ($openPayments as $pay) {
                            $items->push([
                                'type' => 'danger',
                                'title' => 'Balance due: ' . $currencySymbol . number_format((float) $pay->remaining_balance, 2),
                                'subtitle' => ($pay->member?->name ?? 'Member') . ' • ' . ($pay->payment_reference ?? '#' . $pay->id),
                                'url' => route('payments.show', $pay),
                            ]);
                        }
                    } catch (\Throwable) {
                        return collect();
                    }

                    return $items;
                });
            }

            $view->with([
                'gymName' => $gymName,
                'currencySymbol' => $currencySymbol,
                'headerAlerts' => $alerts,
            ]);
        });
    }
}
