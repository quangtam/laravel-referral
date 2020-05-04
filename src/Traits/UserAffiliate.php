<?php

/*
 * This file is part of questocat/laravel-referral package.
 *
 * (c) questocat <zhengchaopu@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Questocat\Referral\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cookie;
use Questocat\Referral\Referral;

trait UserAffiliate
{
    /**
     * @param $url
     *
     * @return string
     */
    public function getAffiliateLink($url)
    {
        $refQuery = config('referral.ref_query');
        if($this->affiliate_id != '') {
            return $url.'?'.$refQuery.'='.$this->affiliate_id;
        } else {
            // Generate new affiliate_id
            $this->affiliate_id = self::generateAffiliateId();
            $this->save();
            // Return url
            return $url.'?'.$refQuery.'='.$this->affiliate_id;
        }
    }

    public static function scopeReferralExists(Builder $query, $referral)
    {
        return $query->whereAffiliateId($referral)->exists();
    }

    /**
     * @return mixed
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    protected static function bootUserAffiliate()
    {
        static::creating(function ($model) {
            $model->affiliate_id = self::generateAffiliateId();
        });

        static::created(function ($model) {
            $affiliateId = Cookie::get(config('referral.ref_cookie'));
            if ($affiliateId && $referrer = static::whereAffiliateId($affiliateId)->first()) {
                Referral::create(['referrer_id' => $referrer->id, 'referral_id' => $model->id]);
            }
        });
    }

    /**
     * Generate an affiliate id.
     * @return string
     */
    protected static function generateAffiliateId()
    {
        do {
            $referral = uniqid();
        } while (static::referralExists($referral));

        return $referral;
    }
}
