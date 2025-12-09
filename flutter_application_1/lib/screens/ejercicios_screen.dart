import 'package:flutter/material.dart';
import '../models/subtema.dart';
import '../models/ejercicio.dart';
import '../services/api_service.dart';
import '../widgets/juegos/juego_arrastrar.dart';
import '../widgets/juegos/juego_relacionar.dart';

class EjerciciosScreen extends StatefulWidget {
  final Subtema subtema;
  const EjerciciosScreen({super.key, required this.subtema});

  @override
  State<EjerciciosScreen> createState() => _EjerciciosScreenState();
}

class _EjerciciosScreenState extends State<EjerciciosScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Ejercicio>> ejerciciosFuture;
  
  final PageController _pageController = PageController();
  final Map<int, String> _respuestas = {}; // Guardamos respuesta por ID de ejercicio
  int _indexActual = 0;
  bool _enviando = false;

  @override
  void initState() {
    super.initState();
    ejerciciosFuture = apiService.getEjerciciosPorSubtema(widget.subtema.id);
  }

  // Enviar todo el paquete a la IA
  Future<void> _finalizar(List<Ejercicio> listaEjercicios) async {
    setState(() => _enviando = true);

    try {
      // Construimos el payload para el endpoint 'evaluarConjuntoEjercicios'
      List<Map<String, dynamic>> payload = [];
      
      for (var ej in listaEjercicios) {
        payload.add({
          'ejercicio_id': ej.id,
          'respuesta': _respuestas[ej.id] ?? "Sin responder"
        });
      }

      // Usamos el servicio de "Evaluar Quiz" que ya creamos (o el de conjunto)
      // Asegúrate de tener apiService.evaluarQuiz o similar implementado
      final resultado = await apiService.evaluarQuiz(payload); // Reutilizamos la logica batch

      if (!mounted) return;
      _mostrarResultadosFinales(resultado);

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    } finally {
      setState(() => _enviando = false);
    }
  }

  void _mostrarResultadosFinales(Map<String, dynamic> data) {
    // 1. Extraemos los datos de forma segura
    // Nota: Asegúrate de que los nombres de las claves coincidan con tu Prompt de Laravel
    final int nota = data['nota_global'] is int ? data['nota_global'] : int.tryParse(data['nota_global'].toString()) ?? 0;
    final String comentarioGeneral = data['comentario_general'] ?? 'Sin comentarios adicionales.';
    final List<dynamic> detalles = data['detalles'] ?? [];
    
    final bool aprobado = nota >= 60;

    showModalBottomSheet(
      context: context,
      isDismissible: false, // Obliga a usar el botón para cerrar
      enableDrag: false,
      isScrollControlled: true, // Permite que la hoja crezca
      backgroundColor: Colors.transparent, // Para ver las esquinas redondeadas
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.7, // Empieza al 70% de la pantalla
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, scrollController) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(25.0)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
          child: Column(
            children: [
              // Barra indicadora para deslizar
              Container(
                width: 50,
                height: 5,
                margin: const EdgeInsets.only(bottom: 20, top: 10),
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(10),
                ),
              ),

              // Título y Nota
              Expanded(
                child: ListView(
                  controller: scrollController,
                  children: [
                    // --- ENCABEZADO DE NOTA ---
                    Center(
                      child: Column(
                        children: [
                          Icon(
                            aprobado ? Icons.verified : Icons.warning_amber_rounded,
                            size: 80,
                            color: aprobado ? Colors.green : Colors.orange,
                          ),
                          const SizedBox(height: 10),
                          Text(
                            aprobado ? '¡Excelente Trabajo!' : 'A repasar un poco',
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 5),
                          Text(
                            '$nota/100',
                            style: TextStyle(
                              fontSize: 40,
                              fontWeight: FontWeight.w900,
                              color: aprobado ? Colors.green[700] : Colors.orange[800],
                            ),
                          ),
                        ],
                      ),
                    ),
                    
                    const SizedBox(height: 20),
                    
                    // --- COMENTARIO GENERAL ---
                    Container(
                      padding: const EdgeInsets.all(15),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue.shade100),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(children: [
                            Icon(Icons.psychology, color: Colors.blue.shade800),
                            const SizedBox(width: 8),
                            Text("Análisis de la IA:", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue.shade900))
                          ]),
                          const SizedBox(height: 8),
                          Text(
                            comentarioGeneral,
                            style: TextStyle(fontSize: 16, color: Colors.blue.shade900, height: 1.4),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 25),
                    const Text("Detalle por Ejercicio:", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 10),

                    // --- LISTA DE DETALLES ---
                    ...detalles.asMap().entries.map((entry) {
                      int numeroVisual = entry.key + 1; // Genera 1, 2, 3, 4... siempre
                      var item = entry.value;
                      bool esCorrecto = item['es_correcto'] == true;

                      return Card(
                        margin: const EdgeInsets.only(bottom: 15),
                        elevation: 2,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: BorderSide(
                            color: esCorrecto ? Colors.green.withOpacity(0.5) : Colors.red.withOpacity(0.5),
                            width: 1
                          )
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    esCorrecto ? Icons.check_circle : Icons.cancel,
                                    color: esCorrecto ? Colors.green : Colors.red,
                                  ),
                                  const SizedBox(width: 10),
                                  Text(
                                    "Ejercicio $numeroVisual", // <--- AQUI ESTÁ EL CAMBIO
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                  const Spacer(),
                                  if(!esCorrecto)
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(color: Colors.red[50], borderRadius: BorderRadius.circular(5)),
                                      child: const Text("Incorrecto", style: TextStyle(color: Colors.red, fontSize: 12)),
                                    )
                                ],
                              ),
                              const Divider(),
                              Text(
                                item['feedback'] ?? "Sin comentarios",
                                style: TextStyle(color: Colors.grey[800], height: 1.3),
                              ),
                            ],
                          ),
                        ),
                      );
                    }), // Fin del map
                    
                    const SizedBox(height: 20),
                  ],
                ),
              ),

              // --- BOTÓN DE SALIDA ---
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: Colors.indigo,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () {
                    Navigator.pop(context); // Cierra modal
                    Navigator.pop(context); // Sale de la pantalla de ejercicios
                  },
                  child: const Text("FINALIZAR REVISIÓN", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              )
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.subtema.titulo}'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(4),
          child: FutureBuilder<List<Ejercicio>>(
            future: ejerciciosFuture,
            builder: (c, s) {
              if (!s.hasData) return const LinearProgressIndicator();
              return LinearProgressIndicator(
                value: (_indexActual + 1) / s.data!.length,
                backgroundColor: Colors.grey[300],
              );
            },
          ),
        ),
      ),
      body: FutureBuilder<List<Ejercicio>>(
        future: ejerciciosFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
          if (!snapshot.hasData || snapshot.data!.isEmpty) return const Center(child: Text("No hay ejercicios."));

          final ejercicios = snapshot.data!;

          return Column(
            children: [
              Expanded(
                child: PageView.builder(
                  controller: _pageController,
                  physics: const NeverScrollableScrollPhysics(), // Bloquea deslizamiento manual
                  itemCount: ejercicios.length,
                  itemBuilder: (context, index) {
                    return SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: _buildEjercicioContent(ejercicios[index]),
                    );
                  },
                ),
              ),
              
              // Botón de Acción Inferior
              Container(
                padding: const EdgeInsets.all(16),
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    backgroundColor: Colors.indigo,
                    foregroundColor: Colors.white
                  ),
                  onPressed: _enviando ? null : () {
                    if (_indexActual < ejercicios.length - 1) {
                      // Siguiente Pregunta
                      _pageController.nextPage(duration: const Duration(milliseconds: 300), curve: Curves.ease);
                      setState(() => _indexActual++);
                    } else {
                      // Finalizar
                      _finalizar(ejercicios);
                    }
                  },
                  child: _enviando 
                    ? const CircularProgressIndicator(color: Colors.white)
                    : Text(_indexActual == ejercicios.length - 1 ? "FINALIZAR Y EVALUAR" : "SIGUIENTE"),
                ),
              )
            ],
          );
        },
      ),
    );
  }

  Widget _buildEjercicioContent(Ejercicio ejercicio) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(ejercicio.pregunta, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 20),
        
        // Renderizado del Juego según tipo
        if (ejercicio.tipoInteraccion == 'arrastrar')
          JuegoArrastrar(
            datos: ejercicio.contenidoJuego ?? {},
            onRespuestaChanged: (val) => _respuestas[ejercicio.id] = val,
          )
        else if (ejercicio.tipoInteraccion == 'relacionar')
          JuegoRelacionar(
            datos: ejercicio.contenidoJuego ?? {},
            onRespuestaChanged: (val) => _respuestas[ejercicio.id] = val,
          )
        else
          // Texto libre por defecto
          TextField(
            onChanged: (val) => _respuestas[ejercicio.id] = val,
            decoration: const InputDecoration(
              hintText: "Escribe tu respuesta...",
              border: OutlineInputBorder()
            ),
            maxLines: 3,
          )
      ],
    );
  }
}