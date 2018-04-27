<?php

namespace App\Billing;

use App\Subscription;
use Carbon\Carbon;

trait Billable
{
    public static function byStripeId($stripeId)
    {
        return static::where('stripe_id', $stripeId)->firstOrFail();
    }

    public function setStripeSubscription($id)
    {
        $this->stripe_subscription = $id;
    }

    public function activate($customerId = null, $subscriptionId = null)
    {
        return $this->update([
            'stripe_id' => $customerId ?? $this->stripe_id,
            'stripe_active' => true,
            'stripe_subscription' => $subscriptionId ?? $this->stripe_subscription,
            'subscription_end_at' => 0
        ]);
    }

    public function deactivate($endDate = null)
    {
        $endDate = $endDate ?: Carbon::now();

        return $this->update([
            'stripe_active' => false,
            'subscription_end_at' => $endDate
        ]);
    }

    public function subscription()
    {
        return new Subscription($this);
    }

    public function hasCanceled()
    {
        return !! $this->stripe_active;
    }

    public function isActive()
    {
        return is_null($this->subscription_end_at) || $this->isOnGracePeriod();
    }

    public function isOnGracePeriod()
    {
        $endsAt = $this->subscription_end_at;

        if (! $endsAt) {
            return false;
        }

        return Carbon::now()->lt(Carbon::instance($endsAt));
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
