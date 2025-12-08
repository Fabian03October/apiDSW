import 'package:flutter/material.dart';
import '../models/subtema.dart';
import '../services/api_service.dart';
import 'contenido_screen.dart';
import 'quiz_screen.dart'; 

class SubtemasScreen extends StatefulWidget {
  final int temaId;
  final String tituloTema;

  const SubtemasScreen({
    super.key,
    required this.temaId,
    required this.tituloTema,
  });

  @override
  State<SubtemasScreen> createState() => _SubtemasScreenState();
}

class _SubtemasScreenState extends State<SubtemasScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Subtema>> subtemasFuture;

  @override
  void initState() {
    super.initState();
    subtemasFuture = apiService.getSubtemasPorTema(widget.temaId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.tituloTema),
        backgroundColor: Colors.blue.shade50,
        // YA NO HAY BOTÓN AQUÍ (Eliminamos el actions)
      ),
      body: FutureBuilder<List<Subtema>>(
        future: subtemasFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No hay subtemas disponibles.'));
          }

          final subtemas = snapshot.data!;
          
          return ListView.builder(
            padding: const EdgeInsets.all(10),
            itemCount: subtemas.length,
            itemBuilder: (context, index) {
              final subtema = subtemas[index];
              return Card(
                elevation: 3,
                margin: const EdgeInsets.symmetric(vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: Column(
                  children: [
                    // Parte Principal: Título y descripción (Clic para leer teoría)
                    ListTile(
                      leading: CircleAvatar(
                        backgroundColor: Colors.indigo.shade100,
                        child: const Icon(Icons.menu_book, color: Colors.indigo),
                      ),
                      title: Text(subtema.titulo, style: const TextStyle(fontWeight: FontWeight.bold)),
                      subtitle: Text(subtema.descripcion ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
                      onTap: () {
                        // Ir a leer contenido
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => ContenidoScreen(subtema: subtema)),
                        );
                      },
                    ),
                    
                    // Parte Inferior: Botones de Acción
                    Padding(
                      padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          // Botón Específico para Generar Quiz de ESTE subtema
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.orange.shade100,
                              foregroundColor: Colors.orange.shade900,
                              elevation: 0,
                            ),
                            icon: const Icon(Icons.psychology, size: 18),
                            label: const Text('Quiz IA'),
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => QuizScreen(
                                    // Pasamos SOLO el subtemaId
                                    subtemaId: subtema.id,
                                    titulo: "Quiz: ${subtema.titulo}",
                                  ),
                                ),
                              );
                            },
                          ),
                          const SizedBox(width: 10),
                          // Botón para ver contenido (opcional, ya que el tap del tile lo hace)
                          const Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey),
                        ],
                      ),
                    )
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}