import 'package:flutter/material.dart';
import '../models/tema.dart';
import '../services/api_service.dart';
import 'subtemas_screen.dart'; 

class TemasScreen extends StatefulWidget {
  final int materiaId;
  final String tituloMateria;

  const TemasScreen({
    super.key, 
    required this.materiaId, 
    required this.tituloMateria
  });

  @override
  State<TemasScreen> createState() => _TemasScreenState();
}

class _TemasScreenState extends State<TemasScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Tema>> temasFuture;

  @override
  void initState() {
    super.initState();
    // Cargamos los temas usando el ID que recibimos
    temasFuture = apiService.getTemasPorMateria(widget.materiaId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.tituloMateria)), // Título dinámico
      body: FutureBuilder<List<Tema>>(
        future: temasFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('Esta materia no tiene temas aún.'));
          }

          final temas = snapshot.data!;
          return ListView.builder(
            itemCount: temas.length,
            itemBuilder: (context, index) {
              return Card(
                child: ListTile(
                  leading: const Icon(Icons.library_books, color: Colors.orange),
                  title: Text(temas[index].titulo),
                  subtitle: Text(temas[index].descripcion ?? ''),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {
                    // ¡AQUÍ ES DONDE SEGUIRÍAS A SUBTEMAS!
                    // Navigator.push(...)
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Ir a subtemas de: ${temas[index].titulo}')),
                    );
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => SubtemasScreen(
                          temaId: temas[index].id,
                          tituloTema: temas[index].titulo,
                        ),
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