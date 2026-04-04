<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'userId';

    /**
     * Password column for Auth::attempt / Hash::check (plain text is passed as credentials['password']).
     */
    protected $authPasswordName = 'userPass';

    /**
     * No remember_token column in the current schema — disable "remember me" persistence.
     */
    protected $rememberTokenName = '';

    protected $fillable = [
        'userName',
        'userPass',
        'userfName',
        'userlName',
        'address',
    ];

    protected $hidden = [
        'userPass',
    ];

    /**
     * Raw hash from DB (fixes common mistake: storing "\$2y\$..." with backslashes).
     */
    public function getAuthPassword(): string
    {
        $hash = $this->attributes['userPass'] ?? '';

        return is_string($hash) ? stripslashes($hash) : '';
    }

    protected function casts(): array
    {
        return [
            'userPass' => 'hashed',
        ];
    }
}
