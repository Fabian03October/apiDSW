class Tema {
  final int id;
  final int materiaId;
  final String titulo;
  final String? descripcion;

  Tema({
    required this.id, 
    required this.materiaId, 
    required this.titulo, 
    this.descripcion
  });

  factory Tema.fromJson(Map<String, dynamic> json) {
    return Tema(
      id: json['id'],
      materiaId: int.parse(json['materia_id'].toString()), // Asegura que sea int
      titulo: json['titulo'],
      descripcion: json['descripcion'],
    );
  }
}