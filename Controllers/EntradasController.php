<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\ConversacionesSeguimientos;
use App\Models\ConversacionesComentario;
use App\Models\UsarioDepartamentos;
use App\Models\Suministro;
use App\Models\ClienteRegistrado;
use App\Models\Delegaciones;
use App\Models\ResultadosComparador;
use App\Models\Comercializadora;
use App\Models\busquedasEntrada;
use App\Models\EmailPlantilla;


use Illuminate\Support\Facades\Auth;
use Session;
use DB;
use Validator;
use Response;
use Illuminate\Support\Facades\Redirect;
use DateTime;
use DateInterval;
use Datatables;

class EntradasController extends MainController {

    public function __construct(ConversacionesSeguimientos $ConversacionesSeguimientos) {
        parent::__construct();
        $this->middleware('permiso');
        \View::share('titulo_pagina', 'Entradas');
        $this->conversacionesseguimientos = $ConversacionesSeguimientos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //$usuario = ClienteRegistrado::find($id);
        $conversaciones_seguimientos_usarios = User::where('estado',1)->where('id', '<>', Auth::user()->id)->orderBy('name', 'asc')->get();
        $usariosdepartmentos = UsarioDepartamentos::orderBy('departmentname', 'asc')->get();
        $currenttime = explode(':', date('H:i'));
        $timeArr['hours'] = $currenttime[0];
        $timeArr['minutes'] = $currenttime[1];

        /*para redondear los minutos a multiplos de 5*/
        $min = round($timeArr['minutes'] / 5,0);
        $timeArr['minutes'] = $min * 5;
        $timeArr['minutes'] = str_pad($timeArr['minutes'], 2, "0", STR_PAD_LEFT);

        $comercializadoras = Comercializadora::orderBy('nombre_comercial')->get();
        $sql = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    });
        //dd($sql->toSql());

        $agentes = User::join('conversaciones_seguimientos','users.id','conversaciones_seguimientos.owned_by')->where(function($query) {
            $query->where('estado', 1);
            $query->where('rol_id', '<>', '20');
            if (Auth::user()->permiso_id == 1) {
                $query->where('created_by', '=', Auth::user()->id);
                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
            } elseif (Auth::user()->permiso_id == 2) {
                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
            } elseif (Auth::user()->permiso_id == 3) {
                $dele = array();
                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                foreach ($delegac as $deleg) {
                    $dele[] = $deleg['delegacion_id'];
                }
                $dele[] = Auth::user()->delegacion_id;
                $query->whereIn('delegacion_id', $dele);
            }
            $query->where('users.id', '<>', Auth::user()->id);
            $query->where('ticket_processing_status', '<>', 5);
       })->select('users.*')->orderBy('name')->distinct()->get();

        $ticket_allocationdepartment_1 =  ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_allocationdepartment', '=', '1')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_allocationdepartment_2 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_allocationdepartment', '=', '2')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_allocationdepartment_3 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_allocationdepartment', '=', '3')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_allocationdepartment_4 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_allocationdepartment', '=', '4')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_processing_status_1 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_processing_status', '=', '1')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_processing_status_2 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_processing_status', '=', '2')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_processing_status_3 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_processing_status', '=', '3')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_processing_status_4 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_processing_status', '=', '4')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_processing_status_5 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('ticket_processing_status', '=', '5')->where('ticket_status', '=', 1)->count();
        $nivel_1 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('nivel', '=', '1')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $nivel_2 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('nivel', '=', '2')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $nivel_3 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('nivel', '=', '3')->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->count();
        $ticket_propietario_2 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('owned_by',0)->where('ticket_processing_status', '<>', 5)->where('ticket_status', '=', 1)->count();
        $ticket_propietario_1 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('owned_by', '=', Auth::user()->id)->where('ticket_processing_status', '<>', 5)->where('ticket_status', '=', 1)->count();
        $ticket_propietario_3 = ConversacionesSeguimientos::whereIn('usuario_id', function ($q) {
                    $q->select('id')
                    ->from('clientes_registrados')
                    ->where(function($query) {
                            if (Auth::user()->permiso_id == 1) {
                                $query->where('created_by', '=', Auth::user()->id);
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 2) {
                                $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                            } elseif (Auth::user()->permiso_id == 3) {
                                $dele = array();
                                $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                                foreach ($delegac as $deleg) {
                                    $dele[] = $deleg['delegacion_id'];
                                }
                                $dele[] = Auth::user()->delegacion_id;
                                $query->whereIn('delegacion_id', $dele);
                            }
                        });
                    })->where('owned_by', '<>', Auth::user()->id)->where('owned_by','<>',0)->where('ticket_processing_status', '<>', 5)->where('ticket_status', '=', 1)->count();

        /*CONTRATOS*/
        $reservadas = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->select('suministro.cups')
                ->select('suministros.nombre_titular')
                ->select(DB::raw('(P1_anual + P2_anual + P3_anual + P4_anual + P5_anual + P6_anual) consumo'))
                ->leftJoin('suministros', function($join) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        $reservadas = $reservadas->whereNotNull('fechareservada');
        $reservadas = $reservadas->whereNull('contrato_enviado');
        $reservadas = $reservadas->whereNull('contrato_firmado');
        $reservadas = $reservadas->whereNull('enviado_comercializadora');
        $reservadas = $reservadas->whereNull('activada')->where('resultados_comparador.reserved_by',Auth::user()->id)->where('annulled_by',0)->get()->count();

        $contratos_enviados = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->leftJoin('suministros', function($join) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        $contratos_enviados = $contratos_enviados->whereNotNull('contrato_enviado');
        $contratos_enviados = $contratos_enviados->whereNull('contrato_firmado');
        $contratos_enviados = $contratos_enviados->whereNull('enviado_comercializadora');
        $contratos_enviados = $contratos_enviados->whereNull('activada')->where('resultados_comparador.reserved_by',Auth::user()->id)->where('annulled_by',0)->get()->count();

        $contratos_firmados = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->leftJoin('suministros', function($join) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        $contratos_firmados = $contratos_firmados->whereNotNull('contrato_firmado');
        $contratos_firmados = $contratos_firmados->whereNull('enviado_comercializadora');
        $contratos_firmados = $contratos_firmados->whereNull('activada')->where('resultados_comparador.reserved_by',Auth::user()->id)->where('annulled_by',0)->get()->count();


        $enviados_comercializadora = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->leftJoin('suministros', function($join) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        $enviados_comercializadora = $enviados_comercializadora->whereNotNull('enviado_comercializadora');
        $enviados_comercializadora = $enviados_comercializadora->whereNull('activada')->where('resultados_comparador.reserved_by',Auth::user()->id)->where('annulled_by',0)->get()->count();

        $activadas = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->leftJoin('suministros', function($join) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        $activadas = $activadas->whereNotNull('activada')->whereNull('desactivada')->where('resultados_comparador.reserved_by',Auth::user()->id)->where('annulled_by',0)->get()->count();

        $desactivadas = ResultadosComparador::query()
                        ->select('resultados_comparador.*')
                        ->leftJoin('suministros', function($join) {
                            $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                        })
                        ->leftJoin('comercializadora', function($join) {
                            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
                        })->where('annulled_by',0)->where('resultados_comparador.reserved_by',Auth::user()->id)->get()->count();

        $bajas = ResultadosComparador::query()
                        ->select('resultados_comparador.*')
                        ->leftJoin('suministros', function($join) {
                            $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                        })
                        ->leftJoin('comercializadora', function($join) {
                            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
                        })->where('annulled_by',0)
                        ->whereNotNull('activada')->whereNotNull('desactivada')->where('resultados_comparador.reserved_by',Auth::user()->id)
                        ->where('annulled_by',0)->get()->count();

        $anuladas = ResultadosComparador::query()
                        ->select('resultados_comparador.*')
                        ->leftJoin('suministros', function($join) {
                            $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                        })
                        ->leftJoin('comercializadora', function($join) {
                            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
                        })->where('annulled_by','>',0)->where('resultados_comparador.reserved_by',Auth::user()->id)->get()->count();


        $comercializadoras_filtro = ConversacionesSeguimientos::comercializadorasActivas();

        return view('entradas.index', ['usariosdepartmentos' => $usariosdepartmentos, 'conversaciones_seguimientos_usarios' => $conversaciones_seguimientos_usarios, 'timeArr' => $timeArr, 'ticket_allocationdepartment_1' => $ticket_allocationdepartment_1, 'ticket_allocationdepartment_2' => $ticket_allocationdepartment_2, 'ticket_allocationdepartment_3' => $ticket_allocationdepartment_3, 'ticket_allocationdepartment_4' => $ticket_allocationdepartment_4, 'ticket_processing_status_1' => $ticket_processing_status_1, 'ticket_processing_status_2' => $ticket_processing_status_2, 'ticket_processing_status_3' => $ticket_processing_status_3, 'ticket_processing_status_4' => $ticket_processing_status_4, 'ticket_processing_status_5' => $ticket_processing_status_5, 'nivel_1' => $nivel_1, 'nivel_2' => $nivel_2, 'nivel_3' => $nivel_3, 'ticket_propietario_3' => $ticket_propietario_3, 'ticket_propietario_2' => $ticket_propietario_2, 'ticket_propietario_1' => $ticket_propietario_1,'reservadas' => $reservadas, 'contratos_enviados' => $contratos_enviados, 'contratos_firmados' => $contratos_firmados, 'enviados_comercializadora' => $enviados_comercializadora, 'activadas' => $activadas, 'desactivadas' => $desactivadas, 'bajas' => $bajas, 'anuladas' => $anuladas, 'comercializadoras' => $comercializadoras, 'comercializadoras_filtro' => $comercializadoras_filtro, 'agentes' => $agentes ]);
    }

    public function searchentradas(Request $request) {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $fecha_inicial = '';
        $fecha_final = '';
        if(!empty($request->fecha)){
            $rango_fechas = explode('-',$request->fecha);
            $fecha_inicial = trim($rango_fechas[0]);
            $fecha_final = trim($rango_fechas[1]);
        }

        $ticket_processing_status = $request->get('ticket_processing_status');
        $ticket_allocationdepartment = $request->get('ticket_allocationdepartment');
        $nivel = $request->get('nivel');
        $ticket_propietario = $request->get('ticket_propietario');
        $alert_time = $request->get('alert_time');
        $order = $request->get('order');
        $comercializadora = $request->get('comercializadora');
        $agente = $request->get('agente');


        $conversacionesseguimientos_Query = $this->conversacionesseguimientos->whereIn('usuario_id', function ($q) {
            $q->select('id')
            ->from('clientes_registrados')
            ->where(function($query) {
                    if (Auth::user()->permiso_id == 1) {
                        $query->where('created_by', '=', Auth::user()->id);
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 2) {
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 3) {
                        $dele = array();
                        $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                        foreach ($delegac as $deleg) {
                            $dele[] = $deleg['delegacion_id'];
                        }
                        $dele[] = Auth::user()->delegacion_id;
                        $query->whereIn('delegacion_id', $dele);
                    }
                });
        });
        if(!empty($comercializadora)){
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('comercializadora_id', '=', $comercializadora);
        }
        if(!empty($agente)){
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('owned_by', $agente);
        }
        //dd($conversacionesseguimientos_Query->toSql());
        if (!empty($ticket_processing_status) && is_array($ticket_processing_status)) {
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('ticket_processing_status', $ticket_processing_status);
        }
        if (!empty($ticket_allocationdepartment)) {
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('ticket_allocationdepartment', $ticket_allocationdepartment);
        }
        if(!empty($fecha_inicial) && !empty($fecha_final)){
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereRaw('alert_time >= "'.implode('-',array_reverse(explode('/',$fecha_inicial))).'"');
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereRaw('alert_time <= "'.implode('-',array_reverse(explode('/',$fecha_final))).'"');
        }
        /* Columns Order */
        if (isset($order[0]['column']) && $order[0]['column'] != '') {
            if ($order[0]['column'] == 1) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('created_at', $order[0]['dir']);
            } else if ($order[0]['column'] == 2) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('updated_at', $order[0]['dir']);
            } else if ($order[0]['column'] == 3) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('created_by', $order[0]['dir']);
            } else if ($order[0]['column'] == 4) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('owned_by', $order[0]['dir']);
            } else if ($order[0]['column'] == 5) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('ticket_allocationdepartment', $order[0]['dir']);
            } else if ($order[0]['column'] == 6) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('ticket_processing_status', $order[0]['dir']);
            } else if ($order[0]['column'] == 7) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('nivel', $order[0]['dir']);
            } else if ($order[0]['column'] == 8) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('alert_time', $order[0]['dir']);
            } else if ($order[0]['column'] == 9) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('usuario_id', $order[0]['dir']);
            } else if ($order[0]['column'] == 10) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('note', $order[0]['dir']);
            } else {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('created_at', $order[0]['dir']);
            }
        } else {
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->orderBy('created_at', $order[0]['dir']);
        }
        if (!empty($ticket_processing_status) && in_array(5, $ticket_processing_status)) {
            $conversacionesseguimientos = $conversacionesseguimientos_Query->where('ticket_status', '=', 1)->where('ticket_processing_status', 5)->get();
        }
        else{
            $conversacionesseguimientos = $conversacionesseguimientos_Query->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->get();
        }

        return \Datatables::of($conversacionesseguimientos)
                ->addColumn('ticket_status', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->ticket_status) {
                        if ($conversacionesseguimientos->ticket_processing_status) {
                            switch ($conversacionesseguimientos->ticket_processing_status):
                                case 1:
                                    $statusmsg = 'Sin atender';
                                    break;
                                case 2:
                                    $statusmsg = 'Comecializadora';
                                    break;
                                case 3:
                                    $statusmsg = 'Interno';
                                    break;
                                case 4:
                                    $statusmsg = 'Cliente';
                                    break;
                                case 5:
                                    $statusmsg = 'Cerrado';
                                    break;

                            endswitch;
                            return $statusmsg;
                        } else {
                            return '';
                        }
                    } else {
                        return '';
                    }
                })
                ->addColumn('contact_id', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->usuario_id) {
                        return $conversacionesseguimientos->usuario_id;
                    }
                })->addColumn('created_at', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->created_at) {
                        return date('d/m/Y H:i', strtotime($conversacionesseguimientos->created_at));
                    }
                })->addColumn('updated_at', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->updated_at) {
                        return date('d/m/Y H:i', strtotime($conversacionesseguimientos->updated_at));
                    }
                })->addColumn('created_by', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->created_by) {
                        $user = User::find($conversacionesseguimientos->created_by);
                        return $user->name . ' ' . $user->lastname;
                    }
                })->addColumn('owned_by', function ($conversacionesseguimientos) {
                    if (!empty($conversacionesseguimientos->owned_by)) {
                        $user = User::find($conversacionesseguimientos->owned_by);
                        if (isset($user->name) && isset($user->lastname)) {
                            return $user->name . ' ' . $user->lastname;
                        } else {
                            return '';
                        }
                    } else {
                        return 'Sin especificar';
                    }
                })->addColumn('ticket_allocationdepartment', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->ticket_allocationdepartment) {
                        $department = UsarioDepartamentos::find($conversacionesseguimientos->ticket_allocationdepartment);
                        if ($department) {
                            return $department->departmentname;
                        }
                    }
                })->addColumn('ticket_processing_status', function ($conversacionesseguimientos) {

                    if ($conversacionesseguimientos->ticket_processing_status) {
                        switch ($conversacionesseguimientos->ticket_processing_status):
                            case 1:
                                $statusmsg = 'Sin atender';
                                break;
                            case 2:
                                $statusmsg = 'Comecializadora';
                                break;
                            case 3:
                                $statusmsg = 'Interno';
                                break;
                            case 4:
                                $statusmsg = 'Cliente';
                                break;
                            case 5:
                                $statusmsg = 'Cerrado';
                                break;

                        endswitch;
                        return $statusmsg;
                    } else {
                        return '';
                    }
                })->addColumn('proxima_intervencion', function ($conversacionesseguimientos) {
                    if (isset($conversacionesseguimientos->alert_time) && !empty($conversacionesseguimientos->alert_time)) {
                        return date('d/m/Y H:i', strtotime($conversacionesseguimientos->alert_time));
                    }else{
                        return '';
                    }
                })->addColumn('nivel', function ($conversacionesseguimientos) {

                    if ($conversacionesseguimientos->nivel) {
                        switch ($conversacionesseguimientos->nivel):
                            case 1:
                                $statusmsg = 'Alto';
                                break;
                            case 2:
                                $statusmsg = 'Muy alto';
                                break;
                            case 3:
                                $statusmsg = 'El más alto';
                                break;

                        endswitch;
                        return $statusmsg;
                    } else {
                        return '';
                    }
                })->addColumn('cantidad_tickets_contacto', function ($conversacionesseguimientos) {
                    if (isset($conversacionesseguimientos->contacto->id)) {
                        return $conversacionesseguimientos->contacto->tickets_abiertos->count();
                    } 
                    else{
                        return 0;
                    }
                })->addColumn('alert_time', function ($conversacionesseguimientos) {
                    return date('d/m/Y H:i', strtotime($conversacionesseguimientos->alert_time));
                })->addColumn('usuario_id', function ($conversacionesseguimientos) {
                    if ($conversacionesseguimientos->usuario_id) {
                        $client = ClienteRegistrado::find($conversacionesseguimientos->usuario_id);
                        return '<a href="' . route('ver-clienteregistrados', $conversacionesseguimientos->usuario_id) . '" target="_blank">' . $client->name . '</a>';
                    }
                })->addColumn('note', function ($conversacionesseguimientos) {
                    $note_comments = '';
                    if ($conversacionesseguimientos->conversationnote) {
                        $cuser = $conversacionesseguimientos->creador;
                        $note_comments .= '<div><span>" ' . $conversacionesseguimientos->conversationnote . ' "</span><br><strong>' . date("d/m/Y H:i", strtotime($conversacionesseguimientos->created_at)).'<i> - ' . $cuser->name . ' ' . $cuser->lastname . '</i></strong>';
                        if ($conversacionesseguimientos->comment_status == 1) {
                            $comments = $conversacionesseguimientos->conversacionesseguimientoscomentario;

                            $note_comments .= '<br>';
                            $note_comments .= '<ul class="list-group">';
                            if ($comments->count() > 0) {
                                $i = 1;
                                foreach ($comments as $ck => $comment) {
                                    $ocultar = '';
                                    $ocultar_css = '';
                                    if($i > 3 ){
                                        $ocultar = 'style="display:none;"';
                                        $ocultar_css = 'ocultar-comentario';
                                    }

                                    if($i == 4 ){
                                        $note_comments .= '
                                        <li class="list-group-item">
                                            Ver todos<a href="#" style="float: right;" class="ver-mas-comentarios"><i class="fa fa-chevron-down"></i></a>
                                        </li>';
                                    }

                                    $cuser = $comment->creador;
                                    $note_comments .= '<li class="list-group-item '.$ocultar_css.'" '.$ocultar.'>" ' . $comment->comentario . ' "<br><strong>' . date("d/m/Y H:i", strtotime($comment->created_at)).'<i> - ' . $cuser->name . ' ' . $cuser->lastname . '</i></strong></li>';
                                    $i++;
                                }
                            }
                            $note_comments .= '</ul>';
                        }
                        $note_comments .= '</div>';
                    }
                    return $note_comments;
                })->addColumn('actions', function ($conversacionesseguimientos) {
                    $actions = '';
                    $actions .= '<br><a class="btn btn-primary btn-xs" target="_blank" href="'.route('ver-tickets',$conversacionesseguimientos->id).'">Ver ticket</a>';
                    return $actions;
                })->escapeColumns([])->make(true);
    }

    public function searchEntradasCalendario(Request $request) {
        $ticket_processing_status = $request->get('ticket_processing_status');
        $ticket_allocationdepartment = $request->get('ticket_allocationdepartment');
        $nivel = $request->get('nivel');
        $ticket_propietario = $request->get('ticket_propietario');
        $alert_time = $request->get('alert_time');
        $comercializadora = $request->get('comercializadora');
        $agente = $request->get('agente');
        $conversacionesseguimientos_Query = $this->conversacionesseguimientos->whereIn('usuario_id', function ($q) {
            $q->select('id')
            ->from('clientes_registrados')
            ->where(function($query) {
                    if (Auth::user()->permiso_id == 1) {
                        $query->where('created_by', '=', Auth::user()->id);
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 2) {
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 3) {
                        $dele = array();
                        $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                        foreach ($delegac as $deleg) {
                            $dele[] = $deleg['delegacion_id'];
                        }
                        $dele[] = Auth::user()->delegacion_id;
                        $query->whereIn('delegacion_id', $dele);
                    }
                });
        });

        if(!empty($comercializadora)){
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('comercializadora_id', '=', $comercializadora);
        }
        if(!empty($agente)){
            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('owned_by', $agente);
        }

        if (!empty($ticket_processing_status) && in_array(5, $ticket_processing_status)) {
            $conversacionesseguimientos = $conversacionesseguimientos_Query->where('ticket_status', '=', 1)->where('ticket_processing_status', 5)->whereIn('usuario_id', function ($q) {
            $q->select('id')
            ->from('clientes_registrados')
            ->where(function($query) {
                    if (Auth::user()->permiso_id == 1) {
                        $query->where('created_by', '=', Auth::user()->id);
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 2) {
                        $query->where('delegacion_id', '=', Auth::user()->delegacion_id);
                    } elseif (Auth::user()->permiso_id == 3) {
                        $dele = array();
                        $delegac = Auth::user()->delegaciones()->select('delegacion_id')->get()->toArray();
                        foreach ($delegac as $deleg) {
                            $dele[] = $deleg['delegacion_id'];
                        }
                        $dele[] = Auth::user()->delegacion_id;
                        $query->whereIn('delegacion_id', $dele);
                    }
                });
            })->get();
        } else {
            if (!empty($ticket_processing_status) && is_array($ticket_processing_status)) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('ticket_processing_status', $ticket_processing_status);
            }
            if (!empty($ticket_allocationdepartment)) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('ticket_allocationdepartment', $ticket_allocationdepartment);
            }
            /*Se comenta porque se quito el filtro de nivel
            if (!empty($nivel) && is_array($nivel)) {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereIn('nivel', $nivel);
            }*/
            /*Se comenta porque se quito el filtro de tipo de propietario
            if (!empty($ticket_propietario)) {
                if ($ticket_propietario == 2) {
                    $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by',0);
               } elseif ($ticket_propietario == 1) {
                    $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by', Auth::user()->id);
                } elseif ($ticket_propietario == 3) {
                    $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by', '<>', 0);
                    $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by', '<>', Auth::user()->id);
                } else {
                    $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by', Auth::user()->id);
                }
            } else {
                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->where('owned_by', Auth::user()->id);
            }*/
            if (!empty($alert_time) && is_array($alert_time)) {
                if (!empty($alert_time['value'])) {
                    switch ($alert_time['value']):

                        case 1:
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereDate('alert_time', '=', date('Y-m-d'))->where('alert_status', '=', 1);
                            break;
                        case 2:
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereDate('alert_time', '=', date('Y-m-d', (strtotime('-1 day', strtotime(date('Y-m-d'))))))->where('alert_status', '=', 1);
                            break;
                        case 3:
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereDate('alert_time', '=', date('Y-m-d', (strtotime('+1 day', strtotime(date('Y-m-d'))))))->where('alert_status', '=', 1);
                            break;
                        case 4:
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereDate('alert_time', '<', date('Y-m-d'))->where('alert_status', '=', 1);
                            break;
                        case 5:
                            $currentweek = $this->currentweekstartandendday();
                            $from = $currentweek['start'];
                            $to = $currentweek['end'];
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereBetween('alert_time', [$from, $to])->where('alert_status', '=', 1);
                            break;
                        case 6:
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereMonth('alert_time', '=', date('m'))->where('alert_status', '=', 1);
                            break;
                        case 7:
                            $from = date('Y-m-d');
                            $to = date('Y-m-d', (strtotime('+3 month', strtotime(date('Y-m-d')))));
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereBetween('alert_time', [$from, $to])->where('alert_status', '=', 1);
                            break;
                        case 8:
                            $from = date('Y-m-d');
                            $to = date('Y-m-d', (strtotime('+6 month', strtotime(date('Y-m-d')))));
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereBetween('alert_time', [$from, $to])->where('alert_status', '=', 1);
                            break;
                        case 9:
                            $from = date('Y-m-d');
                            $to = date('Y-m-d', (strtotime('+12 month', strtotime(date('Y-m-d')))));
                            $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereBetween('alert_time', [$from, $to])->where('alert_status', '=', 1);
                            break;
                        case 10:
                            if ($alert_time['udate']) {
                                $conversacionesseguimientos_Query = $conversacionesseguimientos_Query->whereDate('alert_time', '=', date('Y-m-d', (strtotime($alert_time['udate']))))->where('alert_status', '=', 1);
                            }
                            break;

                    endswitch;
                }
            }
            //print_r($conversacionesseguimientos_Query->toSql());exit();
            $conversacionesseguimientos = $conversacionesseguimientos_Query->where('ticket_status', '=', 1)->where('ticket_processing_status', '<>', 5)->get();
        }

        $eventos = array();

        foreach ($conversacionesseguimientos as $conversacion) {
            if ($conversacion->usuario_id) {
                $client = ClienteRegistrado::find($conversacion->usuario_id);
                $title = $client->name . ' ' . $client->lastname;
            } else {
                $title = 'Sin contacto';
            }
            $currenttime = explode(' ', $conversacion->alert_time);
            if ($currenttime[0] == '0000-00-00') {
                $currenttime[0] = date('Y-m-d');
            }
            $alertdate = date('d/m/Y', strtotime($currenttime[0]));
            $hora = explode(':', $currenttime[1]);
            if (hourIsBetween('00:00', '06:59', $hora[0] . ':' . $hora[1])) {
                $currenttime[1] = '07:00:00';
            }

            $conversacion->alert_time = $currenttime[0] . ' ' . $currenttime[1];
            $currenttime = explode(':', $currenttime[1]);
            $timeArr['hours'] = $currenttime[0];
            $timeArr['minutes'] = $currenttime[1];

            /*para redondear los minutos a multiplos de 5*/
            $min = round($timeArr['minutes'] / 5,0);
            $timeArr['minutes'] = $min * 5;
            $timeArr['minutes'] = str_pad($timeArr['minutes'], 2, "0", STR_PAD_LEFT);

            $comments = DB::table('conversaciones_comentarios')
                    ->where('conversaciones_seguimientos_id', $conversacion->id)
                    ->orderBy('id','desc')
                    ->get();
            $tabla_comentarios = '';
            $i = 0;
            foreach ($comments as $comment) {
                $i++;
                $ocultar = '';
                $ocultar_css = '';
                if($i > 3 ){
                    $ocultar = 'style="display:none;"';
                    $ocultar_css = 'ocultar-comentario';
                }
                $cuser = User::find($comment->user_id);

                if($i == 4 ){
                    $tabla_comentarios .= '
                    <tr class="text-center">
                        <td class="all">Ver todos<a href="#" id="ver-mas-copmentarios" style="float: right;"><i class="fa fa-chevron-down"></i></a></td>
                    </tr>';
                }
                $tabla_comentarios .= '
                <tr class="text-center '.$ocultar_css.'" '.$ocultar.'>
                    <td class="all"><strong>' . $cuser->name . ' ' . $cuser->lastname . ' - ' . date("d/m/Y H:i", strtotime($comment->created_at)) . '</strong><br>' . $comment->comentario . '</td>
                </tr>';
            }

            $tabla_suministros = '';
            foreach ($conversacion->suministros as $sum) {
                $suministro = Suministro::find($sum->suministro_id);
                if(isset($suministro->cups)){
                    $tabla_suministros .= '
                    <tr class="text-center">
                        <td class="all">' . $suministro->cups . '</td>
                        <td class="all">' . $suministro->direccion_suministro . ' ' . $suministro->aclaratorio_suministro . '</td>
                        <td class="all">' . $suministro->poblacion_suministro . '</td>
                        <td class="all">' . ((isset($suministro->tarifaacceso->tarifa)) ? $suministro->tarifaacceso->tarifa : '') . '</td>
                        <td class="all">' . $suministro->total_anual . '</td>
                        <td class="all">' . $suministro->nombre_titular . ' ' . $suministro->apellido_titular . '</td>
                        <td class="all">' . ((isset($suministro->comercializadora->nombre_comercial)) ? $suministro->comercializadora->nombre_comercial : '') . '</td>
                        <td class="all">' . $suministro->fecha_activacion . '</td>
                        <td class="all">
                            <a target="_blank" href="' . route("ver-suministro", ["id" => $suministro->id]) . '" class="btn blue">
                                <i class="fa fa-search"></i>
                            </a>
                        </td>
                    </tr>';
                }
            }

            $user = ClienteRegistrado::find($conversacion->usuario_id);
            $main_user = User::find($conversacion->created_by);
            $owner = User::find($conversacion->owned_by);
            if(isset($user->created_by)){
                $contact_owner = User::find($user->created_by);
                if(isset($contact_owner->delegacion_id)){
                    $contact_delegacion = Delegaciones::find($contact_owner->delegacion_id);
                }
                else{
                    $contact_delegacion = 0;
                }
            }
            $cant_suministros = Suministro::where('contacto_id', $conversacion->usuario_id)->count();
            $fecha_creacion = new DateTime($conversacion->created_at);

            if($conversacion->owned_by == 0){
                $conversacion->ticket_propietario = 2;
            }
            elseif($conversacion->owned_by == Auth::user()->id){
                $conversacion->ticket_propietario = 1;
            }
            elseif($conversacion->owned_by != Auth::user()->id){
                $conversacion->ticket_propietario = 3;
            }
            $color = ($conversacion->appointment_status == 1) ? 'rgb(0, 128, 0)' : '#3598dc';
            $eventos[] = [
                'id' => $conversacion->id,
                'title' => $title,
                'start' => $conversacion->alert_time,
                'end' => $conversacion->alert_time,
                'color' => $color,
                'ticket_allocationdepartment' => $conversacion->ticket_allocationdepartment,
                'ticket_processing_status' => $conversacion->ticket_processing_status,
                'ticket_propietario' => $conversacion->ticket_propietario,
                'conversaciones_seguimientos_usarios' => $conversacion->owned_by,
                'nivel' => $conversacion->nivel,
                'appointment_status' => $conversacion->appointment_status,
                'alertdate' => $alertdate,
                'alerttimeh' => $timeArr['hours'],
                'alerttimem' => $timeArr['minutes'],
                'conversationnote' => $conversacion->conversationnote,
                'alert_status' => $conversacion->alert_status,
                'comercializadora_id' => $conversacion->comercializadora_id,
                'fecha_creacion' => $fecha_creacion->format('d/m/Y H:i:s'),
                'tabla_comentarios' => $tabla_comentarios,
                'tabla_suministros' => $tabla_suministros,
                'usuario_nombre' => (isset($main_user->name) && isset($main_user->lastname)) ? $main_user->name . ' ' . $main_user->lastname : '',
                'prop_nombre' => (isset($owner->name) && isset($owner->lastname)) ? $owner->name . ' ' . $owner->lastname : '',
                'prop_id' => (isset($owner->id)) ? $owner->id : '',
                'contacto_id' => (isset($user->id)) ? $user->id : '',
                'contacto_nombre' => (isset($user->name) && isset($user->lastname)) ? $user->name . ' ' . $user->lastname : '',
                'contacto_email' => (isset($user->email)) ? $user->email : '',
                'contacto_prop' => (isset($contact_owner->name) && isset($contact_owner->lastname)) ? $contact_owner->name . ' ' . $contact_owner->lastname : '',
                'contacto_delegacion' => (isset($contact_delegacion->nombre)) ? $contact_delegacion->nombre : '',
                'contacto_movil' => (isset($user->mobile)) ? $user->mobile : '',
                'contacto_fijo' => (isset($user->phone)) ? $user->phone : '',
                'contacto_suministros' => (isset($user->suministros_luz) && isset($user->pago_anual_suministros)) ? $user->suministros_luz . ', ' . $user->pago_anual_suministros . ', ' . $cant_suministros : '',
            ];
        }
        return $eventos;
    }

    public function currentweekstartandendday() {
        $currentweekArr = [];
        $monday = strtotime("last monday");
        $monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;

        $sunday = strtotime(date("Y-m-d", $monday) . " +6 days");

        $currentweekArr['start'] = date("Y-m-d", $monday);
        $currentweekArr['end'] = date("Y-m-d", $sunday);

        return $currentweekArr;
    }

    public function archivetickets() {
        $tickets = $this->conversacionesseguimientos->with('conversacionesseguimientoscomentario')->where('ticket_processing_status', '=', 5)->where('ticket_status', '=', 1)->whereDate('updated_at', '<=', date('Y-m-d', strtotime('-10 day', strtotime(date('Y-m-d')))))->get();
        if (!$tickets->isEmpty()) {
            foreach ($tickets as $csk => $ticket) {
                $data = array(
                    'ticket_status' => 0,
                    'can_edit' => 0,
                    'can_comment' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                );
                $conversacionesseguimientos = $this->conversacionesseguimientos->find($ticket->id);
                $conversacionesseguimientos->update($data);
            }
            echo 'script ran successfully';
        }
    }

    public function updateFecha(Request $request) {
        $conversacion_seguimiento = ConversacionesSeguimientos::find($request->id);
        $notification_id = $conversacion_seguimiento->notificaciones_id;
        $conversacion_seguimiento->update($request->all());

        if ($notification_id != 0) {
            DB::table('notificaciones')
                    ->where('id', $notification_id)
                    ->update(['notifytime' => $request->input('alert_time'), 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $data = array(
            'comentario' => 'Fecha actualizada a '.$request->input('alert_time'),
            'user_id' => Auth::user()->id,
            'conversaciones_seguimientos_id' => $request->id,
            'status' => 1,
        );
        $conversacionesseguimientos = ConversacionesComentario::create($data);

        return response('fecha actualizada correctamente', 200);
    }

    public function getResultadosComparador(Request $request) {
        $data =$request->all();
        $resultados = ResultadosComparador::query()
                ->select('resultados_comparador.*')
                ->leftJoin('suministros', function($join)use($request) {
                    $join->on('resultados_comparador.suministro_id', '=', 'suministros.id');
                })
                ->leftJoin('comercializadora', function($join)use($request) {
            $join->on('resultados_comparador.comercializadora_id', '=', 'comercializadora.idcomercializadora');
        });
        if (isset($request->palabra) && $request->palabra != '') {
            $resultados = $resultados->where(function($query) use ($request) {
                $query->orWhere('suministros.cups', 'like', '%' . $request->palabra . '%')
                ->orWhere('suministros.nombre_titular', 'like', '%' . $request->palabra . '%')
                ->orWhere('suministros.apellido_titular', 'like', '%' . $request->palabra . '%')
                ->orWhere('comercializadora.nombre', 'like', '%' . $request->palabra . '%')
                ->orWhere('comercializadora.nombre_comercial', 'like', '%' . $request->palabra . '%');
            });
        }
        if (isset($request->idcomercializadora) && $request->idcomercializadora != '') {
            $resultados = $resultados->where('comercializadora.idcomercializadora',$request->idcomercializadora);
        }
        if ($request->reservadas == 1) {
            $resultados = $resultados->whereNotNull('fechareservada');
            $resultados = $resultados->whereNull('contrato_enviado');
            $resultados = $resultados->whereNull('contrato_firmado');
            $resultados = $resultados->whereNull('enviado_comercializadora');
            $resultados = $resultados->whereNull('activada');
        }
        if ($request->contratos_enviados == 1) {
            $resultados = $resultados->whereNotNull('contrato_enviado');
            $resultados = $resultados->whereNull('contrato_firmado');
            $resultados = $resultados->whereNull('enviado_comercializadora');
            $resultados = $resultados->whereNull('activada');
        }
        if ($request->contratos_firmados == 1) {
            $resultados = $resultados->whereNotNull('contrato_firmado');
            $resultados = $resultados->whereNull('enviado_comercializadora');
            $resultados = $resultados->whereNull('activada');
        }
        if ($request->enviados_comercializadora == 1) {
            $resultados = $resultados->whereNotNull('enviado_comercializadora');
            $resultados = $resultados->whereNull('activada');
        }
        if ($request->activadas == 1) {
            $resultados = $resultados->whereNotNull('activada');
            $resultados = $resultados->whereNull('desactivada');
        }
        if ($request->bajas == 1) {
            $resultados = $resultados->whereNotNull('activada');
            $resultados = $resultados->whereNotNull('desactivada');
        }
        if (isset($request->anuladas) && $request->anuladas == 1) {
            $resultados = $resultados->where('annulled_by','>',0);
        }
        else{
            $resultados = $resultados->where('annulled_by',0);
        }

        if (isset($data['order'][0]['column']) && $data['order'][0]['column'] != '') {
            if ($data['order'][0]['column'] == 8) {
                $resultados = $resultados->orderBy('created_at', $data['order'][0]['dir']);
            } else if ($data['order'][0]['column'] == 9) {
                $resultados = $resultados->orderBy('fechareservada', $data['order'][0]['dir']);
            } else if ($data['order'][0]['column'] == 10) {
                $resultados = $resultados->orderBy('contrato_enviado', $data['order'][0]['dir']);
            }else if ($data['order'][0]['column'] == 11) {
                $resultados = $resultados->orderBy('contrato_firmado', $data['order'][0]['dir']);
            }else if ($data['order'][0]['column'] == 12) {
                $resultados = $resultados->orderBy('enviado_comercializadora', $data['order'][0]['dir']);
            }else if ($data['order'][0]['column'] == 13) {
                $resultados = $resultados->orderBy('activada', $data['order'][0]['dir']);
            }
        }
        $resultados = $resultados->where('resultados_comparador.reserved_by',Auth::user()->id)->get();
        return Datatables::of($resultados)
            ->addColumn('suministro', function ($item) {
                if (isset($item->suministro->cups)) {
                    return '<a href="' . route('ver-suministro', $item->suministro->id) . '" target="_blank">' . $item->suministro->cups . '</a>';
                } else {
                    return '';
                }
            })
            ->addColumn('titular', function ($item) {
                if (isset($item->suministro->nombre_titular)) {
                    return $item->suministro->nombre_titular . ' ' . $item->suministro->apellido_titular;
                } else {
                    return '';
                }
            })
            ->addColumn('consumo', function ($item) {
                if (isset($item->suministro)) {
                    $consumo = (float) $item->suministro->P1_anual + (float) $item->suministro->P2_anual + (float) $item->suministro->P3_anual + (float) $item->suministro->P4_anual + (float) $item->suministro->P5_anual + (float) $item->suministro->P6_anual;
                    return $consumo;
                } else {
                    return '';
                }
            })
            ->addColumn('comercializadora', function ($item) {
                return (isset($item->comercializadora->nombre_comercial)) ? $item->comercializadora->nombre_comercial : '';
            })
            ->addColumn('oferta', function ($item) {
                if (isset($item->oferta->codigo_unico)) {
                    return '<a href="' . route('ver-oferta', $item->oferta->id) . '" target="_blank">' . $item->oferta->codigo_unico . '</a>';
                } else {
                    return '';
                }
            })
            ->addColumn('fechareservada', function ($item) {
                if (empty($item->fechareservada)) {
                    if (isset($item->suministro)) {
                        $oferta = json_decode($item->resultado);
                        $oferta = (array) $oferta;
                        return '';
                    }
                } else {
                    $fecha = new DateTime($item->fechareservada);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('publicada', function ($item) {
                $fecha = new DateTime($item->created_at);
                return $fecha->format('d/m/Y H:i:s');
            })
            ->addColumn('contrato_enviado', function ($item) {
                if (empty($item->contrato_enviado)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->contrato_enviado);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('contrato_firmado', function ($item) {
                if (empty($item->contrato_firmado)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->contrato_firmado);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('contrato_enviado_tiempo', function ($item) {
                if (empty($item->contrato_enviado)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->contrato_enviado);
                    $fecha_actual = new DateTime();

                    $interval = $fecha->diff($fecha_actual);
                    //$hours = $interval->h;
                    //$hours = $hours + ($interval->days*24);
                    return $interval->days. ' dias';
                }
            })
            ->addColumn('enviado_comercializadora', function ($item) {
                if (empty($item->enviado_comercializadora)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->enviado_comercializadora);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('activada', function ($item) {
                if (empty($item->activada)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->activada);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('desactivada', function ($item) {
                if (empty($item->desactivada)) {
                    return '';
                } else {
                    $fecha = new DateTime($item->desactivada);
                    return $fecha->format('d/m/Y H:i:s');
                }
            })
            ->addColumn('url', function ($item) {
                return '<a href="' . url('/comparador/compartir/' . \Hashids::encode($item->historial_id) . '/' . \Hashids::encode($item->id)) . '" target="_blank">Ver</a>';
            })
            ->setRowClass(function ($item) {
                return $item->color;
            })->escapeColumns([])->make(true);
    }

    public function storeSearch(Request $request)
    {   
        $data = [
            'nombrebusqueda' => $request->nombrebusqueda,
            'user_id' => Auth::user()->id,
            'ticket_allocationdepartment' => (isset($request->ticket_allocationdepartment))?implode(',',$request->ticket_allocationdepartment):NULL,
            'ticket_processing_status' => (isset($request->ticket_processing_status))?implode(',',$request->ticket_processing_status):NULL,
            'ticket_propietario' => (isset($request->ticket_propietario))?$request->ticket_propietario:NULL,
            'agentes_filter1' => (isset($request->agentes_filter1))?implode(',',$request->agentes_filter1):NULL,
            'comercializadora_filter1' => (isset($request->comercializadora_filter1))?$request->comercializadora_filter1:NULL,
        ];
        $busqueda = busquedasEntrada::create($data);
        Session::flash('flash_message', 'El filtro se ha guardado correctamente.');
    }

    public function updateSearch(Request $request)
    {   
        $data = [
            'nombrebusqueda' => $request->editnombrebusqueda,
            'ticket_allocationdepartment' => (isset($request->ticket_allocationdepartment))?implode(',',$request->ticket_allocationdepartment):NULL,
            'ticket_processing_status' => (isset($request->ticket_processing_status))?implode(',',$request->ticket_processing_status):NULL,
            'ticket_propietario' => (isset($request->ticket_propietario))?$request->ticket_propietario:NULL,
            'agentes_filter1' => (isset($request->agentes_filter1))?implode(',',$request->agentes_filter1):NULL,
            'comercializadora_filter1' => (isset($request->comercializadora_filter1))?$request->comercializadora_filter1:NULL,
        ];
        $busqueda = busquedasEntrada::find($request->id_busqueda);
        $busqueda->update($data);
        Session::flash('flash_message', 'El filtro se ha guardado correctamente.');
    }

    public function destroySearch(Request $request)
    {   
        $busqueda = busquedasEntrada::find($request->id_busqueda);
        $busqueda->delete();
        Session::flash('flash_message', 'El filtro se ha eliminado correctamente.');
    }

    public function listSavedSearch(Request $request)
    {   
        $busquedas = busquedasEntrada::where('user_id',Auth::user()->id)->get();
        if($request->origen == 1){
            return view('entradas.partials.listSavedSearch', ['busquedas' => $busquedas]); 
        }
        elseif($request->origen == 2){
            return view('entradas.partials.listSavedSearch1', ['busquedas' => $busquedas]); 
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $ticket = ConversacionesSeguimientos::find($id);
        if(!isset($request->comentarios_list)){
            $usariosdepartmentos = UsarioDepartamentos::orderBy('departmentname', 'asc')->get();
            $conversaciones_seguimientos_usarios = User::where('estado',1)->where('id', '<>', Auth::user()->id)->orderBy('name', 'asc')->get();
            $comercializadoras = Comercializadora::orderBy('nombre_comercial')->get();
            $firma = '';
            $firma .= '<p style="font-size:14px;margin: 0;" align="">&nbsp;</p>';
            $firma .= '<p style="font-size:14px;margin: 0;" align="">&nbsp;</p>';
            $firma .= '<h3>'.Auth::user()->name.' '.Auth::user()->lastname.'</h3>';
            $firma .= '<a target="_blank" rel="">'.Auth::user()->email_smtp.'</a><br>';
            $firma .= 'Tel. '.Auth::user()->mobile.'<br>';
            $firma .= '<a href="http://www.tarifasdeluz.com" target="_blank" rel="">www.tarifasdeluz.com</a>';


            $firma_comercial = '';
            $firma_comercial .= '<p style="font-size:14px;margin: 0;" align="">&nbsp;</p>';
            $firma_comercial .= '<p style="font-size:14px;margin: 0;" align="">&nbsp;</p>';
            $firma_comercial .= '<h3>'.Auth::user()->name.' '.Auth::user()->lastname.'</h3>';
            $firma_comercial .= '<a target="_blank" rel="">comercial@tarifasdeluz.com</a><br>';
            $firma_comercial .= 'Tel. '.Auth::user()->mobile.'<br>';
            $firma_comercial .= '<a href="http://www.tarifasdeluz.com" target="_blank" rel="">www.tarifasdeluz.com</a>';

            $plantillas = EmailPlantilla::all()->sortBy("titulo");

            return view('entradas.mostrar', compact('ticket','firma','usariosdepartmentos','conversaciones_seguimientos_usarios','comercializadoras','plantillas','firma_comercial'));
        }
        else{
            return view('entradas.partials.comentarios', compact('ticket'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ticketInfo($id, Request $request)
    {
        $ticket = ConversacionesSeguimientos::find($id);
        return view('entradas.partials.ticketinfo', compact('ticket'));
    }
}
