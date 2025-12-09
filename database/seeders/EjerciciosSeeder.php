<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Materia;
use App\Models\Tema;
use App\Models\Subtema;

class EjerciciosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ESTRUCTURA BÁSICA (Aseguramos que existan los padres)
        $materia = Materia::firstOrCreate(
            ['titulo' => 'Programación'],
            ['descripcion' => 'Fundamentos de lógica y código']
        );

        $tema = Tema::firstOrCreate(
            ['titulo' => 'Fundamentos', 'materia_id' => $materia->id],
            ['descripcion' => 'Conceptos básicos del lenguaje']
        );

        // 2. SUBTEMA 1: VARIABLES
        $subVariable = Subtema::firstOrCreate(
            ['titulo' => '¿Qué es una variable?', 'tema_id' => $tema->id],
            ['descripcion' => 'Definición y uso de memoria', 'informacion' => 'Una variable es un contenedor para almacenar datos...']
        );

        // LIMPIAMOS EJERCICIOS ANTERIORES DE ESTE SUBTEMA PARA NO DUPLICAR AL PROBAR
        DB::table('ejercicios')->where('subtema_id', $subVariable->id)->delete();

        // 3. INSERTAR LOS 4 EJERCICIOS (2 Arrastrar, 2 Relacionar)
        $ejercicios = [
            // ==========================================
            // TIPO 1: ARRASTRAR (DRAG & DROP)
            // ==========================================
            
            // Ejercicio #1: Definición Básica
            [
                'subtema_id' => $subVariable->id,
                'titulo' => 'Concepto de Variable',
                'pregunta' => 'Completa la definición arrastrando la palabra correcta.',
                'tipo_interaccion' => 'arrastrar',
                'dificultad' => 'facil',
                'solucion' => 'memoria',
                'contenido_juego' => json_encode([
                    'frase_parte_1' => 'Una variable es un espacio en',
                    'espacio_vacio' => '__________', 
                    'frase_parte_2' => 'donde guardamos un valor.',
                    'opciones' => ['disco', 'memoria', 'nube']
                ]),
            ],

            // Ejercicio #2: Sintaxis de Asignación
            [
                'subtema_id' => $subVariable->id,
                'titulo' => 'El Operador de Asignación',
                'pregunta' => '¿Qué símbolo se usa para guardar un valor en una variable?',
                'tipo_interaccion' => 'arrastrar',
                'dificultad' => 'facil',
                'solucion' => '=',
                'contenido_juego' => json_encode([
                    'frase_parte_1' => 'En programación, usamos el signo',
                    'espacio_vacio' => '___', 
                    'frase_parte_2' => 'para asignar un valor a la variable.',
                    'opciones' => ['==', ':', '=']
                ]),
            ],

            // ==========================================
            // TIPO 2: RELACIONAR (MATCHING)
            // ==========================================

            // Ejercicio #3: Conceptos Clave
            [
                'subtema_id' => $subVariable->id,
                'titulo' => 'Conceptos Fundamentales',
                'pregunta' => 'Relaciona cada término con su característica principal.',
                'tipo_interaccion' => 'relacionar',
                'dificultad' => 'medio',
                'solucion' => 'relacionar_conceptos',
                'contenido_juego' => json_encode([
                    'pares' => [
                        ['origen' => 'Variable', 'destino' => 'Su valor cambia'],
                        ['origen' => 'Constante', 'destino' => 'Su valor es fijo'],
                        ['origen' => 'Nombre', 'destino' => 'Identificador'],
                        ['origen' => 'Tipo', 'destino' => 'Define el dato']
                    ]
                ]),
            ],

            // Ejercicio #4: Código vs Acción
            [
                'subtema_id' => $subVariable->id,
                'titulo' => 'Código vs Significado',
                'pregunta' => 'Une la línea de código con lo que está haciendo.',
                'tipo_interaccion' => 'relacionar',
                'dificultad' => 'dificil',
                'solucion' => 'relacionar_codigo',
                'contenido_juego' => json_encode([
                    'pares' => [
                        ['origen' => 'int x;', 'destino' => 'Declaración'],
                        ['origen' => 'x = 10;', 'destino' => 'Asignación'],
                        ['origen' => 'int x = 10;', 'destino' => 'Inicialización'],
                        ['origen' => 'x = x + 1;', 'destino' => 'Incremento']
                    ]
                ]),
            ],
        ];

        DB::table('ejercicios')->insert($ejercicios);
    }
}