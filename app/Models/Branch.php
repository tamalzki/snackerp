<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'address', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

   public function inventory()
{
    return $this->hasMany(\App\Models\BranchInventory::class);
}

public function consignmentReceivables()
{
    return $this->hasMany(ConsignmentReceivable::class);
}
}