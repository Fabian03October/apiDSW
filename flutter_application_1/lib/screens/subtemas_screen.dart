import 'package:flutter/material.dart';
import '../models/subtema.dart';
import '../services/api_service.dart';
import 'contenido_screen.dart'; 

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
    // Llamamos al servicio para buscar subtemas de este tema específico
    subtemasFuture = apiService.getSubtemasPorTema(widget.temaId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.tituloTema),
        backgroundColor: Colors.blue.shade50,
      ),
      body: FutureBuilder<List<Subtema>>(
        future: subtemasFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.folder_open, size: 50, color: Colors.grey),
                  SizedBox(height: 10),
                  Text('No hay subtemas disponibles.'),
                ],
              ),
            );
          }

          final subtemas = snapshot.data!;
          
          return ListView.builder(
            padding: const EdgeInsets.all(10),
            itemCount: subtemas.length,
            itemBuilder: (context, index) {
              final subtema = subtemas[index];
              return Card(
                elevation: 2,
                margin: const EdgeInsets.symmetric(vertical: 6),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: Colors.green.shade100,
                    child: const Icon(Icons.article, color: Colors.green),
                  ),
                  title: Text(
                    subtema.titulo,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text(
                    subtema.descripcion ?? 'Sin descripción',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: const Icon(Icons.arrow_forward, size: 20, color: Colors.grey),
                  onTap: () {
                    // AQUÍ CONECTAREMOS CON LA PANTALLA DE CONTENIDO/QUIZ
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Abriendo: ${subtema.titulo}')),
                    );
                    
                  
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ContenidoScreen(subtema: subtema),
                      ),
                    );
                    
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}