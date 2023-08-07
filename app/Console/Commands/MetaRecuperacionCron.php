<?php

namespace App\Console\Commands;


use App\Models\MetaRecuperacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;


class MetaRecuperacionCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meta:recuperacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera la meta de recuperacion Todos los meses';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Iniciar Creacion de Meta Recuperacion');

        $insertoNuevaMeta = false;

        $users = User::where([
            ["estado", "=", 1]
        ])->get();

        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

        foreach ($users as $user) {
            $meta_recuperacion = getMetaRecuperacionMensual($user->id, $inicioMesActual, $finMesActual);

            if (!$meta_recuperacion) {
                // crearMetaRecuperacionMensual();
                crearMetaRecuperacionMensualCuotas();
                $insertoNuevaMeta = true;


                $meta_recuperacion = MetaRecuperacion::where('estado', 1)
                    ->whereBetween('created_at', [$inicioMesActual . " 00:00:00",  $finMesActual . " 23:59:59"]);
            }
        }

        if ($insertoNuevaMeta) {
            Log::info("[Nueva Meta Recuperacion Agregada]");
            Log::info(json_encode($meta_recuperacion));
        }
        Log::info('Finalizar Creacion de Meta Recuperacion');

        crearMetaMensual(); // Tambien aprovecho el cron de recuperacion para validar la creacion de Metas
        cambiarClientesAListaNegraFacturasMora60_90();  // Tambien aprovecho el cron de recuperacion para validar los clientes en mora y los coloco en lista negra

    }
}
