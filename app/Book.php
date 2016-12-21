<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books' ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url', 'title', 'author', 'available'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at',
    ];

    protected $attributes = array( 'available' => false, 'author' => 'No author', 'title' => 'No title') ;

    public function setAuthorAttribute($value){
 
        if(empty($value)){
            $this->attributes['author'] = 'No author';
        }else{
            $this->attributes['author'] = $value;
        }
    }

    public function setTitleAttribute($value){
       
       if(empty($value)){
            if(!empty($this->url)){
                $path = parse_url($this->url)['path'];
                preg_match('/[Books|Buecher]\/([^\d]+)\-/', $path, $matches);
                $this->attributes['title'] = $matches[1];
            }else{
                $this->attributes['title'] = 'No title';
            }
        }else{
            $this->attributes['title'] = $value;
        }
    }
}
