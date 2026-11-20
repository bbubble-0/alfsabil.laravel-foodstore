<?php

namespace App\Models;

use illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * fillable
     * 
     * @var array
     */
    protected $fillable = ['image', 'name', 'slug' ];

    /**
     * products
     * 
     * @return void
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * 
     * boot
     * 
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        //generate slug before saving
        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
