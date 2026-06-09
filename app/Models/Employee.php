<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'unit',
        'jabatan',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the employee can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check if employee is active
        if (!$this->is_active) {
            return false;
        }

        // Get role configuration
        $roleConfig = config('internal_roles');
        
        // Check if role exists in configuration
        if (!isset($roleConfig[$this->role])) {
            return false;
        }

        // Get the panel ID that this user's role is allowed to access
        $allowedPanelId = $roleConfig[$this->role]['panel'];

        // Check if the current panel matches the allowed panel
        return $panel->getId() === $allowedPanelId;
    }
}
