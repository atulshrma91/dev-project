<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogsActivityInterface;
use Spatie\Activitylog\LogsActivity;

class EditorDeFormacion extends Model implements LogsActivityInterface {

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

    protected $table = 'editordeformacions';
    protected $fillable = ['user_id', 'faqcategoria'];

    public function formacionagenteseditor() {
        return $this->hasMany('App\Models\FormacionAgentes', 'editordeformacions_id', 'id');
    }

}
