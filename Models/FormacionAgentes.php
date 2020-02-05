<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogsActivityInterface;
use Spatie\Activitylog\LogsActivity;

class FormacionAgentes extends Model implements LogsActivityInterface {

    use LogsActivity;

    /**
     * Get the message that needs to be logged for the given event name.
     *
     * @param string $eventName
     * @return string
     */
    public function getActivityDescriptionForEvent($eventName) {

        $content = 'Id: ' . $this->id . ' | Nombre: ' . $this->nombre;
        if ($eventName == 'created') {
            $this->update(['created_by' => Auth::user()->id]);
            return 'Rol ' . $content . ' fue creada';
        }

        if ($eventName == 'updated') {
            return 'Rol ' . $content . ' fue actualizada';
        }

        if ($eventName == 'deleted') {
            return 'Rol ' . $content . ' fue eliminada';
        }

        return '';
    }

    protected $table = 'formacionagentes';
    protected $fillable = ['user_id', 'editordeformacions_id', 'faq', 'faqbody'];

    public function formacionagentessusario() {
        return $this->hasOne('App\User');
    }

    public function editorformacionagentes() {
        return $this->belongsTo('App\Models\EditorDeFormacion');
    }

    public function commentarsformacionagentes() {
        return $this->hasMany('App\Models\Comentar', 'formacionagentes_id', 'id');
    }

}
