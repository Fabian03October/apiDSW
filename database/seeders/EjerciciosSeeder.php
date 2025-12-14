<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Materia;
use App\Models\Tema;
use App\Models\Subtema;
use App\Models\Contenido;

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

        // 2.5 AGREGAR CONTENIDO EDUCATIVO DEL SUBTEMA
        $this->crearContenidoVariable($subVariable);

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

    /**
     * Crear contenido educativo para el subtema de variables
     */
    private function crearContenidoVariable(Subtema $subVariable): void
    {
        // Limpiar contenidos anteriores para evitar duplicados
        DB::table('contenidos')->where('subtema_id', $subVariable->id)->delete();

        // Contenido 1: Concepto Fundamental
        Contenido::create([
            'subtema_id' => $subVariable->id,
            'titulo' => 'Concepto Fundamental',
            'tipo_contenido' => 'TEXTO',
            'cuerpo' => "Una variable es un espacio en la memoria del computador que almacena un valor que puede cambiar durante la ejecución de un programa.\n\n¿POR QUÉ SON IMPORTANTES LAS VARIABLES?\n\n• Almacenar datos: Guardan información que el programa necesita usar\n\n• Manipular información: Permiten cambiar y procesar datos\n\n• Reutilizar valores: Podemos usar el mismo dato múltiples veces sin escribirlo nuevamente\n\n• Hacer programas dinámicos: El programa puede adaptarse a diferentes valores durante su ejecución\n\nANALOGÍA DE LA VIDA REAL\n\nImagina una caja etiquetada:\n\n• La etiqueta es el nombre de la variable\n\n• Lo que hay dentro de la caja es el valor almacenado\n\n• El contenedor es la memoria del computador\n\nAsí como puedes cambiar lo que hay en una caja física, también puedes cambiar el valor de una variable durante la ejecución del programa."
        ]);

        // Contenido 2: Componentes de una Variable
        Contenido::create([
            'subtema_id' => $subVariable->id,
            'titulo' => 'Componentes de una Variable',
            'tipo_contenido' => 'TEXTO',
            'cuerpo' => "PARTES ESENCIALES DE UNA VARIABLE\n\n1. NOMBRE (IDENTIFICADOR)\n\nEs el nombre que le damos a la variable para referenciarnos a ella. Debe ser:\n\n• Único dentro del ámbito\n• Descriptivo (que tenga sentido)\n• Sin espacios\n• Puede contener letras, números y guiones bajos\n• No puede empezar con número\n\nEjemplos buenos: edad, nombre_usuario, total_ventas\nEjemplos malos: x, a1b2c3, 123valor\n\n2. TIPO DE DATO\n\nDefine qué clase de información puede guardar la variable:\n\n• int (entero): números sin decimales (-5, 0, 100)\n• float/double (decimal): números con decimales (3.14, 2.5)\n• string (texto): palabras y frases (\"Hola\", \"Juan\")\n• boolean (lógico): verdadero o falso (true, false)\n• char (carácter): un solo carácter ('A', '5', '@')\n\n3. VALOR\n\nEs el dato actual almacenado en la variable. Puede cambiar a lo largo del programa.\n\nESTRUCTURA COMPLETA\n\n[Tipo] [Nombre] = [Valor];\n\nEjemplo: int edad = 18;\n\n• Tipo: int\n• Nombre: edad\n• Valor: 18"
        ]);

        // Contenido 3: Operaciones Básicas
        Contenido::create([
            'subtema_id' => $subVariable->id,
            'titulo' => 'Operaciones con Variables',
            'tipo_contenido' => 'TEXTO',
            'cuerpo' => "OPERACIONES BÁSICAS\n\nDECLARACIÓN\n\nCrear una variable sin asignarle un valor inicial:\n\nint numero;\n\nLa variable existe en memoria pero no sabemos qué valor tiene.\n\nINICIALIZACIÓN\n\nCrear una variable y asignarle un valor inicial:\n\nint numero = 5;\n\nASIGNACIÓN\n\nCambiar el valor de una variable que ya existe:\n\nnumero = 10;\n\nAhora la variable numero vale 10, no 5.\n\nLECTURA\n\nUsar el valor de una variable en operaciones:\n\nint a = 5;\nint b = 3;\nint suma = a + b;  // suma = 8\nSystem.out.println(a);  // Imprime: 5\n\nOPERACIONES ARITMÉTICAS\n\n• + (suma): int c = a + b;\n• - (resta): int c = a - b;\n• * (multiplicación): int c = a * b;\n• / (división): int c = a / b;\n• % (módulo/residuo): int c = a % b;\n\nINCREMENTO Y DECREMENTO\n\n• x++ (incrementa 1): int x = 5; x++; // x = 6\n• x-- (decrementa 1): int x = 5; x--; // x = 4"
        ]);

        // Contenido 4: Ejemplo Práctico
        Contenido::create([
            'subtema_id' => $subVariable->id,
            'titulo' => 'Ejemplo Práctico Completo',
            'tipo_contenido' => 'TEXTO',
            'cuerpo' => "PROGRAMA DE EJEMPLO: CALCULADORA SIMPLE\n\n// Declaramos las variables\nint numero1 = 10;\nint numero2 = 5;\nint suma;\nint resta;\nint multiplicacion;\n\n// Realizamos operaciones\nsuma = numero1 + numero2;           // suma = 15\nresta = numero1 - numero2;          // resta = 5\nmultiplicacion = numero1 * numero2; // multiplicacion = 50\n\n// Mostramos resultados\nSystem.out.println(\"Suma: \" + suma);\nSystem.out.println(\"Resta: \" + resta);\nSystem.out.println(\"Multiplicación: \" + multiplicacion);\n\n// Podemos cambiar los valores\nnumero1 = 20;\nnumero2 = 3;\n\nsuma = numero1 + numero2;  // Ahora suma = 23\nSystem.out.println(\"Nueva suma: \" + suma);\n\nEXPLICACIÓN PASO A PASO\n\n1. Creamos tres variables: numero1, numero2, suma, etc.\n2. Inicializamos numero1 con 10 y numero2 con 5\n3. La variable suma recibe el resultado de sumar los dos números\n4. Usamos System.out.println() para mostrar los valores\n5. Luego cambiamos los valores de numero1 y numero2\n6. Recalculamos la suma con los nuevos valores\n\n¿QUÉ APRENDEMOS?\n\n• Las variables pueden almacenar datos\n• Podemos usar variables en operaciones matemáticas\n• Los valores de las variables pueden cambiar durante la ejecución\n• Podemos recalcular valores si las variables cambian"
        ]);

        // Contenido 5: Reglas y Buenas Prácticas
        Contenido::create([
            'subtema_id' => $subVariable->id,
            'titulo' => 'Buenas Prácticas y Errores Comunes',
            'tipo_contenido' => 'TEXTO',
            'cuerpo' => "BUENAS PRÁCTICAS\n\n✓ NOMBRES DESCRIPTIVOS\n\nBueno: int edad_usuario = 25;\nMalo: int x = 25;\n\nUn nombre descriptivo hace el código más legible y fácil de entender.\n\n✓ INICIALIZAR VARIABLES\n\nBueno: int contador = 0;\nMalo: int contador; (sin valor inicial)\n\nSiempre inicializa tus variables para evitar valores indefinidos.\n\n✓ USAR EL TIPO CORRECTO\n\nBueno: double precio = 19.99;\nMalo: int precio = 19; (para un precio con decimales)\n\nElige el tipo de dato que mejor represente tu información.\n\nERRORES COMUNES\n\n❌ Usar una variable sin declararla\nSystem.out.println(nombre);  // Error: nombre no fue declarada\n\n❌ Usar un nombre de variable con espacios\nint mi edad = 25;  // Error: sintaxis incorrecta\n\n❌ Asignar un valor del tipo incorrecto\nint edad = \"veinticinco\";  // Error: string a int\n\n❌ Usar un nombre de variable que ya existe\nint edad = 25;\nint edad = 30;  // Error: edad ya fue declarada\n\n❌ Olvidar el punto y coma\nint numero = 5  // Error: falta punto y coma\n\nRESUMEN\n\n• Siempre declara e inicializa tus variables\n• Usa nombres descriptivos y en minúsculas con guiones bajos\n• Elige el tipo de dato apropiado\n• No olvides el punto y coma al final\n• Las variables pueden cambiar de valor durante la ejecución"
        ]);
    }
}