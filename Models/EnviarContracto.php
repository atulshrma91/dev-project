<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogsActivityInterface;
use Spatie\Activitylog\LogsActivity;

class EnviarContracto extends Model implements LogsActivityInterface {

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

    protected $table = 'enviar_contractos';
    protected $fillable = ['user_id', 'territorio_id', 'suministro_id', 'email', 'mobile_no', 'codigo_de_verificacion', 'vencimiento', 'estado','pdflink','contracturl','tipopersona','is_agent_cancelled','oferta_id', 'reserva_id','comercializadora_id'];

    public function suministro() {
        return $this->belongsTo('App\Models\Suministro');
    }

    /**
     * Get the tipo comision that owns the suministro.
     */
    public function reserva()
    {
        return $this->belongsTo('App\Models\Reserva', 'reserva_id');
    }

    /**
     * Get the tipo comision that owns the suministro.
     */
    public function comercializadora()
    {
        return $this->belongsTo('App\Models\Comercializadora', 'comercializadora_id');
    }

    /**
     * Get the tipo comision that owns the suministro.
     */
    public function oferta()
    {
        return $this->belongsTo('App\Models\Oferta', 'oferta_id');
    }
}
