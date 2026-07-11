<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'profile_photo',
        'bio',
        'gender',
        'date_of_birth',
        'country',
        'state',
        'city',
        'account_type',
        'provider',
        'provider_id',
        'email_verified_at',
        'status',
        'wallet_balance',
        'last_seen_at',
    ];

    protected $guarded = ['currency'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at'      => 'datetime',
        'wallet_balance'    => 'decimal:2',
    ];

    public function celebrations()
    {
        return $this->hasMany(Celebration::class);
    }

    public function gifts()
    {
        return $this->hasMany(Gift::class, 'sender_user_id');
    }

    public function mediaUploads()
    {
        return $this->hasMany(MediaUpload::class);
    }

    public function notifications()
    {
        return $this->hasMany(PlatformNotification::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function wishContributions()
    {
        return $this->hasMany(WishContribution::class, 'contributor_user_id');
    }

    public function ticketPurchases()
    {
        return $this->hasMany(TicketPurchase::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}
