<?php

namespace Penguin\Component\Database\Model;

use Penguin\Component\Database\Attributes\Accessor;
use Penguin\Component\Database\Attributes\Mutator;
use Penguin\Component\Database\Attributes\LocalScope;
use Penguin\Component\Database\Attributes\GlobalScope;
use Penguin\Component\Database\Attributes\Listen;

#[GlobalScope(DeletedScope::class)]
class User extends Model
{
    protected string $table = 'users';

    protected ?string $primaryKey = 'id';

    protected array $hidden = [
        'name'
    ];

    protected array $appends = [
        'avatar'
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // $user->name = 'abc';
        });

        static::deleting(function (User $user) {
            dump($user);
            return false;
        });

        static::creating(function (User $user) {
            dump('creating 2');
        });
    }

    #[Accessor('name')]
    protected function getName(string $name): string
    {
        return "hehe $name";
    }

    public function scopeEmailThuan($query, string $email): void
    {
        $query->where('email', $email);
    }
}