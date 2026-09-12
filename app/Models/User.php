<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // HasApiTokens is what the mobile client authenticates with — issued by
    // Api\AuthController, never used by the session-based web routes.
    use HasApiTokens, HasFactory, Notifiable;

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
        'global_wallet_balance',
        'last_seen_at',
        'email_notifications_enabled',
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
        'global_wallet_balance' => 'decimal:2',
        'email_notifications_enabled' => 'boolean',
    ];

    /**
     * The uploaded avatar, ready to put in a src.
     *
     * The celebration page has been asking for this for a while — it reads
     * profile_photo_url on a comment's author — but the accessor never
     * existed, so a signed-in wisher's photo silently never showed.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? asset('storage/' . $this->profile_photo)
            : null;
    }

    /** What stands in for a photo: up to two letters, never blank. */
    public function initials(): string
    {
        $letters = mb_strtoupper(
            mb_substr(trim((string) $this->first_name), 0, 1) .
            mb_substr(trim((string) $this->last_name), 0, 1)
        );

        if ($letters !== '') {
            return $letters;
        }

        return mb_strtoupper(mb_substr((string) $this->email, 0, 1)) ?: 'U';
    }

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
