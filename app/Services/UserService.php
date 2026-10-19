<?php 

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService {

    public function servers () {
       return  User::whereRole_id(RoleEnum::SERVER)->get();
    }
}