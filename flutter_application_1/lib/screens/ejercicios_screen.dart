import 'package:flutter/material.dart';
import '../models/subtema.dart';
import '../models/ejercicio.dart';
import '../services/api_service.dart';

class EjerciciosScreen extends StatefulWidget {
  final Subtema subtema;

  const EjerciciosScreen({super.key, required this.subtema});

  @override
  State<EjerciciosScreen> createState() => _EjerciciosScreenState();
}

class _EjerciciosScreenState extends State<EjerciciosScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Ejercicio>> ejerciciosFuture;

  // Controladores para guardar lo que escribe el usuario en cada ejercicio
  final Map<int, TextEditingController> _respuestasControllers = {};
  final Map<int, bool> _cargandoEvaluacion = {}; // Para mostrar spinner por ejercicio

  @override
  void initState() {
    super.initState();
    ejerciciosFuture = apiService.getEjerciciosPorSubtema(widget.subtema.id);
  }

  @override
  void dispose() {
    // Limpiar controladores de memoria
    for (var controller in _respuestasControllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _evaluar(int ejercicioId, BuildContext context) async {
    final respuesta = _respuestasControllers[ejercicioId]?.text;
    
    if (respuesta == null || respuesta.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Por favor escribe una respuesta')),
      );
      return;
    }

    setState(() => _cargandoEvaluacion[ejercicioId] = true);

    // Llamada a la IA
    final resultado = await apiService.evaluarRespuestaIA(ejercicioId, respuesta);

    setState(() => _cargandoEvaluacion[ejercicioId] = false);

    if (!mounted) return;

    // Mostrar Resultado (BottomSheet)
    _mostrarResultadoIA(context, resultado);
  }

  void _mostrarResultadoIA(BuildContext context, Map<String, dynamic> resultado) {
    bool esCorrecto = resultado['es_correcto'] == true || (resultado['calificacion'] ?? 0) > 60;
    String feedback = resultado['retroalimentacion'] ?? resultado['respuesta_ia'] ?? 'Sin comentarios';

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                esCorrecto ? Icons.check_circle : Icons.info,
                color: esCorrecto ? Colors.green : Colors.orange,
                size: 60,
              ),
              const SizedBox(height: 10),
              Text(
                esCorrecto ? '¡Bien hecho!' : 'A mejorar',
                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 15),
              Text(
                feedback,
                style: const TextStyle(fontSize: 16),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Entendido'),
              )
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Ejercicios: ${widget.subtema.titulo}')),
      body: FutureBuilder<List<Ejercicio>>(
        future: ejerciciosFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No hay ejercicios para este tema.'));
          }

          final ejercicios = snapshot.data!;

          return ListView.builder(
            padding: const EdgeInsets.all(10),
            itemCount: ejercicios.length,
            itemBuilder: (context, index) {
              final ejercicio = ejercicios[index];
              
              // Inicializar controlador si no existe
              _respuestasControllers.putIfAbsent(ejercicio.id, () => TextEditingController());
              
              return Card(
                margin: const EdgeInsets.only(bottom: 15),
                child: Padding(
                  padding: const EdgeInsets.all(15.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Título y Dificultad
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Ejercicio #${index + 1}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                          Chip(
                            label: Text(ejercicio.dificultad ?? 'Medio', style: const TextStyle(color: Colors.white, fontSize: 10)),
                            backgroundColor: Colors.blueAccent,
                            padding: EdgeInsets.zero,
                          )
                        ],
                      ),
                      const SizedBox(height: 10),
                      
                      // La Pregunta
                      Text(
                        ejercicio.pregunta,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 15),
                      
                      // Campo de Respuesta
                      TextField(
                        controller: _respuestasControllers[ejercicio.id],
                        maxLines: 3,
                        decoration: const InputDecoration(
                          hintText: 'Escribe tu respuesta aquí...',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 15),
                      
                      // Botón de Evaluar
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: (_cargandoEvaluacion[ejercicio.id] == true) 
                              ? null 
                              : () => _evaluar(ejercicio.id, context),
                          icon: (_cargandoEvaluacion[ejercicio.id] == true)
                              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                              : const Icon(Icons.auto_awesome),
                          label: Text((_cargandoEvaluacion[ejercicio.id] == true) ? ' Evaluando...' : 'Evaluar con IA'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.indigo,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}