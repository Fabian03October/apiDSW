class Materia {
  final int id;
  final String titulo;
  final String? descripcion; // Puede ser nulo

  Materia({required this.id, required this.titulo, this.descripcion});

  // Factory para convertir el JSON de Laravel a un Objeto Dart
  factory Materia.fromJson(Map<String, dynamic> json) {
    return Materia(
      id: json['id'],
      titulo: json['titulo'],
      descripcion: json['descripcion'],
    );
  }
}