<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;use Illuminate\Database\Eloquent\Factories\HasFactory;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {use HasFactory,Notifiable;protected $fillable=['name','username','email','role','password','profile_photo_path'];protected $hidden=['password','remember_token'];protected function casts():array{return ['email_verified_at'=>'datetime','password'=>'hashed'];}public function stocks():HasMany{return $this->hasMany(\App\Features\Stock\Models\Stock::class);}}
