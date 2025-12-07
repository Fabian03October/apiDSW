import 'package:flutter/material.dart';
import '../models/subtema.dart';
import '../models/contenido.dart';
import '../services/api_service.dart';
import 'ejercicios_screen.dart';

class ContenidoScreen extends StatefulWidget {
  final Subtema subtema; // Recibimos el objeto subtema completo

  const ContenidoScreen({super.key, required this.subtema});

  @override
  State<ContenidoScreen> createState() => _ContenidoScreenState();
}

class _ContenidoScreenState extends State<ContenidoScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Contenido>> contenidosFuture;

  @override
  void initState() {
    super.initState();
    contenidosFuture = apiService.getContenidosPorSubtema(widget.subtema.id);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.subtema.titulo)),
      body: FutureBuilder<List<Contenido>>(
        future: contenidosFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No hay contenido cargado.'));
          }

          final contenidos = snapshot.data!;

          return SingleChildScrollView( // Permite hacer scroll si el texto es largo
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 1. Información General del Subtema (si la hay)
                if (widget.subtema.informacion != null) ...[
                  Text(
                    'Introducción',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.blue.shade800),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    widget.subtema.informacion!,
                    style: const TextStyle(fontSize: 16, height: 1.5),
                  ),
                  const Divider(height: 30),
                ],

                // 2. Renderizar los Contenidos (Texto, Videos, etc)
                ...contenidos.map((contenido) {
                  return Container(
                    margin: const EdgeInsets.only(bottom: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          contenido.titulo,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        // Aquí podríamos validar si es VIDEO o TEXTO
                        contenido.tipoContenido == 'video'
                            ? Container(
                                height: 150,
                                color: Colors.black12,
                                child: const Center(child: Icon(Icons.play_circle_fill, size: 50, color: Colors.red)),
                              )
                            : Text(
                                contenido.cuerpo,
                                style: const TextStyle(fontSize: 16),
                              ),
                      ],
                    ),
                  );
                }),

                const SizedBox(height: 20),

                // 3. Botón para ir a Ejercicios
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      backgroundColor: Colors.blue,
                      foregroundColor: Colors.white,
                    ),
                    icon: const Icon(Icons.quiz),
                    label: const Text('IR A LOS EJERCICIOS', style: TextStyle(fontSize: 16)),
                    onPressed: () {
                      // AQUÍ IMPLEMENTAREMOS LA PANTALLA DE EJERCICIOS + IA
                      ScaffoldMessenger.of(context).showSnackBar(
                         const SnackBar(content: Text('Preparate para el cuestionario')),
                      );
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => EjerciciosScreen(subtema: widget.subtema),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}