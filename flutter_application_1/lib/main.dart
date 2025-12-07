import 'package:flutter/material.dart';
import 'models/materia.dart';
import 'services/api_service.dart';
import 'screens/login_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false, // Quita la etiqueta 'DEBUG'
      title: 'App Educativa',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
      ),
      home: const LoginScreen(), // <--- Arranca aquí
    );
  }
}

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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Materias Disponibles')),
      body: FutureBuilder<List<Materia>>(
        future: materiasFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No hay materias aún'));
          }

          // Si hay datos, mostramos la lista
          final materias = snapshot.data!;
          return ListView.builder(
            itemCount: materias.length,
            itemBuilder: (context, index) {
              return Card(
                margin: const EdgeInsets.all(8.0),
                child: ListTile(
                  leading: const Icon(Icons.book, color: Colors.blue),
                  title: Text(materias[index].titulo),
                  subtitle: Text(materias[index].descripcion ?? ''),
                  onTap: () {
                    // Aquí navegaremos a los temas más adelante
                    print('Click en ${materias[index].titulo}');
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