import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart'; // Para borrar token al salir
import '../models/materia.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'temas_screen.dart'; 

class MateriasScreen extends StatefulWidget {
  const MateriasScreen({super.key});

  @override
  State<MateriasScreen> createState() => _MateriasScreenState();
}

class _MateriasScreenState extends State<MateriasScreen> {
  final ApiService apiService = ApiService();
  late Future<List<Materia>> materiasFuture;

  @override
  void initState() {
    super.initState();
    materiasFuture = apiService.getMaterias();
  }

  // Función para cerrar sesión
  void _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token'); // Borramos el token

    if (mounted) {
      // Volvemos al Login y eliminamos el historial de navegación
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Materias'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
            tooltip: 'Cerrar Sesión',
          )
        ],
      ),
      body: FutureBuilder<List<Materia>>(
        future: materiasFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No tienes materias asignadas'));
          }

          final materias = snapshot.data!;
          
          return ListView.builder(
            padding: const EdgeInsets.all(10),
            itemCount: materias.length,
            itemBuilder: (context, index) {
              final materia = materias[index];
              return Card(
                elevation: 3,
                margin: const EdgeInsets.symmetric(vertical: 8),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: Colors.blue.shade100,
                    child: Text(
                      materia.titulo.isNotEmpty ? materia.titulo[0] : '?', // Si está vacío pone un '?'
                    ),
                  ),
                  title: Text(
                    materia.titulo,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  subtitle: Text(
                    materia.descripcion ?? 'Sin descripción',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Seleccionaste: ${materia.titulo}')),
                    );
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => TemasScreen(
                          materiaId: materia.id,
                          tituloMateria: materia.titulo,
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